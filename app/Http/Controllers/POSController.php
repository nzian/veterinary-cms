<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Owner;
use App\Models\Billing;
use App\Models\Prescription;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    /**
     * Show POS page - Get all products and pending bills
     */
    public function index(Request $request)
    {
        // Get owners for dropdown
        $owners = Owner::select('own_id', 'own_name')
            ->orderBy('own_name', 'asc')
            ->get();

        // Get pending billings for payment
        $billings = Billing::with([
            'appointment.pet.owner', 
            'appointment.services'
        ])
        ->where('bill_status', 'Pending')
        ->orderBy('bill_date', 'desc')
        ->get();

        // Calculate billing totals including prescriptions
        foreach ($billings as $billing) {
            $servicesTotal = 0;
            $prescriptionTotal = 0;
            
            // Services total
            if ($billing->appointment && $billing->appointment->services) {
                $servicesTotal = $billing->appointment->services->sum('serv_price');
            }
            
            // Prescription products total
            if ($billing->appointment && $billing->appointment->pet) {
                $prescriptions = Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                    ->whereDate('prescription_date', '<=', $billing->bill_date)
                    ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                    ->get();
                
                foreach ($prescriptions as $prescription) {
                    $medications = json_decode($prescription->medication, true) ?? [];
                    foreach ($medications as $medication) {
                        if (isset($medication['product_id']) && $medication['product_id']) {
                            $product = DB::table('tbl_prod')
                                ->where('prod_id', $medication['product_id'])
                                ->first();
                            
                            if ($product) {
                                $prescriptionTotal += $product->prod_price;
                            }
                        }
                    }
                }
            }
            
            $billing->calculated_total = $servicesTotal + $prescriptionTotal;
        }

        // Add Walk-in Customer option
        $owners->prepend((object)[
            'own_id' => 0,
            'own_name' => 'Walk-in Customer',
        ]);

        // Get ALL products that have stock
        $items = Product::select('prod_id', 'prod_name', 'prod_price', 'prod_stocks', 'prod_category')
            ->where('prod_stocks', '>', 0)
            ->orderBy('prod_name', 'asc')
            ->get();

        Log::info("POS loaded: " . $items->count() . " products, " . $billings->count() . " pending bills");

        return view('pos', compact('owners', 'items', 'billings'));
    }

    /**
     * Store POS sale - For walk-in customers buying products only
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:tbl_prod,prod_id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'cash' => 'required|numeric|min:0',
                'owner_id' => 'nullable|integer',
            ]);

            $cash = $validated['cash'];
            $total = $validated['total'];
            $change = $cash - $total;

            if ($change < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient cash amount.'
                ], 422);
            }

            DB::beginTransaction();

            // Check stock availability first
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product with ID {$item['product_id']} not found.");
                }

                if ($product->prod_stocks < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->prod_name}. Available: {$product->prod_stocks}");
                }
            }

            // Check what columns exist in tbl_ord table
            $orderColumns = [];
            try {
                $columns = DB::select("DESCRIBE tbl_ord");
                $orderColumns = array_column($columns, 'Field');
                Log::info('Order table columns: ' . implode(', ', $orderColumns));
            } catch (\Exception $e) {
                Log::warning('Could not check order table structure: ' . $e->getMessage());
            }

            // Create order records for each item (direct sales)
            $orderIds = [];
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                // Build order data with only existing columns
                $orderData = [
                    'ord_quantity' => $item['quantity'],
                    'ord_date' => now(),
                    'prod_id' => $product->prod_id,
                ];

                // Add optional columns if they exist
                if (in_array('ord_total', $orderColumns)) {
                    $orderData['ord_total'] = $item['price'] * $item['quantity'];
                }
                if (in_array('user_id', $orderColumns)) {
                    $orderData['user_id'] = Auth::id();
                }
                if (in_array('own_id', $orderColumns) && !empty($validated['owner_id']) && $validated['owner_id'] != 0) {
                    $orderData['own_id'] = $validated['owner_id'];
                }
                if (in_array('payment_method', $orderColumns)) {
                    $orderData['payment_method'] = 'Cash';
                }
                if (in_array('payment_status', $orderColumns)) {
                    $orderData['payment_status'] = 'Paid';
                }
                if (in_array('order_type', $orderColumns)) {
                    $orderData['order_type'] = 'Direct Sale';
                }

                $orderId = DB::table('tbl_ord')->insertGetId($orderData);
                $orderIds[] = $orderId;

                // Deduct stock
                $product->prod_stocks -= $item['quantity'];
                $product->save();

                Log::info("Direct Sale: {$product->prod_name} x{$item['quantity']} - Stock reduced to {$product->prod_stocks}");
            }

            DB::commit();

            Log::info("POS Direct Sale completed: ₱{$total} paid with ₱{$cash}, change: ₱{$change}");

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully!',
                'change' => $change,
                'items_sold' => count($validated['items']),
                'order_ids' => $orderIds
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('POS Direct Sale Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay an existing billing record
     */
    public function payBilling(Request $request, $billingId)
{
    try {
        $validated = $request->validate([
            'cash' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
        ]);

        $cash = $validated['cash'];
        $total = $validated['total'];
        $change = $cash - $total;

        if ($change < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient cash amount.'
            ], 422);
        }

        DB::beginTransaction();

        // Get billing record
        $billing = Billing::with([
            'appointment.pet.owner', 
            'appointment.services'
        ])->findOrFail($billingId);

        if ($billing->bill_status === 'Paid') {
            throw new \Exception('This bill has already been paid.');
        }

        // Check what columns exist in tbl_ord table
        $orderColumns = [];
        try {
            $columns = DB::select("DESCRIBE tbl_ord");
            $orderColumns = array_column($columns, 'Field');
            Log::info('Order table columns for billing: ' . implode(', ', $orderColumns));
        } catch (\Exception $e) {
            Log::warning('Could not check order table structure: ' . $e->getMessage());
        }

        // Update billing status and payment details
        $billing->update([
            'bill_status' => 'Paid',
            'bill_cash' => $cash,
            'bill_change' => $change,
            'paid_at' => now(),
            'paid_by' => Auth::id()
        ]);

        // Create or update payment record
        if (!$billing->payment) {
            Payment::create([
                'bill_id' => $billing->bill_id,
                'pay_total' => $total,
                'pay_cashAmount' => $cash,
                'pay_change' => $change,
                'pay_date' => now()
            ]);
        } else {
            $billing->payment->update([
                'pay_total' => $total,
                'pay_cashAmount' => $cash,
                'pay_change' => $change,
                'pay_date' => now()
            ]);
        }

        // Process prescription products and deduct from inventory
        $processedPrescriptions = [];
        if ($billing->appointment && $billing->appointment->pet) {
            $prescriptions = Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                ->whereDate('prescription_date', '<=', $billing->bill_date)
                ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                ->get();
            
            foreach ($prescriptions as $prescription) {
                $medications = json_decode($prescription->medication, true) ?? [];
                foreach ($medications as $medication) {
                    if (isset($medication['product_id']) && $medication['product_id']) {
                        $product = Product::find($medication['product_id']);
                        
                        if ($product && $product->prod_stocks > 0) {
                            $quantity = isset($medication['quantity']) ? intval($medication['quantity']) : 1;
                            
                            // Check stock availability
                            if ($product->prod_stocks < $quantity) {
                                Log::warning("Insufficient stock for prescription item {$product->prod_name}. Available: {$product->prod_stocks}, Required: {$quantity}");
                                continue; // Skip this item
                            }
                            
                            // Build order data with only existing columns
                            $orderData = [
                                'ord_quantity' => $quantity,
                                'ord_date' => now(),
                                'prod_id' => $product->prod_id,
                            ];

                            // Add optional columns if they exist
                            if (in_array('ord_total', $orderColumns)) {
                                $orderData['ord_total'] = $product->prod_price * $quantity;
                            }
                            if (in_array('user_id', $orderColumns)) {
                                $orderData['user_id'] = Auth::id();
                            }
                            if (in_array('bill_id', $orderColumns)) {
                                $orderData['bill_id'] = $billing->bill_id;
                            }
                            if (in_array('payment_method', $orderColumns)) {
                                $orderData['payment_method'] = 'Cash';
                            }
                            if (in_array('payment_status', $orderColumns)) {
                                $orderData['payment_status'] = 'Paid';
                            }
                            if (in_array('order_type', $orderColumns)) {
                                $orderData['order_type'] = 'Prescription';
                            }

                            // Create order record for prescription item
                            $orderId = DB::table('tbl_ord')->insertGetId($orderData);

                            // Deduct stock
                            $newStock = $product->prod_stocks - $quantity;
                            $product->prod_stocks = $newStock;
                            $product->save();

                            $processedPrescriptions[] = [
                                'product_name' => $product->prod_name,
                                'quantity' => $quantity,
                                'remaining_stock' => $newStock,
                                'order_id' => $orderId
                            ];

                            Log::info("Prescription fulfilled: {$product->prod_name} x{$quantity} - Stock reduced to {$newStock}");
                        }
                    }
                }
            }
        }

        DB::commit();

        Log::info("Billing payment completed: Bill #{$billingId}, ₱{$total} paid with ₱{$cash}, change: ₱{$change}");

        return response()->json([
            'success' => true,
            'message' => 'Billing payment successful!',
            'bill_id' => $billingId,
            'change' => $change,
            'prescription_items_processed' => $processedPrescriptions
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid data: ' . implode(', ', $e->validator->errors()->all())
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Billing Payment Error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Get billing details for payment
     */
    public function getBillingDetails($billingId)
    {
        try {
            $billing = Billing::with([
                'appointment.pet.owner', 
                'appointment.services'
            ])->findOrFail($billingId);

            // Calculate totals
            $servicesTotal = 0;
            $prescriptionTotal = 0;
            $prescriptionItems = [];
            
            // Services total
            if ($billing->appointment && $billing->appointment->services) {
                $servicesTotal = $billing->appointment->services->sum('serv_price');
            }
            
            // Prescription products total
            if ($billing->appointment && $billing->appointment->pet) {
                $prescriptions = Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                    ->whereDate('prescription_date', '<=', $billing->bill_date)
                    ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                    ->get();
                
                foreach ($prescriptions as $prescription) {
                    $medications = json_decode($prescription->medication, true) ?? [];
                    foreach ($medications as $medication) {
                        if (isset($medication['product_id']) && $medication['product_id']) {
                            $product = DB::table('tbl_prod')
                                ->where('prod_id', $medication['product_id'])
                                ->first();
                            
                            if ($product) {
                                $prescriptionItems[] = [
                                    'name' => $product->prod_name,
                                    'price' => $product->prod_price
                                ];
                                $prescriptionTotal += $product->prod_price;
                            }
                        }
                    }
                }
            }

            $grandTotal = $servicesTotal + $prescriptionTotal;

            return response()->json([
                'success' => true,
                'billing' => $billing,
                'services_total' => $servicesTotal,
                'prescription_total' => $prescriptionTotal,
                'prescription_items' => $prescriptionItems,
                'grand_total' => $grandTotal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
}

//Kai


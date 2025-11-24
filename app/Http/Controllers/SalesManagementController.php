<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesManagementController extends Controller
{
        /**
     * Show details for a POS transaction (direct sale or billing payment not linked to visit)
     */
    public function showTransaction($id)
    {
        // Transaction ID format: SALE-<ord_id> or BILL-<bill_id>
        if (strpos($id, 'SALE-') === 0) {
            $ordId = str_replace('SALE-', '', $id);
            $orders = Order::with(['product', 'user', 'owner'])
                ->where('ord_id', $ordId)
                ->get();
            $transactionType = 'Direct Sale';
            $billId = null;
        } elseif (strpos($id, 'BILL-') === 0) {
            $billId = str_replace('BILL-', '', $id);
            $orders = Order::with(['product', 'user', 'owner'])
                ->where('bill_id', $billId)
                ->get();
            $transactionType = 'Billing Payment';
        } else {
            abort(404, 'Invalid transaction ID');
        }

        if ($orders->isEmpty()) {
            abort(404, 'Transaction not found');
        }

        // Render a simple transaction view (create resources/views/transaction-details.blade.php if needed)
        return view('transaction-details', compact('orders', 'transactionType', 'billId', 'id'));
    }

    /**
     * Export POS sales transactions as CSV
     */
    public function export(Request $request)
    {
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        // Build base query with branch filter
        $query = Order::with(['product', 'user', 'owner', 'billing'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            });

        // Apply date filters if provided
        if ($request->filled('start_date')) {
            $query->where('ord_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('ord_date', '<=', $request->input('end_date') . ' 23:59:59');
        }

        // Only export POS sales (direct sales + billing payments NOT linked to visits)
        // This matches the logic in the index method
        $allOrders = $query->orderBy('ord_date', 'desc')->get();
        
        // Filter out orders that belong to visit-based billings
        $orders = $allOrders->filter(function($order) {
            // Include if no bill_id (direct sale)
            if (!$order->bill_id) {
                return true;
            }
            // Include if bill exists but has no visit_id (POS billing payment)
            if ($order->billing && !$order->billing->visit_id) {
                return true;
            }
            // Exclude visit-based billings
            return false;
        });

        $filename = 'pos_sales_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\""
        ];

        $columns = ['Transaction ID', 'Date', 'Product', 'Quantity', 'Unit Price', 'Total', 'Customer', 'Cashier'];

        $callback = function() use ($orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($orders as $order) {
                $transactionId = $order->bill_id ? ('BILL-' . $order->bill_id) : ('SALE-' . $order->ord_id);
                $unitPrice = $order->product->prod_price ?? 0;
                $total = $order->ord_quantity * $unitPrice;
                
                fputcsv($file, [
                    $transactionId,
                    Carbon::parse($order->ord_date)->format('Y-m-d H:i:s'),
                    $order->product->prod_name ?? 'N/A',
                    $order->ord_quantity,
                    number_format($unitPrice, 2),
                    number_format($total, 2),
                    $order->owner->own_name ?? 'Walk-in Customer',
                    $order->user->user_name ?? 'N/A',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function __construct()
    {
        $this->middleware('auth');
    }   
    public function index(Request $request)
    {
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        // Filter billings by branch through visit -> user relationship
        // ONLY show bills that are linked to visits (visit_id is NOT NULL)
        // This ensures Billing Management tab only shows visit service bills
        $billings = Billing::with([
            'visit.pet.owner', 
            'visit.services',
            'visit.user.branch',
            'orders.product'
        ])
        ->whereNotNull('visit_id') // ✅ Filter to only visit-based bills
        ->where(function($query) use ($activeBranchId) {
            // Try to filter by visit.user.branch_id
            $query->whereHas('visit.user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            // Also include billings with branch_id directly set (if column exists)
            ->orWhere(function($q) use ($activeBranchId) {
                if (\Schema::hasColumn('tbl_bill', 'branch_id')) {
                    $q->where('branch_id', $activeBranchId);
                }
            });
        })
        ->orderBy('bill_date', 'desc')
        ->paginate(10);

        // Get date filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query with relationships and branch filter
        $query = Order::with(['product', 'user', 'owner', 'payment', 'billing'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            });
        
        // Apply date filters if provided
        if ($startDate) {
            $query->where('ord_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('ord_date', '<=', $endDate . ' 23:59:59');
        }
        
        // Get all orders
        $allOrders = $query->orderBy('ord_date', 'desc')->get();
        
        // Create transaction grouping with source tracking
        $transactions = [];
        $processedOrderIds = [];
        
        foreach ($allOrders as $order) {
            if (in_array($order->ord_id, $processedOrderIds)) {
                continue;
            }
            
            $transactionKey = null;
            $transactionSource = 'Direct Sale';
            
            // Check if this order is from a billing payment
            if ($order->bill_id) {
                // ✅ Skip if this bill is linked to a visit (visit-based bills shown in Billing Management tab)
                $billing = Billing::find($order->bill_id);
                if ($billing && $billing->visit_id) {
                    $processedOrderIds[] = $order->ord_id;
                    continue; // Skip this order, it's a visit-based billing
                }
                
                $transactionKey = 'BILL-' . $order->bill_id;
                $transactionSource = 'Billing Payment';
                
                // Get all orders with the same bill_id
                $relatedOrders = $allOrders->filter(function($o) use ($order, $processedOrderIds) {
                    return !in_array($o->ord_id, $processedOrderIds) && 
                           $o->bill_id && 
                           $o->bill_id === $order->bill_id;
                });
            } else {
                // Direct sale - group by timestamp (within 1 second) and user
                $transactionKey = 'SALE-' . $order->ord_id;
                $orderTime = Carbon::parse($order->ord_date);
                
                $relatedOrders = $allOrders->filter(function($o) use ($order, $orderTime, $processedOrderIds) {
                    if (in_array($o->ord_id, $processedOrderIds) || $o->bill_id) {
                        return false;
                    }
                    
                    $oTime = Carbon::parse($o->ord_date);
                    $timeDiff = abs($orderTime->diffInSeconds($oTime));
                    $sameUser = ($o->user_id ?? 0) === ($order->user_id ?? 0);
                    $sameCustomer = ($o->own_id ?? 0) === ($order->own_id ?? 0);
                    
                    return $timeDiff <= 1 && $sameUser && $sameCustomer;
                });
            }
            
            if ($relatedOrders->isNotEmpty()) {
                $transactions[$transactionKey] = [
                    'orders' => $relatedOrders,
                    'source' => $transactionSource,
                    'bill_id' => $order->bill_id
                ];
                
                foreach ($relatedOrders as $ro) {
                    $processedOrderIds[] = $ro->ord_id;
                }
            }
        }
        
        // Sort transactions by date (newest first)
        $transactions = collect($transactions)->sortByDesc(function($transaction) {
            return $transaction['orders']->first()->ord_date;
        });
        
        // Paginate transactions
        $page = $request->get('page', 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $totalTransactions = $transactions->count();
        $paginatedTransactions = $transactions->slice($offset, $perPage);
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedTransactions,
            $totalTransactions,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        
        // Calculate order totals
        $totalSales = $allOrders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        $totalItemsSold = $allOrders->sum('ord_quantity');
        $averageSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return view('orderBilling', compact(
            'billings',
            'paginatedTransactions', 
            'paginator',
            'totalSales',
            'totalTransactions', 
            'totalItemsSold',
            'averageSale'
        ));
    }

    public function generateBill(Visit $visit)
{
    if ($visit->service_status !== 'completed') {
        return back()->with('error', 'Cannot generate bill until all services are completed');
    }

    if ($visit->billing) {
        return redirect()->route('billings.show', $visit->billing->bill_id);
    }

    $billing = $visit->generateBilling();
    
    return redirect()->route('billings.show', $billing->bill_id)
        ->with('success', 'Billing generated successfully');
}
    public function markAsPaid(Request $request, $billId)
    {
        $validated = $request->validate([
            'cash_amount' => 'required|numeric|min:0.01', 
            'payment_type' => 'required|in:full,partial', 
        ]);
        
        $cash = (float) $validated['cash_amount'];
        $paymentType = $validated['payment_type'];

        // Eager load necessary relationships
        $billing = Billing::with([
            'visit.pet.owner', 
            'visit.services', 
            'visit.user', 
            'orders.product', 
            'payments',
            'orders' => function($query) {
                $query->where('source', 'Billing Add-on');
            }
        ])->findOrFail($billId);
        
        // Check product availability before processing payment
        foreach ($billing->orders as $order) {
            if ($order->product && $order->product->prod_stock < $order->ord_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for product: ' . $order->product->prod_name
                ], 422);
            }
        }
        
        DB::beginTransaction();
        
        try {
            $servicesTotal = (float) $billing->visit?->services->sum(function ($service) {
            // For services like Boarding, the quantity (days) and unit_price (daily rate) are
            // stored in the pivot table (tbl_visit_serv).
            $quantity = $service->pivot->quantity ?? 1;
            $unitPrice = $service->pivot->unit_price ?? $service->serv_price ?? 0;
            
            // Check if total_price exists in the pivot, which is often pre-calculated for services like boarding
            $totalPrice = $service->pivot->total_price ?? ($unitPrice * $quantity);

            return $totalPrice; // Use the calculated total price (Days * Rate)
        }) ?? 0;
            //$servicesTotal = (float) ($billing->visit?->services->sum('serv_price') ?? 0);

            // Calculate prescription total (Only from existing and available products in inventory)
            $prescriptionTotal = 0;
            $billablePrescriptions = [];
            
            if ($billing->visit && $billing->visit->pet) {
                $prescriptions = Prescription::where('pet_id', $billing->visit->pet->pet_id)
                    ->whereDate('prescription_date', '<=', $billing->bill_date)
                    ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                    ->get();
                
                foreach ($prescriptions as $prescription) {
                    $medications = json_decode($prescription->medication, true) ?? [];
                    $hasBillableItems = false;
                    $prescriptionTotalForThisPrescription = 0;
                    
                    foreach ($medications as $medication) {
                        if (isset($medication['product_id']) && $medication['product_id']) {
                            // First check if product exists and is active
                            $product = DB::table('tbl_prod')
                                ->where('prod_id', $medication['product_id'])
                                ->where('prod_status', 'active')
                                ->first();
                            
                            // If product doesn't exist or is not active, skip to next medication
                            if (!$product) {
                                continue;
                            }
                            
                            // Check if product is in stock
                            $inStock = $product->prod_stock > 0;
                            $hasPrice = $product->prod_price > 0;
                            
                            if ($inStock && $hasPrice) {
                                $prescriptionTotalForThisPrescription += (float) $product->prod_price;
                                $hasBillableItems = true;
                            }
                        }
                    }
                    
                    // Only add to total if at least one billable item exists
                    if ($hasBillableItems) {
                        $prescriptionTotal += $prescriptionTotalForThisPrescription;
                        $billablePrescriptions[] = $prescription->id;
                    }
                }
            }
            
            // Calculate add-on products total (kept for existing old bills)
            $addOnTotal = (float) $billing->orders->where('source', 'Billing Add-on')->sum('ord_total');
            
            // Total amount = services + prescriptions + add-ons
            $totalAmount = $servicesTotal + $prescriptionTotal + $addOnTotal;
            
            // Calculate total paid from existing payments
            $existingPaymentsTotal = (float) $billing->payments->sum('pay_total');
            
            // Calculate remaining balance before this payment
            $currentBalance = round($totalAmount - $existingPaymentsTotal, 2);
            
            // Validation
            if ($currentBalance <= 0.01) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This bill is already fully paid. No remaining balance.'
                ], 422);
            }

            if ($paymentType === 'partial' && $cash > $currentBalance) {
                // This scenario means the user is trying to overpay for a partial payment, or cash is just slightly over balance
                // If it's a full payment, we expect $cash >= $currentBalance, which is handled below.
                if (abs($cash - $currentBalance) > 0.01) { // Allow for tiny floating point errors
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Partial payment cannot exceed the remaining balance.'
                    ], 422);
                }
                // If cash is slightly over, we proceed as a full payment
            }
            
            // Calculate new payment amount (can't be more than remaining balance)
            $paymentAmount = min($cash, $currentBalance);
            $change = max(0, $cash - $currentBalance);
            
            // Calculate new total paid amount
            $newTotalPaid = round($existingPaymentsTotal + $paymentAmount, 2);
            $newBalance = round($totalAmount - $newTotalPaid, 2);
            
            // Determine if this payment completes the bill
            $isFullyPaid = ($newBalance <= 0.01);
            $billingStatus = $isFullyPaid ? 'paid' : 'partial'; // Changed 'pending' to 'partial' if $newTotalPaid > 0

            if ($newTotalPaid == 0) {
                $billingStatus = 'pending';
            }


            // Create payment record
            $transactionKey = 'PAY-' . $billId . '-' . time();
            
            $payment = Payment::create([
                'bill_id' => $billing->bill_id,
                'pay_total' => $paymentAmount,
                'pay_cashAmount' => $cash,
                'pay_change' => $change,
                'payment_type' => $paymentType,
                'payment_date' => now(),
                'transaction_id' => $transactionKey,
                'status' => $billingStatus
            ]);

            // CRITICAL FIX: Update billing record with new paid amount
            $billing->paid_amount = $newTotalPaid;
            $billing->bill_status = $billingStatus;
            $billing->save();

            // Update visit status to completed when billing is fully paid
            if ($isFullyPaid && $billing->visit) {
                $billing->visit->visit_status = 'completed';
                $billing->visit->save();
            }

            // Update associated orders if fully paid (only update those not already paid)
            if ($isFullyPaid) {
                // Update payment status for unpaid orders
                $unpaidOrders = Order::where('bill_id', $billing->bill_id)
                    ->where('payment_status', '!=', 'paid')
                    ->get();
                
                // Update inventory for each product in the order
                foreach ($unpaidOrders as $order) {
                    if ($order->product) {
                        // Decrease the product stock
                        $order->product->decrement('prod_stocks', $order->ord_quantity);
                    }
                }
                
                // Update payment status after inventory is successfully updated
                $unpaidOrders->each(function($order) use ($transactionKey) {
                    $order->update([
                        'payment_status' => 'paid',
                        'transaction_key' => $transactionKey
                    ]);
                });

                if ($billing->visit) {
                    $billing->visit->update(['visit_status' => 'completed']);
                }
            } else {
                // For partial payments, create an Order record to log the payment itself.
                // This ensures the payment transaction appears in the Orders tab correctly.
                Order::create([
                    'bill_id' => $billing->bill_id,
                    'prod_id' => null, // No product
                    'ord_quantity' => 1,
                    'ord_date' => now(),
                    'ord_total' => $paymentAmount, // The amount that actually covered the bill
                    'user_id' => auth()->id(),
                    'owner_id' => $billing->visit->pet->owner_id,
                    'source' => 'Billing Payment', // New source type
                    'transaction_key' => $transactionKey,
                    'payment_status' => 'paid' // The payment itself is a 'paid' transaction
                ]);
                
            }


            DB::commit();
            
            Log::info("Payment processed - Bill ID: {$billId}, New Paid Amount: {$newTotalPaid}, Balance: {$newBalance}, Status: {$billingStatus}");
            
           return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully!',
            'change' => $change,
            'final_status' => $billingStatus,
            'paid_amount' => $newTotalPaid,
            'total_amount' => $totalAmount, // This will now show the correct total
            'remaining_balance' => $newBalance
        ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment processing error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyBilling($billId) 
    {
        // Keep existing delete logic
        try {
            DB::beginTransaction();
            $billing = Billing::findOrFail($billId);

            // Cascade delete related records
            // Assuming relationships handle deletion, or manually clean up if required by schema
            $billing->orders()->delete();
            $billing->payments()->delete();
            // Note: Visit/Services/Prescriptions are usually not deleted, only detached/updated, 
            // but the provided logic does not touch them, so we proceed with deleting the billing record itself.

            $billing->delete();

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Billing record deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing deletion error: ' . $e->getMessage());
            return redirect()->route('sales.index')->with('error', 'Failed to delete billing record: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction details as JSON for modal display
     */
    public function showTransactionJson($id)
    {
        // Transaction ID format: SALE-<ord_id> or BILL-<bill_id>
        if (strpos($id, 'SALE-') === 0) {
            $ordId = str_replace('SALE-', '', $id);
            // Get the main order first
            $mainOrder = Order::with(['product', 'user', 'owner'])->find($ordId);
            
            if (!$mainOrder) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }
            
            // Find all related orders (same transaction - within 1 second, same user, same customer)
            $orderTime = Carbon::parse($mainOrder->ord_date);
            $orders = Order::with(['product', 'user', 'owner'])
                ->whereNull('bill_id') // Only direct sales
                ->where(function($query) use ($mainOrder, $orderTime) {
                    $query->where('user_id', $mainOrder->user_id)
                          ->where('own_id', $mainOrder->own_id)
                          ->whereBetween('ord_date', [
                              $orderTime->copy()->subSecond()->format('Y-m-d H:i:s'),
                              $orderTime->copy()->addSecond()->format('Y-m-d H:i:s')
                          ]);
                })
                ->get();
            
            $transactionType = 'Direct Sale';
        } elseif (strpos($id, 'BILL-') === 0) {
            $billId = str_replace('BILL-', '', $id);
            $orders = Order::with(['product', 'user', 'owner'])
                ->where('bill_id', $billId)
                ->get();
            $transactionType = 'Billing Payment';
        } else {
            return response()->json(['error' => 'Invalid transaction ID'], 404);
        }

        if ($orders->isEmpty()) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $firstOrder = $orders->first();
        $orderData = [];
        $total = 0;

        foreach ($orders as $order) {
            $unitPrice = $order->product->prod_price ?? 0;
            $itemTotal = $order->ord_quantity * $unitPrice;
            $total += $itemTotal;

            $orderData[] = [
                'product' => $order->product->prod_name ?? 'N/A',
                'quantity' => $order->ord_quantity,
                'unitPrice' => number_format($unitPrice, 2),
                'total' => number_format($itemTotal, 2)
            ];
        }

        // Get branch information
        $branch = auth()->user()->branch ?? \App\Models\Branch::first();
        
        return response()->json([
            'transactionType' => $transactionType,
            'date' => Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A'),
            'customer' => $firstOrder->owner->own_name ?? 'Walk-in Customer',
            'cashier' => $firstOrder->user->user_name ?? 'N/A',
            'total' => number_format($total, 2),
            'orders' => $orderData,
            'branch' => [
                'name' => $branch->branch_name ?? 'Main Branch',
                'address' => 'Address: ' . ($branch->branch_address ?? 'Branch Address'),
                'contact' => 'Contact No: ' . ($branch->branch_contactNum ?? 'Contact Number')
            ]
        ]);
    }
}
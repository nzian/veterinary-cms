<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    /**
     * Display the POS view with products and owners.
     */
    public function index(Request $request)
    {
        // 1. DETERMINE ACTIVE BRANCH CONTEXT
        $user = Auth::user();
        $activeBranchId = optional($user)->branch_id; 

        // Get all user IDs associated with the current user's branch
        $branchUserIds = [];
        if ($activeBranchId) {  
            $branchUserIds = User::where('branch_id', $activeBranchId)
                ->pluck('user_id')
                ->toArray();
        }
        
        // 2. FETCH AND FILTER OWNERS (Customers) 
        // Only show owners created by users within the current branch
        $ownersQuery = Owner::select('own_id', 'own_name')
            ->orderBy('own_name', 'asc');
            
        if (!empty($branchUserIds)) {
            $ownersQuery->whereIn('user_id', $branchUserIds);
        }
        
        $owners = $ownersQuery->get();
        
        // Add Walk-in Customer option to the filtered list
        $owners->prepend((object)[
            'own_id' => 0,
            'own_name' => 'Walk-in Customer',
        ]);

        // 3. FETCH AND FILTER PRODUCTS (Items)
        // Only show products marked as 'Sale' type for POS
        $itemsQuery = Product::select('prod_id', 'prod_name', 'prod_price', 'prod_stocks', 'prod_category')
            ->where('prod_stocks', '>', 0)
            ->where('prod_type', 'Sale')
            ->orderBy('prod_name', 'asc');

        if ($activeBranchId) {
            // Assuming Product model has a branch_id
            $itemsQuery->where('branch_id', $activeBranchId);
        }
        
        $items = $itemsQuery->get();

        Log::info("POS loaded: " . $items->count() . " products");

        return view('pos', compact('owners', 'items'));
    }

    /**
     * Process a direct product sale transaction.
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

            // Transaction Lock Implementation (Stronger prevention)
            $userId = Auth::id();
            $itemsHash = md5(json_encode($validated['items']));
            $userLockKey = "pos_user_{$userId}_lock";
            $transactionLockKey = "pos_txn_{$itemsHash}_{$userId}";

            // Check 1: User-level lock (prevent ANY transaction from this user for 3 seconds)
            if (Cache::has($userLockKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please wait, your previous transaction is still processing.'
                ], 429);
            }

            // Check 2: Transaction-specific lock (prevent THIS EXACT transaction)
            if (Cache::has($transactionLockKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction has already been submitted.'
                ], 429);
            }

            // Set BOTH locks immediately
            Cache::put($userLockKey, true, 3); // 3 second user lock
            Cache::put($transactionLockKey, true, 300); // 5 minute transaction lock (to prevent re-submission after error)

            DB::beginTransaction();

            try {
                // Check what columns exist in tbl_ord table (for compatibility)
                $orderColumns = DB::getSchemaBuilder()->getColumnListing('tbl_ord');

                // Check stock availability before proceeding
                foreach ($validated['items'] as $item) {
                    // Lock the product row for update within the transaction
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    
                    if (!$product) {
                        throw new \Exception("Product with ID {$item['product_id']} not found.");
                    }

                    if ($product->prod_stocks < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->prod_name}. Available: {$product->prod_stocks}");
                    }
                }
                
                $transactionTimestamp = now();
                $orderIds = [];
                
                // Process Sale (Create Orders and Deduct Stock)
                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    
                    // Build order data with only existing columns
                    $orderData = [
                        'ord_quantity' => $item['quantity'],
                        'prod_id' => $product->prod_id,
                    ];

                    // Check and add optional columns
                    if (in_array('ord_date', $orderColumns)) {
                        $orderData['ord_date'] = $transactionTimestamp;
                    }
                    if (in_array('ord_total', $orderColumns)) {
                        $orderData['ord_total'] = $item['price'] * $item['quantity'];
                    }
                    if (in_array('user_id', $orderColumns)) {
                        $orderData['user_id'] = $userId;
                    }
                    if (in_array('own_id', $orderColumns) && !empty($validated['owner_id']) && $validated['owner_id'] != 0) {
                        $orderData['own_id'] = $validated['owner_id'];
                    }
                    // Assuming default values for new columns if they exist
                    if (in_array('payment_method', $orderColumns)) {
                        $orderData['payment_method'] = 'Cash';
                    }
                    if (in_array('payment_status', $orderColumns)) {
                        $orderData['payment_status'] = 'Paid';
                    }
                    if (in_array('order_type', $orderColumns)) {
                        $orderData['order_type'] = 'Direct Sale';
                    }
                    if (in_array('bill_id', $orderColumns)) {
                        $orderData['bill_id'] = null;
                    }

                    $orderId = DB::table('tbl_ord')->insertGetId($orderData);
                    $orderIds[] = $orderId;

                    // Deduct stock
                    $product->prod_stocks -= $item['quantity'];
                    $product->save();
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sale completed successfully!',
                    'change' => $change,
                    'order_ids' => $orderIds,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                // Release user lock on error so they can retry another transaction
                Cache::forget($userLockKey);
                // Keep transaction lock to prevent retry of same transaction
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('POS Direct Sale Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Order;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesManagementController extends Controller
{
  public function index(Request $request)
{
    // Get billing data
    $billings = Billing::with([
        'appointment.pet.owner', 
        'appointment.services',
        'branch'
    ])
    ->orderBy('bill_date', 'desc')
    ->paginate(10);

    // Get order data with date filters
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    
    // Build query with relationships
    $query = Order::with(['product', 'user', 'owner', 'payment', 'billing']);
    
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
    
    // Group by timestamp (within 1 second) and user_id for direct sales
    // OR by bill_id for billing payments
    $processedOrderIds = [];
    
    foreach ($allOrders as $order) {
        if (in_array($order->ord_id, $processedOrderIds)) {
            continue;
        }
        
        $transactionKey = null;
        $transactionSource = 'Direct Sale'; // Default
        
        // Check if this order is from a billing payment
        if ($order->bill_id) {
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
            $orderTime = \Carbon\Carbon::parse($order->ord_date);
            
            $relatedOrders = $allOrders->filter(function($o) use ($order, $orderTime, $processedOrderIds) {
                if (in_array($o->ord_id, $processedOrderIds) || $o->bill_id) {
                    return false;
                }
                
                $oTime = \Carbon\Carbon::parse($o->ord_date);
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

    public function destroyBilling($id)
    {
        $billing = Billing::findOrFail($id);
        $billing->delete();
        return back()->with('success', 'Billing record deleted.');
    }

    public function markAsPaid($billId)
    {
        $billing = Billing::findOrFail($billId);
        $billing->bill_status = 'paid';
        $billing->save();

        return response()->json([
            'success' => true,
            'message' => 'Billing marked as paid',
            'billing_id' => $billId,
            'status' => 'paid'
        ]);
    }

    public function showTransaction($transactionId)
{
    // Check if this is a billing transaction
    if (str_starts_with($transactionId, 'BILL-')) {
        $billId = str_replace('BILL-', '', $transactionId);
        
        // Get all orders with this bill_id
        $orders = Order::with(['product', 'user', 'owner', 'payment', 'billing'])
            ->where('bill_id', $billId)
            ->orderBy('ord_date', 'desc')
            ->get();
        
        if ($orders->isEmpty()) {
            abort(404, 'Transaction not found');
        }
        
        $transactionTotal = $orders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        
        $totalItems = $orders->sum('ord_quantity');
        
        return view('order-detail', compact('orders', 'transactionId', 'transactionTotal', 'totalItems'));
    }
    
    // Handle SALE- transactions (direct sales)
    $firstOrderId = str_replace(['SALE-', 'T'], '', $transactionId);
    
    $firstOrder = Order::with(['product', 'user', 'owner', 'payment'])
        ->find($firstOrderId);
    
    if (!$firstOrder) {
        abort(404, 'Transaction not found');
    }
    
    // Use the EXACT same logic as in index() method
    $orderTime = Carbon::parse($firstOrder->ord_date);
    
    // Get all orders from the database to filter
    $allOrders = Order::with(['product', 'user', 'owner', 'payment'])
        ->whereNull('bill_id') // Only direct sales
        ->whereDate('ord_date', $orderTime->toDateString()) // Same day
        ->get();
    
    // Filter orders using the same logic as index()
    $orders = $allOrders->filter(function($order) use ($firstOrder, $orderTime) {
        $oTime = Carbon::parse($order->ord_date);
        $timeDiff = abs($orderTime->diffInSeconds($oTime));
        $sameUser = ($order->user_id ?? 0) === ($firstOrder->user_id ?? 0);
        $sameCustomer = ($order->own_id ?? 0) === ($firstOrder->own_id ?? 0);
        
        return $timeDiff <= 1 && $sameUser && $sameCustomer;
    });

    if ($orders->isEmpty()) {
        $orders = collect([$firstOrder]);
    }

    $transactionTotal = $orders->sum(function($order) {
        return $order->ord_quantity * ($order->product->prod_price ?? 0);
    });
    
    $totalItems = $orders->sum('ord_quantity');

    return view('order-detail', compact('orders', 'transactionId', 'transactionTotal', 'totalItems'));
}

    public function printTransaction($transactionId)
    {
        $firstOrderId = str_replace('T', '', $transactionId);
        
        $firstOrder = Order::with(['product', 'user', 'owner', 'payment'])
            ->find($firstOrderId);
        
        if (!$firstOrder) {
            abort(404, 'Transaction not found');
        }
        
        $orders = Order::with(['product', 'user', 'owner', 'payment'])
            ->where(function($query) use ($firstOrder) {
                $dateTime = Carbon::parse($firstOrder->ord_date)->format('Y-m-d H:i');
                $query->where('ord_date', 'like', $dateTime . '%');
                
                if ($firstOrder->user_id !== null) {
                    $query->where('user_id', $firstOrder->user_id);
                } else {
                    $query->whereNull('user_id');
                }
                
                if ($firstOrder->own_id !== null) {
                    $query->where('own_id', $firstOrder->own_id);
                } else {
                    $query->whereNull('own_id');
                }
            })
            ->orderBy('ord_date', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            $orders = collect([$firstOrder]);
        }

        $transactionTotal = $orders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        
        $totalItems = $orders->sum('ord_quantity');

        return view('receipt-print', compact('orders', 'transactionId', 'transactionTotal', 'totalItems'));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $orders = Order::with(['product', 'user', 'owner', 'payment'])
            ->whereBetween('ord_date', [$startDate, $endDate])
            ->orderBy('ord_date', 'desc')
            ->get();

        $filename = 'pos_sales_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Order ID',
                'Sale Date',
                'Product Name',
                'Quantity',
                'Unit Price',
                'Total Amount',
                'Customer',
                'Cashier',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->ord_id,
                    $order->ord_date,
                    $order->product->prod_name ?? 'N/A',
                    $order->ord_quantity,
                    $order->product->prod_price ?? 0,
                    ($order->product->prod_price ?? 0) * $order->ord_quantity,
                    $order->owner->own_name ?? 'Walk-in Customer',
                    $order->user->user_name ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

//Anurag, how are you?
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
        $query = Order::with(['product', 'user', 'owner', 'payment']);
        
        // Apply date filters if provided
        if ($startDate) {
            $query->where('ord_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('ord_date', '<=', $endDate . ' 23:59:59');
        }
        
        // Get all orders
        $allOrders = $query->orderBy('ord_date', 'desc')->get();
        
        // Create transaction grouping
        $transactions = [];
        $processedOrderIds = [];
        
        foreach ($allOrders as $order) {
            if (in_array($order->ord_id, $processedOrderIds)) {
                continue;
            }
            
            $transactionId = 'T' . $order->ord_id;
            $orderTime = Carbon::parse($order->ord_date);
            $startTime = $orderTime->copy()->subMinutes(2);
            $endTime = $orderTime->copy()->addMinutes(3);
            
            $similarOrders = $allOrders->filter(function($o) use ($order, $startTime, $endTime) {
                $oTime = Carbon::parse($o->ord_date);
                $withinTimeWindow = $oTime->between($startTime, $endTime);
                $sameUser = ($o->user_id ?? 0) === ($order->user_id ?? 0);
                $sameCustomer = ($o->own_id ?? 0) === ($order->own_id ?? 0);
                
                return $withinTimeWindow && $sameUser && $sameCustomer;
            });
            
            $transactions[$transactionId] = $similarOrders;
            
            foreach ($similarOrders as $so) {
                $processedOrderIds[] = $so->ord_id;
            }
        }
        
        // Paginate transactions
        $page = $request->get('page', 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $totalTransactions = count($transactions);
        $paginatedTransactions = collect($transactions)->slice($offset, $perPage);
        
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
        $firstOrderId = str_replace('T', '', $transactionId);
        
        $firstOrder = Order::with(['product', 'user', 'owner', 'payment'])
            ->find($firstOrderId);
        
        if (!$firstOrder) {
            abort(404, 'Transaction not found');
        }
        
        $startTime = Carbon::parse($firstOrder->ord_date)->subMinutes(2);
        $endTime = Carbon::parse($firstOrder->ord_date)->addMinutes(3);
        
        $orders = Order::with(['product', 'user', 'owner', 'payment'])
            ->whereBetween('ord_date', [$startTime, $endTime])
            ->where(function($query) use ($firstOrder) {
                if ($firstOrder->user_id !== null) {
                    $query->where('user_id', $firstOrder->user_id);
                } else {
                    $query->whereNull('user_id');
                }
            })
            ->where(function($query) use ($firstOrder) {
                if ($firstOrder->own_id !== null) {
                    $query->where('own_id', $firstOrder->own_id);
                } else {
                    $query->whereNull('own_id');
                }
            })
            ->orderBy('ord_date', 'desc')
            ->get();

        if ($orders->isEmpty() || !$orders->contains('ord_id', $firstOrderId)) {
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
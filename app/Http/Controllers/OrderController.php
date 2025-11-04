<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display all orders grouped by payment transaction
     */
    public function index(Request $request)
    {
        // Get date filters if any
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
        
        // Create a simple transaction grouping with broader time window
        $transactions = [];
        $processedOrderIds = [];
        
        foreach ($allOrders as $order) {
            if (in_array($order->ord_id, $processedOrderIds)) {
                continue;
            }
            
            // Create a transaction identifier using the first order ID
            $transactionId = 'T' . $order->ord_id;
            
            // Find similar orders within a 5-minute window (same user, same customer)
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
            
            // Mark these orders as processed
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
        
        // Create pagination object
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
        
        // Calculate totals
        $totalSales = $allOrders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        $totalItemsSold = $allOrders->sum('ord_quantity');
        $averageSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        
        return view('order', compact(
            'paginatedTransactions', 
            'paginator',
            'totalSales',
            'totalTransactions', 
            'totalItemsSold',
            'averageSale'
        ));
    }

    /**
     * Show transaction details - using the transaction ID format T{orderId}
     */
    public function show($transactionId)
    {
        // Extract the order ID from transaction ID (remove 'T' prefix)
        $firstOrderId = str_replace('T', '', $transactionId);
        
        // Debug: Log what we're looking for
        \Log::info("Looking for transaction: " . $transactionId . " (Order ID: " . $firstOrderId . ")");
        
        // Get the first order
        $firstOrder = Order::with(['product', 'user', 'owner', 'payment'])
            ->find($firstOrderId);
        
        if (!$firstOrder) {
            \Log::error("First order not found: " . $firstOrderId);
            abort(404, 'Transaction not found - Order ID: ' . $firstOrderId . ' does not exist');
        }
        
        \Log::info("First order found: " . $firstOrder->ord_id . " Date: " . $firstOrder->ord_date);
        
        // Find all orders in the same transaction group
        $dateTime = Carbon::parse($firstOrder->ord_date)->format('Y-m-d H:i');
        \Log::info("Searching for orders with datetime: " . $dateTime);
        \Log::info("First order user_id: " . ($firstOrder->user_id ?? 'NULL'));
        \Log::info("First order own_id: " . ($firstOrder->own_id ?? 'NULL'));
        
        // Use a broader search to find all related orders
        // Search within a 5-minute window to catch orders that might be a few seconds apart
        $startTime = Carbon::parse($firstOrder->ord_date)->subMinutes(2);
        $endTime = Carbon::parse($firstOrder->ord_date)->addMinutes(3);
        
        $orders = Order::with(['product', 'user', 'owner', 'payment'])
            ->whereBetween('ord_date', [$startTime, $endTime])
            ->where(function($query) use ($firstOrder) {
                // Handle user_id - could be null
                if ($firstOrder->user_id !== null) {
                    $query->where('user_id', $firstOrder->user_id);
                } else {
                    $query->whereNull('user_id');
                }
            })
            ->where(function($query) use ($firstOrder) {
                // Handle own_id - could be null  
                if ($firstOrder->own_id !== null) {
                    $query->where('own_id', $firstOrder->own_id);
                } else {
                    $query->whereNull('own_id');
                }
            })
            ->orderBy('ord_date', 'desc')
            ->get();

        \Log::info("Found " . $orders->count() . " orders in transaction group");

        // ALWAYS ensure we have at least the first order
        if ($orders->isEmpty() || !$orders->contains('ord_id', $firstOrderId)) {
            \Log::warning("Adding first order to collection as fallback");
            $orders = collect([$firstOrder]);
        }

        // Calculate totals
        $transactionTotal = $orders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        
        $totalItems = $orders->sum('ord_quantity');

        \Log::info("Transaction total: " . $transactionTotal . " Total items: " . $totalItems);

        return view('order-detail', compact('orders', 'transactionId', 'transactionTotal', 'totalItems'));
    }

    /**
     * Show individual order details
     */
    public function showOrder($orderId)
    {
        $order = Order::with(['product', 'user', 'owner', 'payment'])
            ->findOrFail($orderId);

        return view('single-order-detail', compact('order'));
    }

    /**
     * Print receipt
     */
    public function printReceipt($transactionId)
    {
        // Extract the order ID from transaction ID
        $firstOrderId = str_replace('T', '', $transactionId);
        
        // Get the first order
        $firstOrder = Order::with(['product', 'user', 'owner', 'payment'])
            ->find($firstOrderId);
        
        if (!$firstOrder) {
            abort(404, 'Transaction not found');
        }
        
        // Find all orders in the same transaction group
        $orders = Order::with(['product', 'user', 'owner', 'payment'])
            ->where(function($query) use ($firstOrder) {
                $dateTime = Carbon::parse($firstOrder->ord_date)->format('Y-m-d H:i');
                $query->where('ord_date', 'like', $dateTime . '%');
                
                // Handle user_id - could be null
                if ($firstOrder->user_id !== null) {
                    $query->where('user_id', $firstOrder->user_id);
                } else {
                    $query->whereNull('user_id');
                }
                
                // Handle own_id - could be null  
                if ($firstOrder->own_id !== null) {
                    $query->where('own_id', $firstOrder->own_id);
                } else {
                    $query->whereNull('own_id');
                }
            })
            ->orderBy('ord_date', 'desc')
            ->get();

        // Double check we have orders
        if ($orders->isEmpty()) {
            // Fallback: just return the first order as a collection
            $orders = collect([$firstOrder]);
        }

        // Calculate totals
        $transactionTotal = $orders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        
        $totalItems = $orders->sum('ord_quantity');

        return view('receipt-print', compact('orders', 'transactionId', 'transactionTotal', 'totalItems'));
    }

    /**
     * Sales summary grouped by product
     */
    public function salesSummary(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $summary = Order::with('product')
            ->whereBetween('ord_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($order) {
                return $order->product->prod_name ?? 'Unknown Product';
            })
            ->map(function ($group) {
                return [
                    'product_name' => $group->first()->product->prod_name ?? 'Unknown Product',
                    'total_quantity' => $group->sum('ord_quantity'),
                    'total_sales' => $group->sum(function ($order) {
                        return $order->ord_quantity * ($order->product->prod_price ?? 0);
                    }),
                    'transaction_count' => $group->count(),
                ];
            });

        return response()->json($summary);
    }

    /**
     * Get daily sales data for charts
     */
    public function dailySales(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $sales = Order::with('product')
            ->where('ord_date', '>=', $startDate)
            ->get()
            ->groupBy(function ($order) {
                return Carbon::parse($order->ord_date)->format('Y-m-d');
            })
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'total_sales' => $group->sum(function ($order) {
                        return $order->ord_quantity * ($order->product->prod_price ?? 0);
                    }),
                    'transaction_count' => $group->count(),
                    'items_sold' => $group->sum('ord_quantity'),
                ];
            })
            ->sortBy('date')
            ->values();

        return response()->json($sales);
    }

    /**
     * Export POS sales
     */
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

            // CSV Headers
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

    /**
     * Get top selling products
     */
    public function topProducts(Request $request)
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $topProducts = Order::with('product')
            ->where('ord_date', '>=', $startDate)
            ->select('prod_id', DB::raw('SUM(ord_quantity) as total_sold'), DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('prod_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'product_name' => $item->product->prod_name ?? 'N/A',
                    'total_sold' => $item->total_sold,
                    'transaction_count' => $item->transaction_count,
                    'revenue' => $item->total_sold * ($item->product->prod_price ?? 0),
                ];
            });

        return response()->json($topProducts);
    }

    



}
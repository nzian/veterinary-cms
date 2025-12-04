<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesManagementController extends Controller
{

    /**
     * Show grouped receipt for all pets of an owner for a billing date
     */
    public function showGroupedReceipt($owner_id, $bill_date)
    {
        // Get all billings for this owner and date
        $petBillings = \App\Models\Billing::with([
            'visit.pet',
            'visit.services',
            'orders.product',
            'visit.user.branch',
        ])->where('owner_id', $owner_id)
          ->whereDate('bill_date', $bill_date)
          ->get();

        if ($petBillings->isEmpty()) {
            abort(404, 'No billings found for this owner and date.');
        }
        //dd($petBillings);
        $owner = $petBillings->first()->visit->pet->owner ?? $petBillings->first()->owner;
        $branch = $petBillings->first()->visit->user->branch ?? \App\Models\Branch::first();
        $totalAmount = $petBillings->sum('total_amount');
        $paidAmount = $petBillings->sum('paid_amount');
        $balance = $totalAmount - $paidAmount;
        $billDate = $bill_date;

        // Gather all services and prescriptions for all pets
        $services = [];
        $prescriptions = [];
        $products = collect();
        foreach ($petBillings as $billing) {
            $petName = $billing->visit->pet->pet_name ?? 'Pet';
            // Services
            if ($billing->visit && $billing->visit->services) {
                foreach ($billing->visit->services as $service) {
                    // Prefer pivot total_price, fallback to unit_price, then serv_price
                    $price = 0;
                    if (isset($service->pivot->total_price) && $service->pivot->total_price > 0) {
                        $price = $service->pivot->total_price;
                    } elseif (isset($service->pivot->unit_price) && $service->pivot->unit_price > 0) {
                        $price = $service->pivot->unit_price * ($service->pivot->quantity ?? 1);
                    } elseif (isset($service->serv_price) && $service->serv_price > 0) {
                        $price = $service->serv_price;
                    }
                    $services[] = [
                        'pet' => $petName,
                        'name' => $service->serv_name,
                        'price' => $price,
                    ];
                }
            }
            $prescriptions = [];
            // Prescriptions
            if($billing->visit == null){
                $billing->load('owner.pets');
                //dd($billing->owner->pets);
                if($billing->owner && $billing->owner->pets->count() > 0){
                    $pet_id = $billing->owner->pets->first()->pet_id;
                    $petName = $pet->pet_name ?? 'Pet';
                }   
            }
            elseif($billing->visit->pet){
                $petName = $billing->visit->pet->pet_name ?? 'Pet';
                $pet_id = $billing->visit->pet->pet_id;
            }
            if(!$billing->visit != null){
                $prescModels = \App\Models\Prescription::where('pet_id', $pet_id)
                ->where('pres_visit_id', $billing->visit_id)
                ->whereDate('prescription_date', $billing->bill_date)
                ->get();
            foreach ($prescModels as $presc) {
                $medications = json_decode($presc->medication, true) ?? [];
                foreach ($medications as $med) {
                    // Try to get price from multiple possible keys
                    $medPrice = 0;
                    if (isset($med['price']) && $med['price'] > 0) {
                        $medPrice = $med['price'];
                    } elseif (isset($med['prod_price']) && $med['prod_price'] > 0) {
                        $medPrice = $med['prod_price'];
                    } elseif (isset($med['unit_price']) && $med['unit_price'] > 0) {
                        $qty = $med['qty'] ?? $med['quantity'] ?? 1;
                        $medPrice = $med['unit_price'] * $qty;
                    }
                    $prescriptions[] = [
                        'pet' => $petName,
                        'name' => $med['product_name'] ?? $med['name'] ?? 'Medication',
                        'price' => $medPrice,
                    ];
                }
            }
        }
           
            // Products
            if ($billing->orders && $billing->orders->count() > 0) {
                foreach ($billing->orders as $order) {
                    $prodPrice = 0;
                    if (isset($order->ord_price) && $order->ord_price > 0) {
                        $prodPrice = $order->ord_price;
                    } elseif (isset($order->product->prod_price) && $order->product->prod_price > 0) {
                        $prodPrice = $order->product->prod_price;
                    }
                    /*$products->push([
                        'name' => $order->product->prod_name ?? 'Product',
                        'qty' => $order->ord_quantity,
                        'subtotal' => $order->ord_quantity * $prodPrice,
                    ]);*/
                }
            }
        }

        return view('grouped-billing-receipt', compact(
            'owner', 'billDate', 'petBillings', 'branch', 'totalAmount', 'paidAmount', 'balance', 'services', 'prescriptions', 'products'
        ));
    }
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

    protected $groupedBillingService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->groupedBillingService = new \App\Services\GroupedBillingService();
    }   
    public function index(Request $request)
    {
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        $isInBranchMode = session('branch_mode') === 'active';
        
        // Determine if we should show ALL branches (Super Admin in global mode)
        $showAllBranches = ($user->user_role === 'superadmin' && !$isInBranchMode);
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        // Get all billings with visits - show all branches if Super Admin in global mode
        $billingQuery = Billing::with([
            'visit.pet.owner' => function($query) {
                // Remove global scope to load owner regardless of branch
                $query->withoutGlobalScope('branch_owner_scope');
            }, 
            'visit.services',
            'visit.user.branch',
            'orders.product',
            'owner' => function($query) {
                // Remove global scope to load owner regardless of branch
                $query->withoutGlobalScope('branch_owner_scope');
            }
        ])
        ->whereNotNull('visit_id');
        
        // Only apply branch filter if NOT showing all branches
        if (!$showAllBranches) {
            $billingQuery->where(function($query) use ($activeBranchId) {
                $query->whereHas('visit.user', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                })
                ->orWhere(function($q) use ($activeBranchId) {
                    if (\Schema::hasColumn('tbl_bill', 'branch_id')) {
                        $q->where('branch_id', $activeBranchId);
                    }
                });
            });
        }
        
        $allBillings = $billingQuery->orderBy('bill_date', 'desc')->get();

        // Group billings by owner and date
        $groupedBillings = $allBillings->groupBy(function($billing) {
            // Safely get owner ID with null checks
            $ownerId = $billing->owner_id 
                ?? ($billing->visit && $billing->visit->pet && $billing->visit->pet->owner 
                    ? $billing->visit->pet->owner->own_id 
                    : 'unknown');
            $date = $billing->bill_date;
            return $ownerId . '_' . $date;
        })->map(function($group) {
            $firstBilling = $group->first();
            
            // Safely get owner with null checks
            $owner = $firstBilling->owner 
                ?? ($firstBilling->visit && $firstBilling->visit->pet 
                    ? $firstBilling->visit->pet->owner 
                    : null);

            // Only include boarding services for boarding bills
            $boardingBillings = $group->filter(function($billing) {
                if (!$billing->visit) return false;
                // Check if any service is boarding
                return $billing->visit->services->contains(function($service) {
                    return strtolower($service->serv_type ?? '') === 'boarding';
                });
            });

            if ($boardingBillings->isNotEmpty()) {
                // Calculate total for boarding only
                $totalAmount = 0;
                foreach ($boardingBillings as $billing) {
                    foreach ($billing->visit->services as $service) {
                        if (strtolower($service->serv_type ?? '') === 'boarding') {
                            $days = $service->pivot->quantity ?? 1; // quantity = total days
                            $unitPrice = $service->pivot->unit_price ?? $service->serv_price ?? 0;
                            $totalAmount += $unitPrice * $days;
                        }
                    }
                }
                $paidAmount = $boardingBillings->sum('paid_amount');
                $balance = round($totalAmount - $paidAmount, 2);
            } else {
                // Fallback: sum all services as before
                
                $totalAmount = $group->sum('total_amount');
                if($totalAmount <= 0){
                    // recalculate from visit services
                    $totalAmount = 0;
                    foreach($group as $billing){
                        if($billing->visit && $billing->visit->services){
                            foreach($billing->visit->services as $service){
                                $price = 0;
                                if (isset($service->pivot->total_price) && $service->pivot->total_price > 0) {
                                    $price = $service->pivot->total_price;
                                } elseif (isset($service->pivot->unit_price) && $service->pivot->unit_price > 0) {
                                    $price = $service->pivot->unit_price * ($service->pivot->quantity ?? 1);
                                } elseif (isset($service->serv_price) && $service->serv_price > 0) {
                                    $price = $service->serv_price;
                                }
                                $totalAmount += $price;
                            }
                        }
                    }
                }
                
                // Add prescription totals for each billing in the group
                $prescriptionTotal = 0;
                foreach($group as $billing) {
                    if($billing->visit) {
                        // Get prescriptions by visit_id first
                        $prescriptions = \App\Models\Prescription::where('pres_visit_id', $billing->visit->visit_id)->get();
                        // Fallback to pet_id + date if no prescriptions found
                        if ($prescriptions->isEmpty() && $billing->visit->pet_id) {
                            $prescriptions = \App\Models\Prescription::where('pet_id', $billing->visit->pet_id)
                                ->whereDate('prescription_date', $billing->visit->visit_date)
                                ->get();
                        }
                        foreach($prescriptions as $prescription) {
                            $medications = json_decode($prescription->medication, true) ?? [];
                            foreach($medications as $med) {
                                // Try to get price from multiple possible keys
                                $medPrice = 0;
                                if (isset($med['price']) && $med['price'] > 0) {
                                    $medPrice = (float) $med['price'];
                                } elseif (isset($med['unit_price']) && $med['unit_price'] > 0) {
                                    $qty = $med['quantity'] ?? $med['qty'] ?? 1;
                                    $medPrice = (float) $med['unit_price'] * $qty;
                                } elseif (isset($med['prod_price']) && $med['prod_price'] > 0) {
                                    $qty = $med['quantity'] ?? $med['qty'] ?? 1;
                                    $medPrice = (float) $med['prod_price'] * $qty;
                                }
                                $prescriptionTotal += $medPrice;
                            }
                        }
                    }
                }
                $totalAmount += $prescriptionTotal;
                
                $paidAmount = $group->sum('paid_amount');
                $balance = round($totalAmount - $paidAmount, 2);
            }

            // Status: determine group-level status. If all paid => 'paid'.
            // If any billing in the group has 'paid 50%', mark group as 'paid 50%'. Otherwise 'unpaid'.
            $allPaid = $group->every(function($b) use ($totalAmount) { return strtolower($b->bill_status ?? '') === 'paid' || (float)$totalAmount - (float)$b->paid_amount <= 0.01; });
            $anyPaid50 = $group->contains(function($b) { return strtolower($b->bill_status ?? '') === 'paid 50%'; });

            if ($allPaid && $totalAmount > 0) {
                $status = 'paid';
            } elseif ($anyPaid50) {
                $status = 'paid 50%';
            } else {
                $status = 'unpaid';
            }

            return [
                'owner' => $owner,
                'owner_id' => $owner ? $owner->own_id : null,
                'bill_date' => $firstBilling->bill_date,
                'billings' => $group,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance' => $balance,
                'status' => $status,
                'pet_count' => $group->count(),
                'billing_group_id' => $firstBilling->billing_group_id ?? 'SINGLE_' . $firstBilling->bill_id,
            ];
        })->sortByDesc('bill_date')->values();
        //dd($groupedBillings);
        
        // Return all grouped billings for client-side filtering
        $billings = $groupedBillings;

        // Get date filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query with relationships - apply branch filter for non-superadmin or when not in global mode
        $query = Order::with(['product', 'user', 'owner', 'payment', 'billing'])
            ->whereNull('bill_id') // Only direct POS sales
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            });

        // Apply branch filter (unless Super Admin in global mode)
        if (!$showAllBranches) {
            $query->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            });
        }

        // Apply date filters if provided
        if ($startDate) {
            $query->where('ord_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('ord_date', '<=', $endDate . ' 23:59:59');
        }

        // Get all direct POS orders for the active branch
        $allOrders = $query->orderBy('ord_date', 'desc')->get();

        // Group direct POS transactions by time, user, and customer
        $transactions = [];
        $processedOrderIds = [];
        foreach ($allOrders as $order) {
            if (in_array($order->ord_id, $processedOrderIds)) {
                continue;
            }
            $orderTime = \Carbon\Carbon::parse($order->ord_date);
            $transactionKey = 'SALE-' . $order->ord_id;
            $transactionSource = 'Direct Sale';
            $relatedOrders = $allOrders->filter(function($o) use ($order, $orderTime, $processedOrderIds) {
                if (in_array($o->ord_id, $processedOrderIds)) {
                    return false;
                }
                $oTime = \Carbon\Carbon::parse($o->ord_date);
                $timeDiff = abs($orderTime->diffInSeconds($oTime));
                $sameUser = ($o->user_id ?? 0) === ($order->user_id ?? 0);
                $sameCustomer = ($o->own_id ?? 0) === ($order->own_id ?? 0);
                return $timeDiff <= 1 && $sameUser && $sameCustomer;
            });
            if ($relatedOrders->isNotEmpty()) {
                $transactions[$transactionKey] = [
                    'orders' => $relatedOrders,
                    'source' => $transactionSource,
                    'bill_id' => null
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
        
        // Return all transactions for client-side filtering
        $paginatedTransactions = $transactions;
        $paginator = null;
        $totalTransactions = $transactions->count();
        
        // Calculate order totals (only paid orders)
        $paidOrders = $allOrders->filter(fn($order) => strtolower($order->payment_status ?? '') === 'paid');
        $totalSales = $paidOrders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        $totalItemsSold = $paidOrders->sum('ord_quantity');
        $averageSale = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        
        // Calculate revenue metrics from billings
        $totalBillingRevenue = $billings->sum('total_amount');
        $paidBillingRevenue = $billings->sum('paid_amount');
        $unpaidBillingBalance = $billings->sum('balance');
        $paidBillings = $billings->filter(fn($b) => strtolower($b['status'] ?? '') === 'paid')->count();
        $unpaidBillings = $billings->filter(fn($b) => strtolower($b['status'] ?? '') !== 'paid')->count();
        
        // Combined revenue (POS + Paid Billings)
        $totalRevenue = $totalSales + $paidBillingRevenue;
        
        // Calculate Daily Revenue (Today's POS Sales + Today's Paid Billing Payments)
        $today = \Carbon\Carbon::today();
        
        // Today's POS sales (paid only)
        $dailyPosSales = Order::whereDate('ord_date', $today)
            ->whereNull('bill_id')
            ->where('payment_status', 'paid')
            ->when(!$showAllBranches, function($q) use ($activeBranchId) {
                $q->whereHas('user', function($qu) use ($activeBranchId) {
                    $qu->where('branch_id', $activeBranchId);
                });
            })
            ->sum('ord_total');
        
        // Today's paid billing payments (actual payments from tbl_pay)
        $dailyBillingPayments = \App\Models\Payment::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->when(!$showAllBranches, function($q) use ($activeBranchId) {
                $q->whereHas('billing', function($qu) use ($activeBranchId) {
                    $qu->where('branch_id', $activeBranchId);
                });
            })
            ->sum('pay_total');
        
        $dailyRevenue = $dailyPosSales + $dailyBillingPayments;
        
        //dd($totalSales);
        return view('orderBilling', compact(
            'billings',
            'paginatedTransactions', 
            'paginator',
            'totalSales',
            'totalTransactions', 
            'totalItemsSold',
            'averageSale',
            'totalBillingRevenue',
            'paidBillingRevenue',
            'unpaidBillingBalance',
            'paidBillings',
            'unpaidBillings',
            'totalRevenue',
            'dailyRevenue'
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

    try {
        $billing = $this->groupedBillingService->generateSingleBilling($visit->visit_id);
        
        return redirect()->route('sales.index')
            ->with('success', 'Billing generated successfully');
    } catch (\Exception $e) {
        return back()->with('error', 'Failed to generate billing: ' . $e->getMessage());
    }
}

    /**
     * Auto-generate grouped billings for completed visits by owner
     */
    public function autoGenerateGroupedBillings(Request $request)
    {
        try {
            $activeBranchId = session('active_branch_id') ?? auth()->user()->branch_id;
            $today = Carbon::today()->toDateString();
            
            // Get all completed visits for TODAY that don't have billing yet
            $visits = Visit::with(['pet.owner', 'billing'])
                ->whereDate('visit_date', $today)
                ->where('workflow_status', 'Completed')
                ->whereHas('user', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                })
                ->get();
            
            // Filter out visits that already have billing
            $visitsWithoutBilling = $visits->filter(function($visit) {
                return $visit->billing === null;
            });
            
            if ($visitsWithoutBilling->isEmpty()) {
                return redirect()->route('sales.index')
                    ->with('info', 'No completed visits found without billing for today');
            }
            
            // Group visits by owner
            $groupedByOwner = $visitsWithoutBilling->groupBy(function($visit) {
                return $visit->pet->owner->own_id;
            });
            
            $generatedCount = 0;
            $errors = [];
            
            foreach ($groupedByOwner as $ownerId => $ownerVisits) {
                try {
                    if ($ownerVisits->count() === 1) {
                        $this->groupedBillingService->generateSingleBilling($ownerVisits->first()->visit_id);
                    } else {
                        $this->groupedBillingService->generateGroupedBilling($ownerVisits->pluck('visit_id')->toArray());
                    }
                    $generatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Owner ID {$ownerId}: " . $e->getMessage();
                }
            }
            
            if ($generatedCount > 0) {
                $message = "Successfully generated {$generatedCount} billing(s) for today's visits";
                if (!empty($errors)) {
                    $message .= " with some errors: " . implode('; ', $errors);
                }
                return redirect()->route('sales.index')->with('success', $message);
            } else {
                return redirect()->route('sales.index')
                    ->with('error', 'Failed to generate billings: ' . implode('; ', $errors));
            }
            
        } catch (\Exception $e) {
            return redirect()->route('sales.index')
                ->with('error', 'Auto-generation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate grouped billing for specific visits
     */
    public function generateGroupedBilling(Request $request)
    {
        $validated = $request->validate([
            'visit_ids' => 'required|array',
            'visit_ids.*' => 'exists:tbl_visit_record,visit_id'
        ]);
        
        try {
            $billing = $this->groupedBillingService->generateGroupedBilling($validated['visit_ids']);
            
            return redirect()->route('sales.index')
                ->with('success', 'Grouped billing generated successfully for ' . count($validated['visit_ids']) . ' visits');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate grouped billing: ' . $e->getMessage());
        }
    }
    
    /**
     * Show single billing details (for viewing individual pet billing)
     */
    public function showBilling($id)
    {
        try {
            $billing = Billing::with(['visit.pet.owner', 'visit.services', 'orders'])->findOrFail($id);
            
            // Calculate services
            $services = $billing->visit->services->map(function($service) {
                $quantity = $service->pivot->quantity ?? 1;
                $unitPrice = $service->pivot->unit_price ?? $service->serv_price;
                return $service->serv_name . ' (x' . $quantity . ')';
            })->implode(', ');
            
            $totalAmount = (float) $billing->total_amount;
            if($totalAmount <= 0){
                // recalculate from visit services
                $totalAmount = 0;
                if($billing->visit && $billing->visit->services){
                    foreach($billing->visit->services as $service){
                        $price = 0;
                        if (isset($service->pivot->total_price) && $service->pivot->total_price > 0) {
                            $price = $service->pivot->total_price;
                        } elseif (isset($service->pivot->unit_price) && $service->pivot->unit_price > 0) {
                            $price = $service->pivot->unit_price * ($service->pivot->quantity ?? 1);
                        } elseif (isset($service->serv_price) && $service->serv_price > 0) {
                            $price = $service->serv_price;
                        }
                        $totalAmount += $price;
                    }
                }
            }
            $paidAmount = (float) $billing->paid_amount;
            $balance = round($totalAmount - $paidAmount, 2);
            
            return response()->json([
                'success' => true,
                'billing' => [
                    'bill_id' => $billing->bill_id,
                    'pet_name' => $billing->visit->pet->pet_name ?? 'N/A',
                    'owner_name' => $billing->visit->pet->owner->own_name ?? 'N/A',
                    'bill_date' => \Carbon\Carbon::parse($billing->bill_date)->format('M d, Y'),
                    'services' => $services ?: 'No services',
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'Paid' : 'Unpaid'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load billing details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mark grouped billing as paid (payment for all pets of an owner)
     */
    public function markGroupAsPaid(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:tbl_own,own_id',
            'bill_date' => 'required|date',
            'cash_amount' => 'required|numeric|min:0.01',
            'payment_type' => 'nullable|in:full,partial'
        ]);
        
        $cash = (float) $validated['cash_amount'];
        $paymentType = $validated['payment_type'] ?? 'full';
        $ownerId = $validated['owner_id'];
        $billDate = $validated['bill_date'];

        DB::beginTransaction();
        
        try {
            // Get all billings for this owner on this date
            $billings = Billing::with(['visit.pet.owner', 'visit.services', 'visit.user', 'payments'])
                ->whereNotNull('visit_id')
                ->where('owner_id', $ownerId)
                ->where('bill_date', $billDate)
                ->get();

            //dd($billings);
            
            if ($billings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No billings found for this owner on this date'
                ], 404);
            }
            
            // Calculate total amount for all billings (all pets)
            $grandTotal = 0;
            $totalPaidSoFar = 0;
            
            foreach ($billings as $billing) {
                if((float) $billing->total_amount <= 0){
                    // recalculate from visit services
                    $recalculatedTotal = 0;
                    if($billing->visit && $billing->visit->services){
                        foreach($billing->visit->services as $service){
                            $price = 0;
                            if (isset($service->pivot->total_price) && $service->pivot->total_price > 0) {
                                $price = $service->pivot->total_price;
                            } elseif (isset($service->pivot->unit_price) && $service->pivot->unit_price > 0) {
                                $price = $service->pivot->unit_price * ($service->pivot->quantity ?? 1);
                            } elseif (isset($service->serv_price) && $service->serv_price > 0) {
                                $price = $service->serv_price;
                            }
                            $recalculatedTotal += $price;
                        }
                    }
                    $billing->total_amount = $recalculatedTotal;
                    $billing->save();
                }
                $grandTotal += (float) $billing->total_amount;
                $totalPaidSoFar += (float) $billing->paid_amount;
            }
            
            $currentBalance = round($grandTotal - $totalPaidSoFar, 2);
            
            // Validation
            if ($currentBalance <= 0.01) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'All bills for this owner are already fully paid'
                ], 422);
            }

            // If requesting a partial payment, require the partial amount (50% of group total)
            if ($paymentType === 'partial') {
                $requiredPartial = round($grandTotal * 0.5, 2);
                if ($cash < $requiredPartial) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Cash amount must be at least ₱' . number_format($requiredPartial, 2)
                    ], 422);
                }

                // Process group partial: apply per-billing partial (50%) and mark placeholders/partial payments as paid
                $transactionKey = 'PAY-GROUP-PARTIAL-' . $ownerId . '-' . time();
                $remainingCash = $cash;
                $appliedDetails = [];

                foreach ($billings as $idx => $billing) {
                    $thisPartial = round((float) $billing->total_amount * 0.5, 2);
                    if ($thisPartial <= 0) {
                        continue;
                    }

                    $placeholder = $billing->payments()->where('payment_type', 'partial')->where('status', 'pending')->first();

                    // Determine cash allocation for this billing (assign full partial amount sequentially)
                    $allocatedCash = min($thisPartial, $remainingCash);

                    if ($placeholder) {
                        $placeholder->pay_total = $thisPartial;
                        $placeholder->pay_cashAmount = $idx === 0 ? $cash : ($placeholder->pay_cashAmount ?? 0);
                        $placeholder->pay_change = $idx === 0 ? max(0, $cash - $requiredPartial) : ($placeholder->pay_change ?? 0);
                        $placeholder->payment_date = now();
                        $placeholder->transaction_id = $transactionKey;
                        // If we cover the full partial amount for this bill, mark paid
                        if (abs($allocatedCash - $thisPartial) <= 0.01) {
                            $placeholder->status = 'paid';
                        } else {
                            $placeholder->status = 'partial';
                            $placeholder->pay_total = $allocatedCash;
                        }
                        $placeholder->save();

                        $applied = $allocatedCash;
                    } else {
                        // Create a paid partial payment record for this billing
                        $created = Payment::create([
                            'bill_id' => $billing->bill_id,
                            'pay_total' => $allocatedCash,
                            'pay_cashAmount' => $idx === 0 ? $cash : 0,
                            'pay_change' => $idx === 0 ? max(0, $cash - $requiredPartial) : 0,
                            'payment_type' => 'partial',
                            'payment_date' => now(),
                            'transaction_id' => $transactionKey,
                            'status' => abs($allocatedCash - $thisPartial) <= 0.01 ? 'paid' : 'partial'
                        ]);

                        $applied = $created->pay_total;
                    }

                    // Update billing paid_amount and status
                        $billing->paid_amount = (float) $billing->paid_amount + $applied;
                        $newBalance = (float) $billing->total_amount - $billing->paid_amount;
                        // If this is the group partial flow and we reached exactly 50% paid, mark as 'paid 50%'
                        if (abs($billing->paid_amount - ((float)$billing->total_amount * 0.5)) <= 0.01) {
                            $billing->bill_status = 'paid 50%';
                        } else {
                            $billing->bill_status = $newBalance <= 0.01 ? 'paid' : 'partial';
                        }
                    $billing->save();

                    // record applied detail for response and logging
                    $appliedDetails[] = [
                        'bill_id' => $billing->bill_id,
                        'applied' => $applied,
                        'new_paid_amount' => $billing->paid_amount,
                        'bill_status' => $billing->bill_status,
                        'remaining_for_bill' => round((float)$billing->total_amount - $billing->paid_amount, 2)
                    ];

                    Log::info("Group partial applied - Bill: {$billing->bill_id}, applied: {$applied}, new_paid: {$billing->paid_amount}, status: {$billing->bill_status}");

                    $remainingCash = round($remainingCash - $applied, 2);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Partial payment applied for owner group (50%).',
                    'change' => max(0, $cash - $requiredPartial),
                    'final_status' => 'partial',
                    'applied_details' => $appliedDetails
                ]);
            }
            
            // Calculate payment amount (always full payment for groups)
            $paymentAmount = $currentBalance;
            $change = $cash - $currentBalance;
            
            // Distribute payment proportionally across all billings
            $remainingPayment = $paymentAmount;
            $transactionKey = 'PAY-GROUP-' . $ownerId . '-' . time();
            
            foreach ($billings as $billing) {
                $billingBalance = (float) $billing->total_amount - (float) $billing->paid_amount;

                if ($billingBalance <= 0) {
                    continue; // Skip already paid bills
                }

                // Calculate proportional payment for this bill
                $proportion = $billingBalance / $currentBalance;
                $thisBillPayment = round($proportion * $paymentAmount, 2);

                // Adjust for rounding errors on last bill
                if ($billing->bill_id === $billings->last()->bill_id) {
                    $thisBillPayment = $remainingPayment;
                }

                // First, if there is a pending partial placeholder, apply portion of this payment to it
                $placeholder = $billing->payments()->where('payment_type', 'partial')->where('status', 'pending')->first();
                $appliedToPlaceholder = 0;
                if ($placeholder && $thisBillPayment > 0) {
                    $apply = min($thisBillPayment, (float) $placeholder->pay_total);
                    if ($apply > 0) {
                        $placeholder->pay_cashAmount = $billing->bill_id === $billings->first()->bill_id ? $cash : ($placeholder->pay_cashAmount ?? 0);
                        $placeholder->pay_change = $billing->bill_id === $billings->first()->bill_id ? $change : ($placeholder->pay_change ?? 0);
                        $placeholder->payment_date = now();
                        $placeholder->transaction_id = $transactionKey;
                        // If we fully cover the placeholder, mark it paid
                        if (abs($apply - (float) $placeholder->pay_total) <= 0.01) {
                            $placeholder->status = 'paid';
                        } else {
                            $placeholder->status = 'partial';
                            $placeholder->pay_total = $apply; // record partial application
                        }
                        $placeholder->save();

                        $appliedToPlaceholder = $apply;
                        $thisBillPayment = round($thisBillPayment - $apply, 2);
                    }
                }

                // Create payment record for the remainder (if any)
                $createdPayment = null;
                if ($thisBillPayment > 0) {
                    $createdPayment = Payment::create([
                        'bill_id' => $billing->bill_id,
                        'pay_total' => $thisBillPayment,
                        'pay_cashAmount' => $billing->bill_id === $billings->first()->bill_id ? $cash : 0,
                        'pay_change' => $billing->bill_id === $billings->first()->bill_id ? $change : 0,
                        'payment_type' => 'full',
                        'payment_date' => now(),
                        'transaction_id' => $transactionKey,
                        'status' => ($thisBillPayment >= ($billingBalance - $appliedToPlaceholder)) ? 'paid' : 'partial'
                    ]);
                }

                // Update billing record: include any applied placeholder amount + created payment
                $newPaidAmount = (float) $billing->paid_amount + $appliedToPlaceholder + ($createdPayment?->pay_total ?? 0);
                $newBalance = (float) $billing->total_amount - $newPaidAmount;
                $isFullyPaid = ($newBalance <= 0.01);

                $billing->paid_amount = $newPaidAmount;
                $billing->bill_status = $isFullyPaid ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'pending');
                $billing->save();

                // Update visit status if fully paid
                if ($isFullyPaid && $billing->visit) {
                    $billing->visit->visit_status = 'completed';
                    $billing->visit->save();
                }

                $remainingPayment = round($remainingPayment - ($appliedToPlaceholder + ($createdPayment?->pay_total ?? 0)), 2);
            }
            
            DB::commit();
            
            $allPaid = $billings->every(function($b) {
                return $b->bill_status === 'paid';
            });
            
            $finalStatus = $allPaid ? 'paid' : 'partial';
            
            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully for all pets!',
                'change' => $change,
                'final_status' => $finalStatus,
                'paid_amount' => $totalPaidSoFar + $paymentAmount,
                'total_amount' => $grandTotal,
                'remaining_balance' => $currentBalance - $paymentAmount,
                'pet_count' => $billings->count()
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Grouped payment processing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
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
                ->where('pres_visit_id', $billing->visit->visit_id)
                    ->where('pres_visit_id', $billing->visit->visit_id)
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
            
            // Calculate total paid from existing payments (only count payments with status 'paid')
            $existingPaymentsTotal = (float) $billing->payments->where('status', 'paid')->sum('pay_total');
            
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

            // If this is a partial payment and it equals 50% of the bill, mark specifically as 'paid 50%'
            if ($paymentType === 'partial' && abs($newTotalPaid - ($totalAmount * 0.5)) <= 0.01) {
                $billingStatus = 'paid 50%';
            }

            if ($newTotalPaid == 0) {
                $billingStatus = 'pending';
            }


            // Create or update payment record
            $transactionKey = 'PAY-' . $billId . '-' . time();

            if ($paymentType === 'partial') {
                // If a pending placeholder partial payment exists, update it to mark as paid
                $placeholder = $billing->payments()->where('payment_type', 'partial')->where('status', 'pending')->first();
                if ($placeholder) {
                    $placeholder->pay_total = $paymentAmount;
                    $placeholder->pay_cashAmount = $cash;
                    $placeholder->pay_change = $change;
                    $placeholder->payment_date = now();
                    $placeholder->transaction_id = $transactionKey;
                    $placeholder->status = $billingStatus === 'paid' ? 'paid' : 'partial';
                    $placeholder->save();
                    $payment = $placeholder;
                } else {
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
                }
            } else {
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
            }

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

    /**
     * Show receipt for a billing record (related to visit and visit services)
     */
    public function showReceipt($billId)
    {
        $billing = Billing::with([
            'visit.pet.owner',
            'visit.services',
            'visit.user.branch',
            'orders.product'
        ])->findOrFail($billId);

        // Billing must be related to a visit
        if (!$billing->visit) {
            abort(404, 'This billing record is not associated with a visit.');
        }

        // Calculate services total from visit services
        $servicesTotal = 0;
        if ($billing->visit->services && $billing->visit->services->count() > 0) {
            $servicesTotal = $billing->visit->services->sum('serv_price');
        }

        // Calculate prescription total from visit-related prescriptions
        $prescriptionTotal = 0;
        $prescriptionItems = [];
        
        if ($billing->visit->pet) {
            // Get prescriptions related to this visit's date (within visit timeframe)
            $visitDate = $billing->visit->visit_date ?? $billing->bill_date;
            $prescriptions = Prescription::where('pet_id', $billing->visit->pet->pet_id)
                ->where('pres_visit_id', $billing->visit->visit_id)
                ->whereDate('prescription_date', '<=', $visitDate)
                ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($visitDate . ' -7 days')))
                ->get();
            
            foreach ($prescriptions as $prescription) {
                $medications = json_decode($prescription->medication, true) ?? [];
                foreach ($medications as $medication) {
                    if (isset($medication['product_id']) && $medication['product_id']) {
                        $product = Product::find($medication['product_id']);
                        
                        if ($product) {
                            $prescriptionItems[] = [
                                'name' => $product->prod_name,
                                'price' => $product->prod_price,
                                'instructions' => $medication['instructions'] ?? ''
                            ];
                            $prescriptionTotal += $product->prod_price;
                        }
                    }
                }
            }
        }

        // Calculate products total from orders
        $productsTotal = $billing->orders->sum(function($order) {
            return $order->ord_quantity * ($order->product->prod_price ?? 0);
        });
        $productsTotal = 0;
        $grandTotal = $servicesTotal + $prescriptionTotal + $productsTotal;
        
        // Get branch info from visit user
        $branch = $billing->visit->user?->branch ?? \App\Models\Branch::first();
        
        return view('billing-receipt', compact(
            'billing',
            'servicesTotal',
            'prescriptionTotal',
            'prescriptionItems',
            'productsTotal',
            'grandTotal',
            'branch'
        ));
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

    /**
     * Get available products for sale (excludes out-of-stock and expired products)
     */
    public function getAvailableProducts(Request $request)
    {
        $search = $request->input('search', '');
        
        $query = Product::with(['stockBatches', 'manufacturer'])
            ->where('prod_type', 'Sale');
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('prod_name', 'like', "%{$search}%")
                  ->orWhere('prod_category', 'like', "%{$search}%");
            });
        }
        
        $products = $query->get();
        
        // Filter out expired and out-of-stock products
        $availableProducts = $products->filter(function($product) {
            // Check if product is not disabled (not expired and has stock)
            return !$product->is_disabled;
        })->map(function($product) {
            return [
                'prod_id' => $product->prod_id,
                'prod_name' => $product->prod_name,
                'prod_price' => (float) $product->prod_price,
                'prod_stocks' => $product->available_stock - $product->usage_from_inventory_transactions,
                'prod_category' => $product->prod_category,
                'prod_image' => $product->prod_image,
                'is_expired' => $product->all_expired,
                'is_out_of_stock' => $product->is_out_of_stock,
            ];
        })->values();
        
        return response()->json([
            'products' => $availableProducts
        ]);
    }
}
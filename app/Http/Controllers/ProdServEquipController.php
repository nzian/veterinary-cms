<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\ServiceProduct; 
use App\Models\InventoryTransaction; 
use App\Services\InventoryService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class ProdServEquipController extends Controller
{

     protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
    }

    // Helper to get redirect tab value
    protected function getRedirectTab(Request $request, $default = 'products')
    {
        return $request->input('tab', $default); 
    }

    /**
     * Record inventory transaction for audit trail
     */
    private function recordInventoryTransaction($productId, $type, $quantityChange, $reference = null, $notes = null, $appointmentId = null, $serviceId = null)
    {
        try {
            InventoryTransaction::create([
                'prod_id' => $productId,
                'transaction_type' => $type,
                'quantity_change' => $quantityChange,
                'reference' => $reference,
                'notes' => $notes,
                'appoint_id' => $appointmentId,
                'serv_id' => $serviceId,
                'performed_by' => auth()->id(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to record inventory transaction: " . $e->getMessage());
        }
    }

    public function getServiceProducts($serviceId)
    {
        try {
            $serviceProducts = ServiceProduct::where('serv_id', $serviceId)
                ->with('product')
                ->get()
                ->map(function($sp) {
                    return [
                        'id' => $sp->id,
                        'prod_id' => $sp->prod_id,
                        'product_name' => $sp->product->prod_name ?? 'Unknown',
                        'quantity_used' => $sp->quantity_used,
                        'is_billable' => $sp->is_billable,
                        'current_stock' => $sp->product->prod_stocks ?? 0
                    ];
                });

            return response()->json([
                'success' => true,
                'products' => $serviceProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateServiceProducts(Request $request, $serviceId)
    {
        try {
            $validated = $request->validate([
                'products' => 'required|array',
                'products.*.prod_id' => 'required|exists:tbl_prod,prod_id',
                'products.*.quantity_used' => 'required|numeric|min:0.01',
                'products.*.is_billable' => 'boolean'
            ]);

            DB::beginTransaction();

            ServiceProduct::where('serv_id', $serviceId)->delete();

            foreach ($validated['products'] as $productData) {
                ServiceProduct::create([
                    'serv_id' => $serviceId,
                    'prod_id' => $productData['prod_id'],
                    'quantity_used' => $productData['quantity_used'],
                    'is_billable' => $productData['is_billable'] ?? false
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service products updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewProduct($id)
    {
        try {
            $product = Product::with('branch')->findOrFail($id);
            
            $salesData = DB::table('tbl_ord')
                ->where('prod_id', $id)
                ->selectRaw('
                    COUNT(*) as total_orders,
                    COALESCE(SUM(ord_quantity), 0) as total_quantity_sold,
                    COALESCE(SUM(ord_quantity * ?), 0) as total_revenue,
                    COALESCE(AVG(ord_quantity * ?), 0) as average_order_value
                ', [$product->prod_price, $product->prod_price])
                ->first();

            $monthlySales = DB::table('tbl_ord')
                ->where('prod_id', $id)
                ->where('ord_date', '>=', Carbon::now()->subMonths(6))
                ->selectRaw('
                    YEAR(ord_date) as year,
                    MONTH(ord_date) as month,
                    SUM(ord_quantity) as quantity,
                    SUM(ord_quantity * ?) as revenue
                ', [$product->prod_price])
                ->groupBy(DB::raw('YEAR(ord_date)'), DB::raw('MONTH(ord_date)'))
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $recentOrders = DB::table('tbl_ord')
                ->leftJoin('tbl_user', 'tbl_ord.user_id', '=', 'tbl_user.user_id')
                ->leftJoin('tbl_own', 'tbl_ord.own_id', '=', 'tbl_own.own_id')
                ->where('tbl_ord.prod_id', $id)
                ->select(
                    'tbl_ord.ord_id',
                    'tbl_ord.ord_quantity',
                    'tbl_ord.ord_date',
                    'tbl_ord.bill_id',
                    'tbl_user.user_name',
                    'tbl_own.own_name as customer_name'
                )
                ->orderBy('tbl_ord.ord_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function($order) use ($product) {
                    return (object)[
                        'ord_quantity' => $order->ord_quantity,
                        'ord_total' => $order->ord_quantity * $product->prod_price,
                        'ord_date' => $order->ord_date,
                        'user_name' => $order->user_name ?? 'System',
                        'customer_name' => $order->customer_name ?? 'Walk-in Customer',
                        'source' => $order->bill_id ? 'Billing #' . $order->bill_id : 'Direct Sale'
                    ];
                });

            $stockAlert = null;
            if ($product->prod_reorderlevel && $product->prod_stocks <= $product->prod_reorderlevel) {
                $stockAlert = 'low_stock';
            }

            $profitData = [
                'total_revenue' => $salesData->total_revenue ?? 0,
                'profit_margin_percentage' => 0
            ];

            return response()->json([
                'product' => $product,
                'sales_data' => $salesData,
                'monthly_sales' => $monthlySales,
                'recent_orders' => $recentOrders,
                'stock_alert' => $stockAlert,
                'profit_data' => $profitData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch product details: ' . $e->getMessage()], 500);
        }
    }

    public function viewService($id)
    {
        try {
            $service = Service::with('branch')->findOrFail($id);
            
            $appointments = DB::table('tbl_appoint')
                ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->where('tbl_appoint_serv.serv_id', $id)
                ->pluck('tbl_appoint.appoint_id');

            $revenueData = (object)[
                'total_bookings' => $appointments->count(),
                'total_revenue' => $appointments->count() * $service->serv_price,
                'average_booking_value' => $service->serv_price
            ];

            $monthlyRevenue = DB::table('tbl_appoint')
                ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->where('tbl_appoint_serv.serv_id', $id)
                ->where('tbl_appoint.appoint_date', '>=', Carbon::now()->subMonths(6))
                ->selectRaw('
                    YEAR(tbl_appoint.appoint_date) as year,
                    MONTH(tbl_appoint.appoint_date) as month,
                    COUNT(DISTINCT tbl_appoint.appoint_id) as bookings,
                    COUNT(DISTINCT tbl_appoint.appoint_id) * ? as revenue
                ', [$service->serv_price])
                ->groupBy(DB::raw('YEAR(tbl_appoint.appoint_date)'), DB::raw('MONTH(tbl_appoint.appoint_date)'))
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Primary source: visits table (various possible names)
            $visitTables = ['tbl_visit', 'tbl_visits', 'tbl_visit_record', 'tbl_visit_records', 'visits', 'visit'];
            $visitsTable = null;
            foreach ($visitTables as $vt) {
                if (!Schema::hasTable($vt)) { continue; }
                try {
                    // Probe columns to ensure compatibility
                    $probe = DB::table($vt)->limit(1)->first();
                    $columns = Schema::getColumnListing($vt);
                    $need = ['visit_date','visit_status','pet_id','user_id','service_type'];
                    $hasAll = !array_diff($need, $columns);
                    if ($hasAll) { $visitsTable = $vt; break; }
                } catch (\Throwable $t) {
                    // skip invalid table
                }
            }

            if ($visitsTable) {
                // Build recent visits for this service using visits table
                $recentAppointments = DB::table($visitsTable . ' as v')
                    ->leftJoin('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
                    ->leftJoin('tbl_own as o', 'p.own_id', '=', 'o.own_id')
                    ->leftJoin('tbl_user as u', 'v.user_id', '=', 'u.user_id')
                    ->where(function($q) use ($service) {
                        $q->where('v.service_type', $service->serv_type)
                          ->orWhere('v.service_type', $service->serv_name)
                          ->orWhere('v.service_type', 'like', "%".$service->serv_type."%")
                          ->orWhere('v.service_type', 'like', "%".$service->serv_name."%");
                    })
                    ->select(
                        DB::raw('NULL as appoint_id'),
                        'v.visit_date as appoint_date',
                        DB::raw('NULL as appoint_time'),
                        'v.visit_status as appoint_status',
                        DB::raw("CONCAT('Visit - ', COALESCE(v.service_type,'')) as appoint_type"),
                        'p.pet_name',
                        'p.pet_species',
                        'o.own_name',
                        'o.own_contactnum',
                        'u.user_name'
                    )
                    ->orderBy('v.visit_date', 'desc')
                    ->orderBy('v.updated_at', 'desc')
                    ->limit(10)
                    ->get();
            } else {
                // Fallback 1: pivot appoint-service linkage
                $recentAppointments = DB::table('tbl_appoint_serv as aps')
                    ->join('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
                    ->leftJoin('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
                    ->leftJoin('tbl_own as o', 'p.own_id', '=', 'o.own_id')
                    // Prefer veterinarian assigned on the service record; fallback to scheduler/handler on appointment
                    ->leftJoin('tbl_user as v', 'aps.vet_user_id', '=', 'v.user_id')
                    ->leftJoin('tbl_user as u', 'a.user_id', '=', 'u.user_id')
                    ->where('aps.serv_id', $id)
                    ->select(
                        'a.appoint_id',
                        'a.appoint_date',
                        'a.appoint_time',
                        'a.appoint_status',
                        'a.appoint_type',
                        'p.pet_name',
                        'p.pet_species',
                        'o.own_name',
                        'o.own_contactnum',
                        DB::raw('COALESCE(v.user_name, u.user_name) as user_name')
                    )
                    ->orderBy('a.appoint_date', 'desc')
                    ->orderBy('a.appoint_time', 'desc')
                    ->limit(10)
                    ->get();

                // Fallback 2: heuristic by appointment_type
                if ($recentAppointments->isEmpty()) {
                    $recentAppointments = DB::table('tbl_appoint as a')
                        ->leftJoin('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
                        ->leftJoin('tbl_own as o', 'p.own_id', '=', 'o.own_id')
                        ->leftJoin('tbl_user as u', 'a.user_id', '=', 'u.user_id')
                        ->where(function($q) use ($service) {
                            $q->where('a.appoint_type', $service->serv_name)
                              ->orWhere('a.appoint_type', 'like', "%".$service->serv_name."%")
                              ->orWhere('a.appoint_type', 'like', "%".$service->serv_type."%");
                        })
                        ->select(
                            'a.appoint_id',
                            'a.appoint_date',
                            'a.appoint_time',
                            'a.appoint_status',
                            'a.appoint_type',
                            'p.pet_name',
                            'p.pet_species',
                            'o.own_name',
                            'o.own_contactnum',
                            'u.user_name'
                        )
                        ->orderBy('a.appoint_date', 'desc')
                        ->orderBy('a.appoint_time', 'desc')
                        ->limit(10)
                        ->get();
                }
            }

            $utilizationData = DB::table('tbl_appoint')
                ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->where('tbl_appoint_serv.serv_id', $id)
                ->selectRaw('
                    tbl_appoint.appoint_status,
                    COUNT(*) as count
                ')
                ->groupBy('tbl_appoint.appoint_status')
                ->get();

            $peakTimes = DB::table('tbl_appoint')
                ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->where('tbl_appoint_serv.serv_id', $id)
                ->selectRaw('
                    HOUR(tbl_appoint.appoint_time) as hour,
                    COUNT(*) as bookings
                ')
                ->groupBy(DB::raw('HOUR(tbl_appoint.appoint_time)'))
                ->orderBy('bookings', 'desc')
                ->limit(5)
                ->get();

            $appointmentTypes = DB::table('tbl_appoint')
                ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->where('tbl_appoint_serv.serv_id', $id)
                ->selectRaw('
                    tbl_appoint.appoint_type,
                    COUNT(*) as count
                ')
                ->groupBy('tbl_appoint.appoint_type')
                ->get();

            return response()->json([
                'service' => $service,
                'revenue_data' => $revenueData,
                'monthly_revenue' => $monthlyRevenue,
                'recent_appointments' => $recentAppointments,
                'utilization_data' => $utilizationData,
                'peak_times' => $peakTimes,
                'appointment_types' => $appointmentTypes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch service details: ' . $e->getMessage()], 500);
        }
    }

    public function viewEquipment($id)
    {
        try {
            $equipment = Equipment::findOrFail($id);
            
            $usageData = [
                'total_quantity' => $equipment->equipment_quantity,
                'available_quantity' => $equipment->equipment_quantity, 
                'in_use_quantity' => 0, 
                'maintenance_quantity' => 0,
                'branch' => $equipment->branch->branch_name ?? 'N/A'
            ];

            $availabilityStatus = strtolower($equipment->equipment_status ?? 'Available');
            if ($equipment->equipment_quantity == 0) {
                $availabilityStatus = 'none';
            } elseif (in_array($availabilityStatus, ['under maintenance', 'out of service'])) {
                 $availabilityStatus = 'unavailable';
            }

            $usageHistory = collect([
                [
                    'date' => Carbon::now()->subDays(1)->toISOString(),
                    'action' => 'Used',
                    'quantity' => 2,
                    'user' => 'Dr. Smith',
                    'purpose' => 'Surgery'
                ],
                [
                    'date' => Carbon::now()->subDays(3)->toISOString(),
                    'action' => 'Maintenance',
                    'quantity' => 1,
                    'user' => 'Technician',
                    'purpose' => 'Regular checkup'
                ],
            ]);

            $conditionData = [
                'excellent' => intval($equipment->equipment_quantity * 0.8),
                'good' => intval($equipment->equipment_quantity * 0.15),
                'fair' => intval($equipment->equipment_quantity * 0.05),
                'poor' => 0,
                'last_updated' => $equipment->updated_at ?? now()
            ];

            return response()->json([
                'equipment' => $equipment,
                'usage_data' => $usageData,
                'usage_history' => $usageHistory,
                'availability_status' => $availabilityStatus,
                'condition_data' => $conditionData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch equipment details: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();

        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        $productsPerPage = $request->get('productsPerPage', 10);
        $servicesPerPage = $request->get('servicesPerPage', 10);
        $equipmentPerPage = $request->get('equipmentPerPage', 10);

        $productsPerPage = $productsPerPage === 'all' ? PHP_INT_MAX : (int)$productsPerPage;
        $servicesPerPage = $servicesPerPage === 'all' ? PHP_INT_MAX : (int)$servicesPerPage;
        $equipmentPerPage = $equipmentPerPage === 'all' ? PHP_INT_MAX : (int)$equipmentPerPage;

        $products = Product::with('branch')
            ->where('prod_category', '!=', 'Service')
            ->when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->orderBy('prod_id', 'desc')
            ->paginate($productsPerPage, ['*'], 'productsPage')
            ->appends($request->except('productsPage'));

        $services = Service::when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->orderBy('serv_id', 'desc')
            ->paginate($servicesPerPage, ['*'], 'servicesPage')
            ->appends($request->except('servicesPage'));

        $equipment = Equipment::when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->orderBy('equipment_id', 'desc')
            ->paginate($equipmentPerPage, ['*'], 'equipmentPage')
            ->appends($request->except('equipmentPage'));

        $branches = Branch::all();

        $allProducts = Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_category')
                ->where('prod_type', 'Consumable')
                ->orderBy('prod_id', 'desc')
                ->get();

        return view('prodServEquip', compact('products', 'branches', 'services', 'equipment','allProducts'));
    }

    // -------------------- PRODUCT METHODS --------------------
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'prod_name' => 'required|string|max:255',
            'prod_category' => 'nullable|string|max:255',
            'prod_type' => 'required|in:Sale,Consumable',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'required|numeric|min:0',
            'prod_stocks' => 'nullable|integer|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'prod_expiry' => 'nullable|date',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        if ($request->hasFile('prod_image')) {
            $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $product = Product::create($validated);

            // Record initial stock as inventory transaction
            if (isset($validated['prod_stocks']) && $validated['prod_stocks'] > 0) {
                $this->recordInventoryTransaction(
                    $product->prod_id,
                    'restock',
                    $validated['prod_stocks'],
                    'Initial Stock',
                    'Product created with initial stock'
                );
            }

            DB::commit();
            $redirectTab = $this->getRedirectTab($request, 'products');
            return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Product added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to add product: ' . $e->getMessage());
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $validated = $request->validate([
            'prod_name' => 'required|string|max:255',
            'prod_category' => 'nullable|string|max:255',
            'prod_type' => 'required|in:Sale,Consumable',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'required|numeric|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $product = Product::findOrFail($id);

        if ($request->hasFile('prod_image')) {
            if ($product->prod_image) {
                Storage::disk('public')->delete($product->prod_image);
            }
            $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
        }

        $product->update($validated);

        $redirectTab = $this->getRedirectTab($request, 'products');
        return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Product updated successfully!');
    }

    public function deleteProduct($id, Request $request)
    {
        $product = Product::findOrFail($id);
        
        if ($product->prod_image) {
            Storage::disk('public')->delete($product->prod_image);
        }
        
        // Record deletion in inventory transactions
        $this->recordInventoryTransaction(
            $product->prod_id,
            'adjustment',
            -($product->prod_stocks ?? 0),
            'Product Deletion',
            "Product '{$product->prod_name}' was deleted from inventory"
        );
        
        $product->delete();

        $redirectTab = $this->getRedirectTab($request, 'products');
        return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Product deleted successfully!');
    }

    // -------------------- INVENTORY UPDATE METHODS (UPDATED) --------------------
    
    /**
     * ✅ UPDATED: Update stock with automatic inventory transaction recording
     */
    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'add_stock' => 'required|integer|min:1',
            'new_expiry' => 'required|date',
            'reorder_level' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $product = Product::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $oldStock = $product->prod_stocks ?? 0;
            $addedStock = $validated['add_stock'];
            
            // Update product stock
            $product->prod_stocks = $oldStock + $addedStock;
            $product->prod_expiry = $validated['new_expiry'];
            
            if (isset($validated['reorder_level'])) {
                $product->prod_reorderlevel = $validated['reorder_level'];
            }
            
            $product->save();
            
            // ✅ Record the restock transaction
            $this->recordInventoryTransaction(
                $product->prod_id,
                'restock',
                $addedStock,
                'Manual Stock Update',
                $validated['notes'] ?? "Stock increased from {$oldStock} to {$product->prod_stocks}. New expiry: {$validated['new_expiry']}"
            );
            
            DB::commit();
            
            $redirectTab = $this->getRedirectTab($request, 'products');
            return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])
                ->with('success', "Stock updated successfully! Added {$addedStock} units.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update stock: ' . $e->getMessage());
        }
    }

    /**
     * ✅ UPDATED: Update damage/pullout with AUTOMATIC STOCK DEDUCTION and transaction recording
     */
    public function updateDamage(Request $request, $id)
    {
        $validated = $request->validate([
            'damaged_qty' => 'nullable|integer|min:0',
            'pullout_qty' => 'nullable|integer|min:0',
            'reason' => 'nullable|string',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $product = Product::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $oldDamaged = $product->prod_damaged ?? 0;
            $oldPullout = $product->prod_pullout ?? 0;
            $oldStock = $product->prod_stocks ?? 0;
            
            $newDamaged = $validated['damaged_qty'] ?? 0;
            $newPullout = $validated['pullout_qty'] ?? 0;
            
            // Calculate the DIFFERENCE (how many NEW damaged/pullout items)
            $damagedDiff = $newDamaged - $oldDamaged;
            $pulloutDiff = $newPullout - $oldPullout;
            
            $totalDeduction = $damagedDiff + $pulloutDiff;
            
            // ✅ VALIDATE: Ensure we have enough stock to deduct
            if ($totalDeduction > $oldStock) {
                DB::rollBack();
                return redirect()->back()->with('error', "Insufficient stock! Cannot deduct {$totalDeduction} units. Current stock: {$oldStock}");
            }
            
            // ✅ UPDATE: Deduct from stock automatically
            if ($totalDeduction > 0) {
                $product->prod_stocks = $oldStock - $totalDeduction;
            }
            
            // Update damaged and pullout quantities
            $product->prod_damaged = $newDamaged;
            $product->prod_pullout = $newPullout;
            
            $product->save();
            
            // ✅ RECORD TRANSACTIONS for damaged items
            if ($damagedDiff > 0) {
                $this->recordInventoryTransaction(
                    $product->prod_id,
                    'damage',
                    -$damagedDiff,
                    'Damaged Items',
                    $validated['reason'] ?? "Marked {$damagedDiff} units as damaged. Total damaged: {$newDamaged}"
                );
            }
            
            // ✅ RECORD TRANSACTIONS for pullout items
            if ($pulloutDiff != 0) {  // Changed to check for any difference, not just positive
                $this->recordInventoryTransaction(
                    $product->prod_id,
                    'pullout',
                    -$pulloutDiff,
                    'Pullout Items',
                    $validated['reason'] ?? "Pulled out {$pulloutDiff} units for quality control. Total pullout: {$newPullout}"
                );
            }
            
            DB::commit();
            
            $message = "Updated successfully! ";
            if ($totalDeduction > 0) {
                $message .= "Deducted {$totalDeduction} units from stock (Damaged: {$damagedDiff}, Pullout: {$pulloutDiff}). New stock: {$product->prod_stocks}";
            }
            
            $redirectTab = $this->getRedirectTab($request, 'products');
            return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    // -------------------- INVENTORY HISTORY VIEW (UPDATED) --------------------
    
    /**
     * ✅ UPDATED: View comprehensive inventory history from transactions table
     */
   public function viewInventoryHistory($id)
{
    try {
        $product = Product::findOrFail($id);
        
        // Check if InventoryTransaction table exists and has data
        $transactionsTableExists = Schema::hasTable('tbl_inventory_transactions');
        $hasTransactions = false;
        if ($transactionsTableExists) {
            $hasTransactions = DB::table('tbl_inventory_transactions')
                ->where('prod_id', $id)
                ->whereIn('transaction_type', ['sale', 'restock', 'damage', 'pullout', 'service_usage'])
                ->exists();
        }
        
        if ($transactionsTableExists && $hasTransactions) {
            // ✅ Get inventory transactions from the dedicated table
            $hasUserIdCol = Schema::hasColumn('tbl_inventory_transactions', 'user_id');
            $hasPerformedByCol = Schema::hasColumn('tbl_inventory_transactions', 'performed_by');
            $hasServIdCol = Schema::hasColumn('tbl_inventory_transactions', 'serv_id');

            $query = DB::table('tbl_inventory_transactions as it');
            // Prefer performed_by if it exists, otherwise fallback to user_id if present
            if ($hasPerformedByCol) {
                $query->leftJoin('tbl_user as u', 'it.performed_by', '=', 'u.user_id');
            } elseif ($hasUserIdCol) {
                $query->leftJoin('tbl_user as u', 'it.user_id', '=', 'u.user_id');
            }
            if ($hasServIdCol) {
                $query->leftJoin('tbl_serv as s', 'it.serv_id', '=', 's.serv_id');
            }

            $selects = [
                'it.created_at',
                'it.transaction_type',
                'it.quantity_change',
                'it.reference',
                'it.notes',
                'it.appoint_id',
                'it.serv_id',
            ];
            if ($hasPerformedByCol) {
                $selects[] = 'it.performed_by';
            } else {
                $selects[] = DB::raw('NULL as performed_by');
            }
            // Add conditional projections for names when joins are not present
            if ($hasPerformedByCol || $hasUserIdCol) {
                $selects[] = 'u.user_name';
            } else {
                $selects[] = DB::raw('NULL as user_name');
            }
            if ($hasServIdCol) {
                $selects[] = 's.serv_name';
            } else {
                $selects[] = DB::raw('NULL as serv_name');
            }

            $stockHistory = collect(
                $query
                    ->where('it.prod_id', $id)
                    ->select($selects)
                    ->orderBy('it.created_at', 'desc')
                    ->get()
            )
                ->map(function($trans) {
                    $reference = $trans->reference ?? 'N/A';
                    $type = $trans->transaction_type;
                    $quantity = $trans->quantity_change;
                    $notes = $trans->notes ?? 'No notes';
                    
                    // Format type for display
                    $displayType = ucfirst($type);
                    
                    // Build detailed reference and notes
                    if ($trans->appoint_id) {
                        $reference .= " (Appointment #{$trans->appoint_id})";
                    }
                    if ($trans->serv_id && $trans->serv_name) {
                        $reference .= " - {$trans->serv_name}";
                    }
                    
                    // Special handling for pullout transactions
                    if ($type === 'pullout') {
                        $notes = "Pulled out " . abs($quantity) . " units. " . $notes;
                    }
                    
                    return [
                        'date' => $trans->created_at,
                        'type' => $displayType,
                        'quantity' => $quantity,
                        'reference' => $reference,
                        'user' => ($trans->user_name ?? null) ? $trans->user_name : (($trans->performed_by ?? null) ? ('User #'.$trans->performed_by) : 'System'),
                        'notes' => $notes
                    ];
                });
        } else {
            // Fallback: Get stock history primarily from orders table
            $orders = collect(DB::table('tbl_ord')
                ->leftJoin('tbl_user', 'tbl_ord.user_id', '=', 'tbl_user.user_id')
                ->where('tbl_ord.prod_id', $id)
                ->select(
                    'tbl_ord.ord_date as created_at',
                    DB::raw("'sale' as transaction_type"),
                    DB::raw('-(tbl_ord.ord_quantity) as quantity_change'),
                    DB::raw("CONCAT('Order #', tbl_ord.ord_id) as reference"),
                    'tbl_user.user_name',
                    DB::raw("CASE 
                        WHEN tbl_ord.bill_id IS NOT NULL THEN CONCAT('Billing Payment #', tbl_ord.bill_id)
                        ELSE 'Direct Sale via POS'
                    END as notes")
                )
                ->orderBy('tbl_ord.ord_date', 'desc')
                ->get())
                ->map(function($trans) {
                    return [
                        'date' => $trans->created_at,
                        'type' => $trans->transaction_type,
                        'quantity' => $trans->quantity_change,
                        'reference' => $trans->reference,
                        'user' => $trans->user_name ?? 'System',
                        'notes' => $trans->notes
                    ];
                });

            // Add synthetic entries for damage and pullout based on product fields so history isn't empty
            $synthetic = collect();
            if (($product->prod_damaged ?? 0) > 0) {
                $synthetic->push([
                    'date' => $product->updated_at ?? now(),
                    'type' => 'damage',
                    'quantity' => -abs($product->prod_damaged),
                    'reference' => 'Damaged Items (summary)',
                    'user' => 'System',
                    'notes' => 'Total damaged recorded on product record'
                ]);
            }
            if (($product->prod_pullout ?? 0) > 0) {
                $synthetic->push([
                    'date' => $product->updated_at ?? now(),
                    'type' => 'pullout',
                    'quantity' => -abs($product->prod_pullout),
                    'reference' => 'Pull-out Items (summary)',
                    'user' => 'System',
                    'notes' => 'Total pull-out recorded on product record'
                ]);
            }

            $stockHistory = $synthetic->concat($orders);
        }

        // Get service usage data
        $servicesUsing = ServiceProduct::where('prod_id', $id)
            ->with('service')
            ->get()
            ->map(function($sp) {
                return [
                    'service_id' => $sp->serv_id,
                    'service_name' => $sp->service->serv_name ?? 'Unknown',
                    'service_type' => $sp->service->serv_type ?? 'N/A',
                    'quantity_used' => $sp->quantity_used,
                ];
            });
        
        // Get recent service usage transactions
        $recentServiceUsage = [];
        if ($transactionsTableExists && $hasTransactions) {
            $recentServiceUsage = collect(DB::table('tbl_inventory_transactions as it')
                ->leftJoin('tbl_serv as s', 'it.serv_id', '=', 's.serv_id')
                ->leftJoin('tbl_appoint as a', 'it.appoint_id', '=', 'a.appoint_id')
                ->leftJoin('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
                ->leftJoin('tbl_own as o', 'p.own_id', '=', 'o.own_id')
                ->where('it.prod_id', $id)
                ->where('it.transaction_type', 'service_usage')
                ->select(
                    'it.created_at',
                    's.serv_name',
                    'it.appoint_id',
                    'p.pet_name',
                    'o.own_name',
                    'it.quantity_change',
                    'it.reference'
                )
                ->orderBy('it.created_at', 'desc')
                ->limit(20)
                ->get())
                ->map(function($trans) {
                    return [
                        'date' => Carbon::parse($trans->created_at)->format('M d, Y H:i'),
                        'service_name' => $trans->serv_name ?? 'N/A',
                        'appointment_id' => $trans->appoint_id,
                        'pet_name' => $trans->pet_name ?? 'N/A',
                        'owner_name' => $trans->own_name ?? 'N/A',
                        'quantity_used' => abs($trans->quantity_change),
                        'reference' => $trans->reference ?? 'N/A',
                    ];
                });
        }
        
        // Calculate totals
        if ($transactionsTableExists && $hasTransactions) {
            $totalUsedInServices = abs(DB::table('tbl_inventory_transactions')
                ->where('prod_id', $id)
                ->where('transaction_type', 'service_usage')
                ->sum('quantity_change'));

            $damageAnalysis = [
                'total_damaged' => abs(DB::table('tbl_inventory_transactions')
                    ->where('prod_id', $id)
                    ->where('transaction_type', 'damage')
                    ->sum('quantity_change')),
                'total_pullout' => abs(DB::table('tbl_inventory_transactions')
                    ->where('prod_id', $id)
                    ->where('transaction_type', 'pullout')
                    ->sum('quantity_change')),
                'total_sold' => abs(DB::table('tbl_inventory_transactions')
                    ->where('prod_id', $id)
                    ->where('transaction_type', 'sale')
                    ->sum('quantity_change')),
                'total_restocked' => DB::table('tbl_inventory_transactions')
                    ->where('prod_id', $id)
                    ->where('transaction_type', 'restock')
                    ->sum('quantity_change'),
            ];
        } else {
            $totalUsedInServices = 0;
            $totalSold = DB::table('tbl_ord')->where('prod_id', $id)->sum('ord_quantity');
            
            $damageAnalysis = [
                'total_damaged' => $product->prod_damaged ?? 0,
                'total_pullout' => $product->prod_pullout ?? 0,
                'total_sold' => $totalSold ?? 0,
                'total_restocked' => 0,
            ];
        }

        $totalMovement = $damageAnalysis['total_damaged'] + $damageAnalysis['total_sold'];
        $damageAnalysis['damage_percentage'] = $totalMovement > 0 ? 
            round(($damageAnalysis['total_damaged'] / $totalMovement) * 100, 2) : 0;

        // Expiry tracking
        $expiryData = [
            'expiry_date' => $product->prod_expiry,
            'days_until_expiry' => null,
            'expiry_status' => 'good'
        ];

        if ($product->prod_expiry) {
            $expiryDate = Carbon::parse($product->prod_expiry);
            $daysUntilExpiry = now()->diffInDays($expiryDate, false);
            $expiryData['days_until_expiry'] = $daysUntilExpiry;
            
            if ($daysUntilExpiry < 0) {
                $expiryData['expiry_status'] = 'expired';
            } elseif ($daysUntilExpiry <= 30) {
                $expiryData['expiry_status'] = 'warning';
            }
        }

        // Calculate average daily usage
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        if ($transactionsTableExists && $hasTransactions) {
            $averageDailyUsage = abs(DB::table('tbl_inventory_transactions')
                ->where('prod_id', $id)
                ->whereIn('transaction_type', ['sale', 'service_usage'])
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum('quantity_change')) / 30;
        } else {
            $averageDailyUsage = DB::table('tbl_ord')
                ->where('prod_id', $id)
                ->where('ord_date', '>=', $thirtyDaysAgo)
                ->sum('ord_quantity') / 30;
        }

        $stockAnalytics = [
            'current_stock' => $product->prod_stocks ?? 0,
            'reorder_level' => $product->prod_reorderlevel ?? 0,
            'average_daily_usage' => round($averageDailyUsage, 2),
            'days_until_reorder' => 0
        ];

        if ($stockAnalytics['average_daily_usage'] > 0 && $product->prod_reorderlevel && $product->prod_stocks > $product->prod_reorderlevel) {
            $stockAnalytics['days_until_reorder'] = intval(($product->prod_stocks - $product->prod_reorderlevel) / $stockAnalytics['average_daily_usage']);
        }

        return response()->json([
            'product' => $product,
            'stock_history' => $stockHistory,
            'damage_analysis' => $damageAnalysis,
            'expiry_data' => $expiryData,
            'stock_analytics' => $stockAnalytics,
            'services_using_product' => $servicesUsing,
            'recent_service_usage' => $recentServiceUsage,
            'total_used_in_services' => $totalUsedInServices
        ]);
    } catch (\Exception $e) {
        \Log::error("Inventory History Error: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        
        return response()->json([
            'error' => 'Failed to fetch inventory history',
            'message' => $e->getMessage(),
            'debug_info' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
}

    // -------------------- PRODUCT METHODS --------------------

    public function updateInventory(Request $request, $id)
    {
        $validated = $request->validate([
            'prod_stocks' => 'nullable|integer|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'prod_damaged' => 'nullable|integer|min:0',
            'prod_pullout' => 'nullable|integer|min:0',
            'prod_expiry' => 'nullable|date',
            'tab' => 'nullable|string|in:products,services,equipment' // Added tab for redirect
        ]);

        $product = Product::findOrFail($id);
        $product->update($validated);

        $redirectTab = $this->getRedirectTab($request, 'products');
        return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Inventory updated successfully!');
    }

    // -------------------- SERVICE METHODS --------------------
    public function storeService(Request $request)
{
    $validated = $request->validate([
        'serv_name' => 'required|string|max:255',
        'serv_type' => 'nullable|string|max:255',
        'serv_description' => 'nullable|string|max:1000',
        'serv_price' => 'required|numeric|min:0',
        'branch_id' => 'required|exists:tbl_branch,branch_id',
        'tab' => 'nullable|string|in:products,service,equipment' // Added tab for redirect
    ]);

    Service::create($validated);
    

    $redirectTab = $this->getRedirectTab($request, 'services');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Service added successfully!');
}


    public function updateService(Request $request, $id)
{
    $validated = $request->validate([
        'serv_name' => 'required|string|max:255',
        'serv_type' => 'nullable|string|max:255',
        'serv_description' => 'nullable|string|max:1000',
        'serv_price' => 'required|numeric|min:0',
        'branch_id' => 'required|exists:tbl_branch,branch_id',
        'tab' => 'nullable|string|in:products,service,equipment' // Added tab for redirect
    ]);

    $service = Service::findOrFail($id);
    $service->update($validated);

    $redirectTab = $this->getRedirectTab($request, 'services');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Service updated successfully!');
}
    public function deleteService($id, Request $request) // Inject Request for tab persistence
{
    $service = Service::findOrFail($id);
    $service->delete();

    $redirectTab = $this->getRedirectTab($request, 'services');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Service deleted successfully!');
}

    // -------------------- EQUIPMENT METHODS --------------------
    public function storeEquipment(Request $request)
{
    $validated = $request->validate([
        'equipment_name' => 'required|string|max:255',
        'equipment_quantity' => 'required|integer|min:0',
        'equipment_description' => 'nullable|string|max:1000',
        'equipment_category' => 'required|string|max:255',
        'equipment_status' => 'nullable|string|max:50',
        'equipment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'branch_id' => 'required|exists:tbl_branch,branch_id', 
        'tab' => 'nullable|string|in:products,services,equipment' 
    ]);

    if ($request->hasFile('equipment_image')) {
        $validated['equipment_image'] = $request->file('equipment_image')->store('equipment', 'public');
    }

    Equipment::create($validated);

    $redirectTab = $this->getRedirectTab($request, 'equipment');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Equipment added successfully!');
}

    public function updateEquipment(Request $request, $id)
{
    $validated = $request->validate([
        'equipment_name' => 'required|string|max:255',
        'equipment_quantity' => 'required|integer|min:0',
        'equipment_description' => 'nullable|string|max:1000',
        'equipment_category' => 'nullable|string|max:255',
        'equipment_status' => 'nullable|string|max:50',
        'equipment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'branch_id' => 'required|exists:tbl_branch,branch_id', 
        'tab' => 'nullable|string|in:products,services,equipment',
        'quantity_used' => 'nullable|integer|min:0' 
    ]);

    $equipment = Equipment::findOrFail($id);

    // Handle quantity deduction if quantity_used is provided
    if ($request->filled('quantity_used') && $request->quantity_used > 0) {
        $quantityUsed = (int) $request->quantity_used;
        $currentQuantity = $equipment->equipment_quantity;
        
        // Calculate new quantity (deduct used quantity)
        $newQuantity = max(0, $currentQuantity - $quantityUsed);
        $validated['equipment_quantity'] = $newQuantity;
    }

    if ($request->hasFile('equipment_image')) {
        if ($equipment->equipment_image) {
            Storage::disk('public')->delete($equipment->equipment_image);
        }
        $validated['equipment_image'] = $request->file('equipment_image')->store('equipment', 'public');
    }

    $equipment->update($validated);

    $redirectTab = $this->getRedirectTab($request, 'equipment');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Equipment updated successfully!');
}

    public function deleteEquipment($id, Request $request) // Inject Request for tab persistence
{
    $equipment = Equipment::findOrFail($id);
    
    if ($equipment->equipment_image) {
        Storage::disk('public')->delete($equipment->equipment_image);
    }
    
    $equipment->delete();

    $redirectTab = $this->getRedirectTab($request, 'equipment');
    return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Equipment deleted successfully!');
}

    /**
     * ✅ UPDATED METHOD: Updates the status of a specific equipment item.
     * @param \Illuminate\Http\Request $request
     * @param int $id The equipment ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateEquipmentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'equipment_status' => 'required|string|in:Available,In Use,Under Maintenance,Out of Service',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $equipment = Equipment::findOrFail($id);
        $equipment->equipment_status = $validated['equipment_status'];
        $equipment->save();

        $redirectTab = $this->getRedirectTab($request, 'equipment');
        return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])->with('success', 'Equipment status updated successfully!');
    }

public function getProductServiceUsage($productId)
{
    try {
        $product = Product::findOrFail($productId);
        
        // Get all services that use this product
        $servicesUsing = ServiceProduct::where('prod_id', $productId)
            ->with('service')
            ->get()
            ->map(function($sp) {
                return [
                    'service_id' => $sp->serv_id,
                    'service_name' => $sp->service->serv_name ?? 'Unknown',
                    'service_type' => $sp->service->serv_type ?? 'N/A',
                    'quantity_used' => $sp->quantity_used,
                    'is_billable' => $sp->is_billable
                ];
            });
        
        // Get service usage transactions from inventory
        $serviceUsageTransactions = InventoryTransaction::where('prod_id', $productId)
            ->where('transaction_type', 'service_usage')
            ->with(['service', 'appointment.pet.owner'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($trans) {
                return [
                    'date' => $trans->created_at->format('M d, Y H:i'),
                    'service_name' => $trans->service->serv_name ?? 'N/A',
                    'appointment_id' => $trans->appoint_id,
                    'pet_name' => $trans->appointment->pet->pet_name ?? 'N/A',
                    'owner_name' => $trans->appointment->pet->owner->own_name ?? 'N/A',
                    'quantity_used' => abs($trans->quantity_change),
                    'reference' => $trans->reference,
                ];
            });
        
        // Calculate total used in services
        $totalUsedInServices = InventoryTransaction::where('prod_id', $productId)
            ->where('transaction_type', 'service_usage')
            ->sum(DB::raw('ABS(quantity_change)'));
        
        return response()->json([
            'success' => true,
            'product' => $product,
            'services_using_product' => $servicesUsing,
            'recent_service_usage' => $serviceUsageTransactions,
            'total_used_in_services' => $totalUsedInServices
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getServiceInventoryOverview()
{
    try {
        // Get all products used in services with their service information
        $serviceProducts = ServiceProduct::with(['product', 'service'])
            ->get()
            ->groupBy('prod_id')
            ->map(function($items, $prodId) {
                $product = $items->first()->product;
                $services = $items->map(function($item) {
                    return [
                        'service_id' => $item->serv_id,
                        'service_name' => $item->service->serv_name ?? 'Unknown',
                        'service_type' => $item->service->serv_type ?? 'N/A',
                        'quantity_used' => $item->quantity_used,
                    ];
                });
                
                // Calculate total usage from all services
                $totalUsedInServices = $items->sum('quantity_used');
                
                // Get actual usage from transactions
                $actualUsage = InventoryTransaction::where('prod_id', $prodId)
                    ->where('transaction_type', 'service_usage')
                    ->sum(DB::raw('ABS(quantity_change)'));
                
                // Calculate how many services can be performed with current stock
                $servicesRemaining = [];
                foreach ($items as $item) {
                    if ($item->quantity_used > 0) {
                        $remaining = floor(($product->prod_stocks ?? 0) / $item->quantity_used);
                        $servicesRemaining[] = [
                            'service_name' => $item->service->serv_name ?? 'Unknown',
                            'remaining_count' => $remaining
                        ];
                    }
                }
                
                // Determine stock status
                $stockStatus = 'good';
                $statusClass = 'bg-green-100 text-green-800';
                
                if (($product->prod_stocks ?? 0) <= ($product->prod_reorderlevel ?? 10)) {
                    $stockStatus = 'low';
                    $statusClass = 'bg-red-100 text-red-800';
                } elseif (($product->prod_stocks ?? 0) <= (($product->prod_reorderlevel ?? 10) * 2)) {
                    $stockStatus = 'warning';
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                }
                
                return [
                    'product_id' => $prodId,
                    'product_name' => $product->prod_name ?? 'Unknown',
                    'product_category' => $product->prod_category ?? 'N/A',
                    'current_stock' => $product->prod_stocks ?? 0,
                    'reorder_level' => $product->prod_reorderlevel ?? 0,
                    'services_using' => $services,
                    'total_used_per_service_cycle' => $totalUsedInServices,
                    'actual_usage_count' => $actualUsage,
                    'services_remaining' => $servicesRemaining,
                    'stock_status' => $stockStatus,
                    'status_class' => $statusClass,
                    'expiry_date' => $product->prod_expiry ? \Carbon\Carbon::parse($product->prod_expiry)->format('M d, Y') : 'N/A',
                ];
            })
            ->values();
        
        // Calculate summary statistics
        $summary = [
            'total_products_in_services' => $serviceProducts->count(),
            'low_stock_count' => $serviceProducts->where('stock_status', 'low')->count(),
            'warning_stock_count' => $serviceProducts->where('stock_status', 'warning')->count(),
            'good_stock_count' => $serviceProducts->where('stock_status', 'good')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'products' => $serviceProducts,
            'summary' => $summary
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}
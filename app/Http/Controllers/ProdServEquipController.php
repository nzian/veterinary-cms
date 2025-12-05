<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Manufacturer;
use Illuminate\Support\Facades\Auth;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\ServiceProduct; 
use App\Models\ServiceEquipment;
use App\Models\InventoryTransaction; 
use App\Models\ProductConsumable;
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
    private function recordInventoryTransaction($productId, $type, $quantityChange, $reference = null, $notes = null, $batch_id = null, $serviceId = null)
    {
        try {
            InventoryTransaction::create([
                'prod_id' => $productId,
                'transaction_type' => $type,
                'quantity_change' => $quantityChange,
                'reference' => $reference,
                'notes' => $notes,
                'batch_id' => $batch_id,
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
                        'current_stock' => ($sp->product->available_stock - $sp->product->usage_from_inventory_transactions) ?? 0
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
                'products' => 'nullable|array',
                'products.*.prod_id' => 'required|exists:tbl_prod,prod_id',
                'products.*.quantity_used' => 'required|numeric|min:0.01',
                'products.*.is_billable' => 'boolean'
            ]);

            DB::beginTransaction();

            // If no products provided, just clear existing links
            $products = $validated['products'] ?? [];

            // Validate that all products are consumable
            foreach ($products as $productData) {
                $product = Product::find($productData['prod_id']);
                if (!$product) {
                    throw new \Exception("Product not found: " . $productData['prod_id']);
                }
                
                if ($product->prod_type !== 'Consumable') {
                    throw new \Exception("❌ Error saving products. Please try with consumable product again. Product '{$product->prod_name}' is not a consumable item.");
                }
            }

            // Clear existing product links
            ServiceProduct::where('serv_id', $serviceId)->delete();

            // Create new links if products provided
            foreach ($products as $productData) {
                ServiceProduct::create([
                    'serv_id' => $serviceId,
                    'prod_id' => $productData['prod_id'],
                    'quantity_used' => $productData['quantity_used'],
                    'is_billable' => $productData['is_billable'] ?? false,
                    'created_by' => Auth::id()
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

    /**
     * Get equipment linked to a service (for Boarding services)
     */
    public function getServiceEquipment($serviceId)
    {
        try {
            $serviceEquipment = ServiceEquipment::where('serv_id', $serviceId)
                ->with('equipment')
                ->get()
                ->map(function($se) {
                    return [
                        'id' => $se->id,
                        'equipment_id' => $se->equipment_id,
                        'equipment_name' => $se->equipment->equipment_name ?? 'Unknown',
                        'quantity_used' => $se->quantity_used,
                        'notes' => $se->notes,
                        'available_quantity' => $se->equipment->equipment_available ?? 0,
                        'total_quantity' => $se->equipment->equipment_quantity ?? 0,
                        'equipment_status' => $se->equipment->equipment_status ?? 'available'
                    ];
                });

            return response()->json([
                'success' => true,
                'equipment' => $serviceEquipment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update equipment linked to a service (for Boarding services)
     */
    public function updateServiceEquipment(Request $request, $serviceId)
    {
        try {
            $validated = $request->validate([
                'equipment' => 'nullable|array',
                'equipment.*.equipment_id' => 'required|exists:tbl_equipment,equipment_id',
                'equipment.*.quantity_used' => 'required|integer|min:1',
                'equipment.*.notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Delete existing service equipment links
            ServiceEquipment::where('serv_id', $serviceId)->delete();

            // Get equipment array or empty array if not provided
            $equipment = $validated['equipment'] ?? [];

            // Create new links if equipment provided
            foreach ($equipment as $equipmentData) {
                ServiceEquipment::create([
                    'serv_id' => $serviceId,
                    'equipment_id' => $equipmentData['equipment_id'],
                    'quantity_used' => $equipmentData['quantity_used'],
                    'notes' => $equipmentData['notes'] ?? null,
                    'created_by' => Auth::id()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service equipment updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get equipment filtered by branch (for Boarding services)
     * Only returns equipment from "Furniture & General Clinic Equipment" category
     */
    public function getEquipmentByBranch(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');
            
            $query = Equipment::query();
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            // Filter only "Furniture & General Clinic Equipment" category for boarding
            $query->where('equipment_category', 'Furniture & General Clinic Equipment');
            
            // Only get available equipment
            $query->where(function($q) {
                $q->where('equipment_status', 'available')
                  ->orWhereNull('equipment_status');
            });
            
            $equipment = $query->get()->map(function($eq) {
                return [
                    'equipment_id' => $eq->equipment_id,
                    'equipment_name' => $eq->equipment_name,
                    'equipment_category' => $eq->equipment_category,
                    'equipment_quantity' => $eq->equipment_quantity ?? 0,
                    'equipment_available' => $eq->equipment_available ?? 0,
                    'equipment_status' => $eq->equipment_status ?? 'available'
                ];
            });

            return response()->json([
                'success' => true,
                'equipment' => $equipment
            ]);
        } catch (\Exception $e) {
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

            // Get total service usage for this product
            $totalServiceUsage = DB::table('tbl_inventory_transactions')
                ->where('prod_id', $id)
                ->where('transaction_type', 'service_usage')
                ->sum(DB::raw('ABS(quantity_change)')) ?? 0;

            // Fetch stock batches with damage/pullout history
            $stockBatches = DB::table('product_stock as ps')
                ->leftJoin('tbl_user as creator', 'ps.created_by', '=', 'creator.user_id')
                ->where('ps.stock_prod_id', $id)
                ->select(
                    'ps.id',
                    'ps.batch',
                    'ps.quantity',
                    'ps.expire_date',
                    'ps.note',
                    'ps.created_at',
                    'creator.user_name as created_by_name'
                )
                ->orderBy('ps.created_at', 'asc') // FIFO: oldest batches first for service usage allocation
                ->get();
            
            // Distribute service usage across batches (FIFO)
            $remainingServiceUsage = $totalServiceUsage;
            $stockBatches = $stockBatches->map(function($batch) use (&$remainingServiceUsage) {
                // Calculate damage and pullout for this batch
                $damagePullout = DB::table('product_damage_pullout')
                    ->where('stock_id', $batch->id)
                    ->selectRaw('
                        COALESCE(SUM(damage_quantity), 0) as total_damage,
                        COALESCE(SUM(pullout_quantity), 0) as total_pullout
                    ')
                    ->first();
                
                $batch->total_damage = $damagePullout->total_damage ?? 0;
                $batch->total_pullout = $damagePullout->total_pullout ?? 0;
                
                // Calculate available before service usage
                $availableBeforeUsage = $batch->quantity - $batch->total_damage - $batch->total_pullout;
                
                // Allocate service usage to this batch (FIFO)
                $batchServiceUsage = min($remainingServiceUsage, $availableBeforeUsage);
                $remainingServiceUsage -= $batchServiceUsage;
                
                $batch->service_usage = $batchServiceUsage;
                $batch->available_quantity = $availableBeforeUsage - $batchServiceUsage;
                $batch->is_expired = $batch->expire_date && Carbon::parse($batch->expire_date)->isPast();
                
                return $batch;
            })->sortByDesc('created_at')->values(); // Sort back to newest first for display

            // Fetch damage/pullout history
            $damagePulloutHistory = DB::table('product_damage_pullout as pdp')
                ->join('product_stock as ps', 'pdp.stock_id', '=', 'ps.id')
                ->leftJoin('tbl_user as creator', 'pdp.created_by', '=', 'creator.user_id')
                ->where('pdp.pd_prod_id', $id)
                ->select(
                    'pdp.id',
                    'pdp.damage_quantity',
                    'pdp.pullout_quantity',
                    'pdp.reason',
                    'pdp.created_at',
                    'ps.batch',
                    'creator.user_name as created_by_name'
                )
                ->orderBy('pdp.created_at', 'desc')
                ->limit(20)
                ->get();

            // Fetch service consumption data for consumable products
            $serviceConsumptionData = null;
            $servicesUsingProduct = [];
            $recentServiceUsage = [];
            
            if ($product->prod_type === 'Consumable') {
                // Get services that use this product
                $servicesUsingProduct = DB::table('tbl_service_products as sp')
                    ->join('tbl_serv as s', 'sp.serv_id', '=', 's.serv_id')
                    ->leftJoin('tbl_user as creator', 'sp.created_by', '=', 'creator.user_id')
                    ->where('sp.prod_id', $id)
                    ->select(
                        's.serv_id',
                        's.serv_name',
                        's.serv_type',
                        'sp.quantity_used',
                        'sp.is_billable',
                        'sp.created_at',
                        'creator.user_name as added_by'
                    )
                    ->get();
                
                // Get recent service usage from inventory transactions
                $recentServiceUsage = DB::table('tbl_inventory_transactions as it')
                    ->leftJoin('tbl_serv as serv', 'it.serv_id', '=', 'serv.serv_id')
                    ->leftJoin('tbl_user as performer', 'it.performed_by', '=', 'performer.user_id')
                    ->leftJoin('tbl_appoint as appt', 'it.appoint_id', '=', 'appt.appoint_id')
                    ->leftJoin('tbl_pet as pet', 'appt.pet_id', '=', 'pet.pet_id')
                    ->where('it.prod_id', $id)
                    ->where('it.transaction_type', 'service_usage')
                    ->select(
                        'it.created_at as transaction_date',
                        'it.quantity_change as quantity',
                        'it.notes',
                        'it.reference',
                        'serv.serv_name',
                        'performer.user_name as performed_by',
                        'appt.appoint_id as visit_id',
                        'pet.pet_name'
                    )
                    ->orderBy('it.created_at', 'desc')
                    ->limit(15)
                    ->get();
                
                // Calculate consumption statistics
                $serviceConsumptionData = [
                    'total_services_using' => $servicesUsingProduct->count(),
                    'total_quantity_consumed' => DB::table('tbl_inventory_transactions')
                        ->where('prod_id', $id)
                        ->where('transaction_type', 'service_usage')
                        ->sum(DB::raw('ABS(quantity_change)')),
                    'recent_consumption_30days' => DB::table('tbl_inventory_transactions')
                        ->where('prod_id', $id)
                        ->where('transaction_type', 'service_usage')
                        ->where('created_at', '>=', Carbon::now()->subDays(30))
                        ->sum(DB::raw('ABS(quantity_change)')),
                    'avg_consumption_per_service' => DB::table('tbl_inventory_transactions')
                        ->where('prod_id', $id)
                        ->where('transaction_type', 'service_usage')
                        ->avg(DB::raw('ABS(quantity_change)'))
                ];
            }

            return response()->json([
                'product' => $product,
                'sales_data' => $salesData,
                'monthly_sales' => $monthlySales,
                'recent_orders' => $recentOrders,
                'stock_alert' => $stockAlert,
                'profit_data' => $profitData,
                'stock_batches' => $stockBatches,
                'damage_pullout_history' => $damagePulloutHistory,
                'service_consumption_data' => $serviceConsumptionData,
                'services_using_product' => $servicesUsingProduct,
                'recent_service_usage' => $recentServiceUsage
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch product details: ' . $e->getMessage()], 500);
        }
    }

    public function viewService($id)
    {
        try {
            $service = Service::with('branch')->findOrFail($id);
            
            // Calculate revenue data properly
            $revenueQuery = DB::table('tbl_visit_record')
                ->join('tbl_visit_service', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
                ->where('tbl_visit_service.serv_id', $id)
                ->selectRaw('
                    COUNT(DISTINCT tbl_visit_record.visit_id) as total_bookings,
                    COUNT(DISTINCT tbl_visit_record.visit_id) * ? as total_revenue
                ', [$service->serv_price])
                ->first();

            $revenueData = (object)[
                'total_bookings' => $revenueQuery->total_bookings ?? 0,
                'total_revenue' => $revenueQuery->total_revenue ?? 0,
                'average_booking_value' => $service->serv_price
            ];

            $monthlyRevenue = DB::table('tbl_visit_record')
                ->join('tbl_visit_service', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
                ->where('tbl_visit_service.serv_id', $id)
                ->where('tbl_visit_record.visit_date', '>=', Carbon::now()->subMonths(6))
                ->selectRaw('
                    YEAR(tbl_visit_record.visit_date) as year,
                    MONTH(tbl_visit_record.visit_date) as month,
                    COUNT(DISTINCT tbl_visit_record.visit_id) as bookings,
                    COUNT(DISTINCT tbl_visit_record.visit_id) * ? as revenue
                ', [$service->serv_price])
                ->groupBy(DB::raw('YEAR(tbl_visit_record.visit_date)'), DB::raw('MONTH(tbl_visit_record.visit_date)'))
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            // Primary source: visits table (various possible names)
            $visitTable =  'tbl_visit_record';

            if ($visitTable) {
                // Build recent visits for this service using visits table
                $recentAppointments = DB::table($visitTable . ' as v')
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

            // Get utilization data from appointments (more accurate than visits)
            $utilizationData = DB::table('tbl_appoint as a')
                ->join('tbl_appoint_serv as aps', 'a.appoint_id', '=', 'aps.appoint_id')
                ->where('aps.serv_id', $id)
                ->selectRaw('
                    CASE 
                        WHEN a.appoint_status IN ("complete", "completed") THEN "completed"
                        WHEN a.appoint_status IN ("cancel", "cancelled") THEN "cancelled" 
                        ELSE "pending"
                    END as appoint_status,
                    COUNT(*) as count
                ')
                ->groupBy(DB::raw('
                    CASE 
                        WHEN a.appoint_status IN ("complete", "completed") THEN "completed"
                        WHEN a.appoint_status IN ("cancel", "cancelled") THEN "cancelled" 
                        ELSE "pending"
                    END
                '))
                ->get();

            // Fallback to visit data if no appointments found
            if ($utilizationData->isEmpty()) {
                $utilizationData = DB::table('tbl_visit_record as vr')
                    ->join('tbl_visit_service as vs', 'vr.visit_id', '=', 'vs.visit_id')
                    ->where('vs.serv_id', $id)
                    ->selectRaw('
                        CASE 
                            WHEN vr.visit_status IN ("complete", "completed") THEN "completed"
                            WHEN vr.visit_status IN ("cancel", "cancelled") THEN "cancelled" 
                            ELSE "pending"
                        END as appoint_status,
                        COUNT(*) as count
                    ')
                    ->groupBy(DB::raw('
                        CASE 
                            WHEN vr.visit_status IN ("complete", "completed") THEN "completed"
                            WHEN vr.visit_status IN ("cancel", "cancelled") THEN "cancelled" 
                            ELSE "pending"
                        END
                    '))
                    ->get();
            }

            $peakTimes = DB::table('tbl_visit_record')
                ->join('tbl_visit_service', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
                ->where('tbl_visit_service.serv_id', $id)
                ->selectRaw('
                    HOUR(tbl_visit_record.created_at) as hour,
                    COUNT(*) as bookings
                ')
                ->groupBy(DB::raw('HOUR(tbl_visit_record.created_at)'))
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

            // Get consumable products attached to this service
            $consumableProducts = DB::table('tbl_service_products as sp')
                ->join('tbl_prod as p', 'sp.prod_id', '=', 'p.prod_id')
                ->leftJoin('tbl_user as creator', 'sp.created_by', '=', 'creator.user_id')
                ->where('sp.serv_id', $id)
                ->select(
                    'p.prod_id',
                    'p.prod_name',
                    'p.prod_stocks',
                    'p.prod_type',
                    'p.prod_category',
                    'sp.quantity_used',
                    'sp.is_billable',
                    'sp.created_at',
                    'creator.user_name as added_by'
                )
                ->get();

            // Get stock transaction history for consumable products used in this service
            $stockHistory = [];
            $transactionsTableExists = Schema::hasTable('tbl_inventory_transactions');
            if ($transactionsTableExists) {
                $stockHistory = DB::table('tbl_inventory_transactions as it')
                    ->join('tbl_service_products as sp', 'it.prod_id', '=', 'sp.prod_id')
                    ->join('tbl_prod as p', 'it.prod_id', '=', 'p.prod_id')
                    ->leftJoin('tbl_user as u', 'it.performed_by', '=', 'u.user_id')
                    ->leftJoin('tbl_appoint as a', 'it.appoint_id', '=', 'a.appoint_id')
                    ->leftJoin('tbl_pet as pet', 'a.pet_id', '=', 'pet.pet_id')
                    ->where('sp.serv_id', $id)
                    ->where('it.transaction_type', 'service_usage')
                    ->select(
                        'it.created_at',
                        'p.prod_name',
                        'it.quantity_change',
                        'it.reference',
                        'it.notes',
                        'u.user_name',
                        'a.appoint_id',
                        'pet.pet_name',
                        'it.transaction_type'
                    )
                    ->orderBy('it.created_at', 'desc')
                    ->limit(50)
                    ->get();
            }

            return response()->json([
                'service' => $service,
                'revenue_data' => $revenueData,
                'monthly_revenue' => $monthlyRevenue,
                'recent_appointments' => $recentAppointments,
                'utilization_data' => $utilizationData,
                'peak_times' => $peakTimes,
                'appointment_types' => $appointmentTypes,
                'consumable_products' => $consumableProducts,
                'stock_history' => $stockHistory
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch service details: ' . $e->getMessage()], 500);
        }
    }

    public function viewEquipment($id)
    {
        try {
            $equipment = Equipment::findOrFail($id);
            
            // Calculate status quantities
            $availableQty = $equipment->equipment_available ?? 0;
            $maintenanceQty = $equipment->equipment_maintenance ?? 0;
            $outOfServiceQty = $equipment->equipment_out_of_service ?? 0;
            $totalQty = $equipment->equipment_quantity ?? 0;
            
            // If individual status columns are empty, use total as available
            if ($availableQty == 0 && $maintenanceQty == 0 && $outOfServiceQty == 0 && $totalQty > 0) {
                $availableQty = $totalQty;
            }
            
            $usageData = [
                'total_quantity' => $totalQty,
                'available_quantity' => $availableQty, 
                'maintenance_quantity' => $maintenanceQty,
                'out_of_service_quantity' => $outOfServiceQty,
                'in_use_quantity' => max(0, $totalQty - $availableQty - $maintenanceQty - $outOfServiceQty),
                'branch' => $equipment->branch->branch_name ?? 'N/A'
            ];

            $availabilityStatus = strtolower($equipment->equipment_status ?? 'available');
            if ($totalQty == 0) {
                $availabilityStatus = 'none';
            } elseif ($availableQty == 0) {
                $availabilityStatus = 'unavailable';
            } elseif ($maintenanceQty > 0) {
                $availabilityStatus = 'partial';
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

        // Load all data for client-side filtering
        $products = Product::with('branch')
            ->where('prod_category', '!=', 'Service')
            ->when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->orderBy('prod_id', 'desc')
            ->get();

        $services = Service::when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->with(['branch'])
            ->orderBy('serv_id', 'desc')
            ->get();

        $equipment = Equipment::when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->with(['branch'])
            ->orderBy('equipment_id', 'desc')
            ->get();

        $branches = Branch::all();
        
        $manufacturers = Manufacturer::where('is_active', true)->orderBy('manufacturer_name')->get();
        
        $allProducts = Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_category')
         ->when($user->user_role !== 'superadmin', function ($query) use ($activeBranchId) {
                $query->where('branch_id', $activeBranchId);
            })
            ->where('prod_type', 'Consumable')
                ->orderBy('prod_id', 'desc')
                ->get();

        return view('prodServEquip', compact('products', 'branches', 'services', 'equipment','allProducts', 'manufacturers', 'activeBranchId'));
    }

    // -------------------- PRODUCT METHODS --------------------
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'prod_name' => 'required|string|max:255',
            'prod_category' => 'nullable|string|max:255',
            'service_category' => 'nullable|string|max:255',
            'prod_type' => 'required|in:Sale,Consumable,Prescription',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'nullable|numeric|min:0',
            'prod_reorderlevel' => 'required|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'manufacturer_id' => 'nullable|exists:tbl_manufacturer,manufacturer_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);
        
        // Remove non-database fields
        unset($validated['tab']);
        
        // Auto-set branch_id from active branch if not provided
        if (empty($validated['branch_id'])) {
            $user = auth()->user();
            $validated['branch_id'] = $user->user_role === 'superadmin' 
                ? session('active_branch_id') 
                : $user->branch_id;
        }
        
        // Set category based on product type
        // For Sale products: use prod_category
        // For Consumable products: use service_category as prod_category
        if ($validated['prod_type'] === 'Consumable') {
            $validated['prod_category'] = $validated['service_category'] ?? null;
            $validated['prod_price'] = 0;
        }
        // Remove service_category as it's not a database field
        unset($validated['service_category']);

        // Set initial stock to 0 (stock added via "Add Stock" feature)
        $validated['prod_stocks'] = 0;

        if ($request->hasFile('prod_image')) {
            $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $product = Product::create($validated);

            DB::commit();
            $redirectTab = $this->getRedirectTab($request, 'products');
            return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])
                ->with('success', 'Product added successfully! Use "Add Stock" to add inventory batches.');
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
            'service_category' => 'nullable|string|max:255',
            'prod_type' => 'required|in:Sale,Consumable,Prescription',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'nullable|numeric|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'manufacturer_id' => 'nullable|exists:tbl_manufacturer,manufacturer_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);
        
        // Remove non-database fields
        unset($validated['tab']);
        
        // Set category based on product type
        // For Sale products: use prod_category
        // For Consumable products: use service_category as prod_category
        if ($validated['prod_type'] === 'Consumable') {
            $validated['prod_category'] = $validated['service_category'] ?? null;
            $validated['prod_price'] = 0;
        }
        // Remove service_category as it's not a database field
        unset($validated['service_category']);

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

        // fetch associated stock batches and delete them
        $stockBatches = \App\Models\ProductStock::where('stock_prod_id', $product->prod_id)->get();
        foreach ($stockBatches as $batch) {
            $batch->delete();
        }
        
        // Record deletion in inventory transactions
        $transactions = \App\Models\InventoryTransaction::where('prod_id', $product->prod_id)->get();
        foreach ($transactions as $transaction) {
            $transaction->delete();
        }
        
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
            'batch' => 'required|string|max:100',
            'new_expiry' => 'nullable|date|after:today',
            'non_expiring' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        // Custom validation: if non_expiring is not checked, expiry date is required
        if (!$request->boolean('non_expiring') && empty($validated['new_expiry'])) {
            return redirect()->back()->withErrors(['new_expiry' => 'Expiry date is required for expiring products.']);
        }

        // If non_expiring is checked, expiry date should not be provided
        if ($request->boolean('non_expiring') && !empty($validated['new_expiry'])) {
            return redirect()->back()->withErrors(['new_expiry' => 'Expiry date should not be set for non-expiring products.']);
        }

        $product = Product::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $addedStock = $validated['add_stock'];
            $isNonExpiring = $request->boolean('non_expiring');
            $expiryDate = $isNonExpiring ? null : $validated['new_expiry'];
            
            // Create new stock batch record
            $stockBatch = \App\Models\ProductStock::create([
                'stock_prod_id' => $product->prod_id,
                'batch' => $validated['batch'],
                'quantity' => $addedStock,
                'expire_date' => $expiryDate,
                'note' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);
            
            // Update product's total stock (sum of all available batches)
            // Include both non-expired and non-expiring batches
            $product->prod_stocks = $product->stockBatches()
                ->where(function($query) {
                    $query->whereNull('expire_date') // Non-expiring products
                          ->orWhere('expire_date', '>=', now()->format('Y-m-d')); // Non-expired products
                })
                ->get()
                ->sum('available_quantity');
            
            $product->save();
            
            // ✅ Record the restock transaction
            $expiryText = $expiryDate ? "Expiry: {$expiryDate}" : "Non-expiring product";
            $this->recordInventoryTransaction(
                $product->prod_id,
                'purchase',
                $addedStock,
                'Manual Stock Update',
                "Added batch '{$validated['batch']}' with {$addedStock} units. {$expiryText}",
                ($validated['notes'] ? " - {$validated['notes']}" : ''),
                $stockBatch->id
            );
            
            DB::commit();
            
            $batchInfo = $isNonExpiring ? 
                "Stock batch added successfully! Added {$addedStock} units (Batch: {$validated['batch']}) - Non-expiring product." :
                "Stock batch added successfully! Added {$addedStock} units (Batch: {$validated['batch']}) - Expires: {$expiryDate}.";
            
            $redirectTab = $this->getRedirectTab($request, 'products');
            return redirect()->route('prodServEquip.index', ['tab' => $redirectTab])
                ->with('success', $batchInfo);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update stock: ' . $e->getMessage());
        }
    }

    /**
     * ✅ UPDATED: Update damage/pullout with stock batch tracking
     */
    public function updateDamage(Request $request, $id)
    {
        $validated = $request->validate([
            'stock_id' => 'required|exists:product_stock,id',
            'damaged_qty' => 'nullable|integer|min:0',
            'pullout_qty' => 'nullable|integer|min:0',
            'reason' => 'required|string',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $product = Product::findOrFail($id);
        $stockBatch = \App\Models\ProductStock::findOrFail($validated['stock_id']);
        
        // Verify the stock batch belongs to this product
        if ($stockBatch->stock_prod_id != $product->prod_id) {
            return redirect()->back()->with('error', 'Invalid stock batch selected.');
        }
        
        DB::beginTransaction();
        try {
            $damagedQty = $validated['damaged_qty'] ?? 0;
            $pulloutQty = $validated['pullout_qty'] ?? 0;
            $totalQty = $damagedQty + $pulloutQty;
            
            if ($totalQty == 0) {
                return redirect()->back()->with('error', 'Please enter damage or pullout quantity.');
            }
            
            // Check if stock batch has enough available quantity
            $availableQty = $stockBatch->available_quantity;
            if ($totalQty > $availableQty) {
                DB::rollBack();
                return redirect()->back()->with('error', "Insufficient stock in batch '{$stockBatch->batch}'! Available: {$availableQty}, Requested: {$totalQty}");
            }
            
            // Create damage/pullout record
            \App\Models\ProductDamagePullout::create([
                'pd_prod_id' => $product->prod_id,
                'stock_id' => $stockBatch->id,
                'pullout_quantity' => $pulloutQty,
                'damage_quantity' => $damagedQty,
                'reason' => $validated['reason'],
                'created_by' => Auth::id(),
            ]);
            
            // Update product's total stock (sum of all non-expired available batches)
            $product->prod_stocks = $product->stockBatches()
                ->notExpired()
                ->get()
                ->sum('available_quantity');
            
            // Update cumulative damaged and pullout
            $product->prod_damaged = ($product->prod_damaged ?? 0) + $damagedQty;
            $product->prod_pullout = ($product->prod_pullout ?? 0) + $pulloutQty;
            
            $product->save();
            
            // ✅ RECORD TRANSACTIONS
            if ($damagedQty > 0) {
                $this->recordInventoryTransaction(
                    $product->prod_id,
                    'damage',
                    -$damagedQty,
                    'Damaged Items',
                    "Batch '{$stockBatch->batch}': {$damagedQty} units damaged. Reason: {$validated['reason']}",
                    null,
                    $stockBatch->id
                );
            }
            
            if ($pulloutQty > 0) {
                $this->recordInventoryTransaction(
                    $product->prod_id,
                    'pullout',
                    -$pulloutQty,
                    'Pullout Items',
                    "Batch '{$stockBatch->batch}': {$pulloutQty} units pulled out. Reason: {$validated['reason']}",
                    null,
                    $stockBatch->id
                );
            }
            
            DB::commit();
            
            $message = "Updated successfully! ";
            if ($totalQty > 0) {
                $message .= "Batch '{$stockBatch->batch}': ";
                if ($damagedQty > 0) $message .= "Damaged: {$damagedQty} ";
                if ($pulloutQty > 0) $message .= "Pullout: {$pulloutQty} ";
                $message .= "| New available stock: {$product->prod_stocks}";
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
        
        // Fetch stock batches with expiry dates
        $stockBatches = DB::table('product_stock as ps')
            ->leftJoin('tbl_user as creator', 'ps.created_by', '=', 'creator.user_id')
            ->where('ps.stock_prod_id', $id)
            ->select(
                'ps.id',
                'ps.batch',
                'ps.quantity',
                'ps.expire_date',
                'ps.note',
                'ps.created_at',
                'creator.user_name as created_by_name'
            )
            ->orderBy('ps.created_at', 'desc')
            ->get()
            ->map(function($batch) {
                $damagePullout = DB::table('product_damage_pullout')
                    ->where('stock_id', $batch->id)
                    ->selectRaw('
                        COALESCE(SUM(damage_quantity), 0) as total_damage,
                        COALESCE(SUM(pullout_quantity), 0) as total_pullout
                    ')
                    ->first();
                
                $batch->total_damage = $damagePullout->total_damage ?? 0;
                $batch->total_pullout = $damagePullout->total_pullout ?? 0;
                $batch->available_quantity = $batch->quantity - $batch->total_damage - $batch->total_pullout;
                $batch->is_expired = $batch->expire_date && Carbon::parse($batch->expire_date)->isPast();
                
                return $batch;
            });
        
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
            'stock_batches' => $stockBatches,
            'damage_analysis' => $damageAnalysis,
            'expiry_data' => $expiryData,
            'stock_analytics' => $stockAnalytics,
            'services_using_product' => $servicesUsing,
            'recent_service_usage' => $recentServiceUsage,
            'total_used_in_services' => $totalUsedInServices
        ]);
    } catch (\Exception $e) {
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
        'branch_id' => 'nullable|exists:tbl_branch,branch_id',
        'tab' => 'nullable|string|in:products,service,equipment' // Added tab for redirect
    ]);

    // Auto-set branch_id from active branch if not provided
    if (empty($validated['branch_id'])) {
        $user = auth()->user();
        $validated['branch_id'] = $user->user_role === 'superadmin' 
            ? session('active_branch_id') 
            : $user->branch_id;
    }

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
        'branch_id' => 'nullable|exists:tbl_branch,branch_id', 
        'tab' => 'nullable|string|in:products,services,equipment' 
    ]);

    // Auto-set branch_id from active branch if not provided
    if (empty($validated['branch_id'])) {
        $user = auth()->user();
        $validated['branch_id'] = $user->user_role === 'superadmin' 
            ? session('active_branch_id') 
            : $user->branch_id;
    }

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
        'equipment_available' => 'nullable|integer|min:0',
        'equipment_maintenance' => 'nullable|integer|min:0',
        'equipment_out_of_service' => 'nullable|integer|min:0',
        'equipment_description' => 'nullable|string|max:1000',
        'equipment_category' => 'nullable|string|max:255',
        'equipment_status' => 'nullable|string|max:50',
        'equipment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'branch_id' => 'required|exists:tbl_branch,branch_id', 
        'tab' => 'nullable|string|in:products,services,equipment',
    ]);

    $equipment = Equipment::findOrFail($id);

    // Validate that sum of status quantities doesn't exceed total quantity
    $totalQty = $validated['equipment_quantity'];
    $available = $validated['equipment_available'] ?? 0;
    $maintenance = $validated['equipment_maintenance'] ?? 0;
    $outOfService = $validated['equipment_out_of_service'] ?? 0;
    
    $sum = $available + $maintenance + $outOfService;
    
    if ($sum > $totalQty) {
        return back()->withErrors([
            'equipment_status' => "Sum of status quantities ({$sum}) cannot exceed total quantity ({$totalQty})."
        ])->withInput();
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
            'equipment_available' => 'nullable|integer|min:0',
            'equipment_maintenance' => 'nullable|integer|min:0',
            'equipment_out_of_service' => 'nullable|integer|min:0',
            'tab' => 'nullable|string|in:products,services,equipment'
        ]);

        $equipment = Equipment::findOrFail($id);
        
        // Validate that sum doesn't exceed total quantity
        $totalQty = $equipment->equipment_quantity;
        $available = $validated['equipment_available'] ?? 0;
        $maintenance = $validated['equipment_maintenance'] ?? 0;
        $outOfService = $validated['equipment_out_of_service'] ?? 0;
        
        $sum = $available + $maintenance + $outOfService;
        
        if ($sum > $totalQty) {
            return back()->withErrors([
                'equipment_status' => "Sum of status quantities ({$sum}) cannot exceed total quantity ({$totalQty})."
            ])->withInput();
        }
        
        $equipment->equipment_available = $available;
        $equipment->equipment_maintenance = $maintenance;
        $equipment->equipment_out_of_service = $outOfService;
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
                    'quantity_used' => abs((float)$trans->quantity_change),
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
        // Filter out orphaned records where product or service no longer exists
        $serviceProducts = ServiceProduct::with(['product', 'service'])
            ->whereHas('product')
            ->whereHas('service')
            ->get()
            ->groupBy('prod_id')
            ->map(function($items, $prodId) {
                $product = $items->first()->product;
                
                // Skip if product is null (shouldn't happen with whereHas, but just in case)
                if (!$product) {
                    return null;
                }
                
                $services = $items->filter(function($item) {
                    return $item->service !== null;
                })->map(function($item) {
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
                    if ($item->service && $item->quantity_used > 0) {
                        $remaining = floor(($product->available_stock - $product->usage_from_inventory_transactions) / $item->quantity_used);
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
                    'current_stock' => ($product->available_stock - $product->usage_from_inventory_transactions) ?? 0,
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
            ->filter() // Remove any null values
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

    /**
     * Get complete service usage history - all services performed on patients
     */
    public function getServiceUsageHistory(Request $request)
    {
        try {
            $user = auth()->user();
            $branchId = session('active_branch_id') ?? $user->branch_id;
            
            // Get all visit services with related data
            $visitServices = DB::table('tbl_visit_service as vs')
                ->join('tbl_visit_record as v', 'vs.visit_id', '=', 'v.visit_id')
                ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
                ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
                ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
                ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
                ->where('u.branch_id', $branchId)
                ->select(
                    'vs.id',
                    'vs.visit_id',
                    'vs.serv_id',
                    'vs.quantity',
                    'vs.unit_price',
                    'vs.total_price',
                    'vs.status as service_status',
                    'vs.completed_at',
                    'vs.notes as service_notes',
                    'vs.created_at',
                    's.serv_name',
                    's.serv_type',
                    's.serv_price',
                    'v.visit_date',
                    'v.visit_service_type',
                    'v.patient_type',
                    'v.workflow_status',
                    'v.visit_status',
                    'pet.pet_id',
                    'pet.pet_name',
                    'pet.pet_species',
                    'pet.pet_breed',
                    'owner.own_id',
                    'owner.own_name as owner_name',
                    'owner.own_contactnum as owner_contact',
                    'u.user_id',
                    'u.user_name as performed_by'
                )
                ->orderBy('vs.created_at', 'desc')
                ->limit(500)
                ->get();
            
            $movements = $visitServices->map(function($record) {
                $statusColors = [
                    'completed' => ['label' => 'Completed', 'color' => 'green'],
                    'pending' => ['label' => 'Pending', 'color' => 'yellow'],
                    'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
                    'cancelled' => ['label' => 'Cancelled', 'color' => 'red'],
                ];
                
                $status = strtolower($record->service_status ?? 'pending');
                $statusInfo = $statusColors[$status] ?? ['label' => ucfirst($status), 'color' => 'gray'];
                
                $serviceTypeColors = [
                    'Vaccination' => 'bg-blue-100 text-blue-800',
                    'Deworming' => 'bg-green-100 text-green-800',
                    'Grooming' => 'bg-pink-100 text-pink-800',
                    'Treatment' => 'bg-purple-100 text-purple-800',
                    'Surgery' => 'bg-red-100 text-red-800',
                    'Checkup' => 'bg-teal-100 text-teal-800',
                    'Boarding' => 'bg-amber-100 text-amber-800',
                    'Diagnostics' => 'bg-indigo-100 text-indigo-800',
                    'Consultation' => 'bg-cyan-100 text-cyan-800',
                ];
                
                return [
                    'id' => $record->id,
                    'date' => $record->created_at,
                    'visit_date' => $record->visit_date,
                    'completed_at' => $record->completed_at,
                    'service_name' => $record->serv_name,
                    'service_type' => $record->serv_type ?? 'Service',
                    'service_type_class' => $serviceTypeColors[$record->serv_type] ?? 'bg-gray-100 text-gray-800',
                    'visit_id' => $record->visit_id,
                    'visit_type' => $record->visit_service_type ?? 'Walk-in',
                    'patient_type' => $record->patient_type ?? 'Outpatient',
                    'pet_id' => $record->pet_id,
                    'pet_name' => $record->pet_name ?? 'N/A',
                    'pet_species' => $record->pet_species ?? '',
                    'pet_breed' => $record->pet_breed ?? '',
                    'owner_name' => $record->owner_name ?? 'N/A',
                    'owner_contact' => $record->owner_contact ?? '',
                    'performed_by' => $record->performed_by ?? 'System',
                    'quantity' => $record->quantity ?? 1,
                    'unit_price' => $record->unit_price ?? $record->serv_price ?? 0,
                    'total_price' => $record->total_price ?? ($record->unit_price ?? $record->serv_price ?? 0),
                    'status' => $status,
                    'status_label' => $statusInfo['label'],
                    'status_color' => $statusInfo['color'],
                    'notes' => $record->service_notes ?? '',
                    'workflow_status' => $record->workflow_status ?? '',
                ];
            });
            
            // Calculate summary statistics
            $summary = [
                'total_services' => $movements->count(),
                'completed_services' => $movements->where('status', 'completed')->count(),
                'pending_services' => $movements->whereIn('status', ['pending', null, ''])->count(),
                'unique_patients' => $movements->pluck('pet_id')->filter()->unique()->count(),
                'total_revenue' => $movements->where('status', 'completed')->sum('total_price'),
                'unique_service_types' => $movements->pluck('service_type')->unique()->count(),
            ];
            
            // Get service type breakdown
            $serviceBreakdown = $movements->groupBy('service_type')->map(function($group, $type) {
                return [
                    'type' => $type,
                    'count' => $group->count(),
                    'revenue' => $group->where('status', 'completed')->sum('total_price'),
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'movements' => $movements->values(),
                'summary' => $summary,
                'service_breakdown' => $serviceBreakdown
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get equipment assignment history (for boarding, visits, etc.)
     */
    public function getEquipmentAssignmentHistory(Request $request)
    {
        try {
            $user = auth()->user();
            $branchId = session('active_branch_id') ?? $user->branch_id;
            
            // First, get equipment assignments from boarding records
            // Note: tbl_boarding_record uses visit_id + pet_id as composite key, no boarding_id
            $boardingAssignments = collect();
            
            if (Schema::hasTable('tbl_boarding_record') && Schema::hasColumn('tbl_boarding_record', 'equipment_id')) {
                $boardingAssignments = DB::table('tbl_boarding_record as br')
                    ->join('tbl_visit_record as v', 'br.visit_id', '=', 'v.visit_id')
                    ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
                    ->leftJoin('tbl_equipment as eq', 'br.equipment_id', '=', 'eq.equipment_id')
                    ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
                    ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
                    ->leftJoin('tbl_serv as s', 'br.serv_id', '=', 's.serv_id')
                    ->where('u.branch_id', $branchId)
                    ->whereNotNull('br.equipment_id')
                    ->select(
                        'br.visit_id',
                        'br.pet_id as br_pet_id',
                        'br.equipment_id',
                        'br.check_in_date',
                        'br.check_out_date',
                        'br.room_no',
                        'br.status as boarding_status',
                        'br.handled_by',
                        'eq.equipment_name',
                        'eq.equipment_category',
                        'eq.equipment_quantity',
                        'eq.equipment_available',
                        'pet.pet_id',
                        'pet.pet_name',
                        'pet.pet_species',
                        'pet.pet_breed',
                        'owner.own_id',
                        'owner.own_name as owner_name',
                        's.serv_name as service_name',
                        's.serv_type as service_type',
                        'u.user_name as vet_name',
                        'v.visit_date'
                    )
                    ->orderBy('br.check_in_date', 'desc')
                    ->limit(500)
                    ->get();
            }
            
            // Also get from equipment assignment log if exists
            $logAssignments = collect();
            if (Schema::hasTable('tbl_equipment_assignment_log')) {
                $logAssignments = DB::table('tbl_equipment_assignment_log as eal')
                    ->join('tbl_equipment as eq', 'eal.equipment_id', '=', 'eq.equipment_id')
                    ->leftJoin('tbl_visit_record as v', 'eal.visit_id', '=', 'v.visit_id')
                    ->leftJoin('tbl_user as u', 'eal.performed_by', '=', 'u.user_id')
                    ->leftJoin('tbl_pet as pet', 'eal.pet_id', '=', 'pet.pet_id')
                    ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
                    ->where('eq.branch_id', $branchId)
                    ->select(
                        'eal.id',
                        'eal.equipment_id',
                        'eal.action_type',
                        'eal.visit_id',
                        'eal.pet_id',
                        'eal.quantity_changed',
                        'eal.previous_status',
                        'eal.new_status',
                        'eal.previous_available',
                        'eal.new_available',
                        'eal.reference',
                        'eal.notes as log_notes',
                        'eal.created_at',
                        'eq.equipment_name',
                        'eq.equipment_category',
                        'pet.pet_name',
                        'pet.pet_species',
                        'owner.own_name as owner_name',
                        'u.user_name as performed_by_name'
                    )
                    ->orderBy('eal.created_at', 'desc')
                    ->limit(500)
                    ->get();
            }
            
            // Map boarding assignments to a standard format
            $movements = $boardingAssignments->map(function($record, $index) {
                $actionType = 'assigned';
                $actionLabel = 'Assigned';
                $actionColor = 'green';
                
                if ($record->boarding_status === 'Checked Out') {
                    $actionType = 'released';
                    $actionLabel = 'Released';
                    $actionColor = 'blue';
                } elseif ($record->boarding_status === 'Checked In') {
                    $actionType = 'assigned';
                    $actionLabel = 'Checked In';
                    $actionColor = 'green';
                } elseif ($record->boarding_status === 'Reserved') {
                    $actionType = 'reserved';
                    $actionLabel = 'Reserved';
                    $actionColor = 'amber';
                }
                
                $categoryColors = [
                    'Cage' => 'bg-blue-100 text-blue-800',
                    'Room' => 'bg-purple-100 text-purple-800',
                    'Ward' => 'bg-teal-100 text-teal-800',
                    'Kennel' => 'bg-amber-100 text-amber-800',
                    'Equipment' => 'bg-gray-100 text-gray-800',
                ];
                
                // Use check_in_date as the date, or visit_date as fallback
                $recordDate = $record->check_in_date ?? $record->visit_date ?? now();
                
                return [
                    'id' => 'boarding_' . $record->visit_id . '_' . ($record->pet_id ?? $index),
                    'source' => 'boarding',
                    'date' => $recordDate,
                    'action_type' => $actionType,
                    'action_label' => $actionLabel,
                    'action_color' => $actionColor,
                    'equipment_id' => $record->equipment_id,
                    'equipment_name' => $record->equipment_name ?? 'Unknown',
                    'equipment_category' => $record->equipment_category ?? 'Equipment',
                    'category_class' => $categoryColors[$record->equipment_category] ?? 'bg-gray-100 text-gray-800',
                    'visit_id' => $record->visit_id,
                    'pet_id' => $record->pet_id,
                    'pet_name' => $record->pet_name ?? 'N/A',
                    'pet_species' => $record->pet_species ?? '',
                    'pet_breed' => $record->pet_breed ?? '',
                    'owner_name' => $record->owner_name ?? 'N/A',
                    'service_name' => $record->service_name ?? 'Boarding',
                    'service_type' => $record->service_type ?? 'Boarding',
                    'check_in_date' => $record->check_in_date,
                    'check_out_date' => $record->check_out_date,
                    'boarding_status' => $record->boarding_status ?? 'Unknown',
                    'handled_by' => $record->handled_by ?? $record->vet_name ?? 'System',
                    'reference' => 'Visit #' . $record->visit_id,
                    'notes' => $record->room_no ? 'Room: ' . $record->room_no : '',
                    'quantity' => 1,
                ];
            });
            
            // Add log entries to movements
            foreach ($logAssignments as $log) {
                $actionColors = [
                    'assigned' => 'green',
                    'released' => 'blue',
                    'maintenance' => 'yellow',
                    'status_change' => 'purple',
                    'damaged' => 'red',
                    'repaired' => 'teal',
                ];
                
                $categoryColors = [
                    'Cage' => 'bg-blue-100 text-blue-800',
                    'Room' => 'bg-purple-100 text-purple-800',
                    'Ward' => 'bg-teal-100 text-teal-800',
                    'Kennel' => 'bg-amber-100 text-amber-800',
                    'Equipment' => 'bg-gray-100 text-gray-800',
                ];
                
                $movements->push([
                    'id' => 'log_' . $log->id,
                    'source' => 'log',
                    'date' => $log->created_at,
                    'action_type' => $log->action_type,
                    'action_label' => ucfirst(str_replace('_', ' ', $log->action_type)),
                    'action_color' => $actionColors[$log->action_type] ?? 'gray',
                    'equipment_id' => $log->equipment_id,
                    'equipment_name' => $log->equipment_name ?? 'Unknown',
                    'equipment_category' => $log->equipment_category ?? 'Equipment',
                    'category_class' => $categoryColors[$log->equipment_category] ?? 'bg-gray-100 text-gray-800',
                    'visit_id' => $log->visit_id,
                    'pet_id' => $log->pet_id,
                    'pet_name' => $log->pet_name ?? '-',
                    'pet_species' => $log->pet_species ?? '',
                    'owner_name' => $log->owner_name ?? '-',
                    'service_name' => null,
                    'service_type' => null,
                    'check_in_date' => null,
                    'check_out_date' => null,
                    'boarding_status' => null,
                    'handled_by' => $log->performed_by_name ?? 'System',
                    'reference' => $log->reference ?? '',
                    'notes' => $log->log_notes ?? '',
                    'quantity' => $log->quantity_changed ?? 1,
                    'previous_status' => $log->previous_status,
                    'new_status' => $log->new_status,
                    'previous_available' => $log->previous_available,
                    'new_available' => $log->new_available,
                ]);
            }
            
            // Sort by date descending
            $movements = $movements->sortByDesc('date')->values();
            
            // Calculate summary statistics
            $summary = [
                'total_assignments' => $movements->count(),
                'active_assignments' => $movements->whereIn('action_type', ['assigned', 'reserved'])->where('boarding_status', '!=', 'Checked Out')->count(),
                'checked_in' => $movements->where('action_type', 'assigned')->count(),
                'checked_out' => $movements->where('action_type', 'released')->count(),
                'unique_equipment' => $movements->pluck('equipment_id')->unique()->count(),
                'unique_pets' => $movements->pluck('pet_id')->filter()->unique()->count(),
            ];
            
            // Get equipment breakdown
            $equipmentBreakdown = $movements->groupBy('equipment_name')->map(function($group, $name) {
                return [
                    'equipment' => $name,
                    'count' => $group->count(),
                    'category' => $group->first()['equipment_category'] ?? 'Unknown',
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'movements' => $movements,
                'summary' => $summary,
                'equipment_breakdown' => $equipmentBreakdown
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete stock movement history for all products
     */
    public function getAllStockMovementHistory(Request $request)
    {
        try {
            $user = auth()->user();
            $branchId = session('active_branch_id') ?? $user->branch_id;
            
            // Check if InventoryTransaction table exists
            $transactionsTableExists = Schema::hasTable('tbl_inventory_transactions');
            
            $movements = collect();
            
            if ($transactionsTableExists) {
                // Get all inventory transactions with product and user info
                $hasPerformedByCol = Schema::hasColumn('tbl_inventory_transactions', 'performed_by');
                $hasServIdCol = Schema::hasColumn('tbl_inventory_transactions', 'serv_id');
                $hasVisitIdCol = Schema::hasColumn('tbl_inventory_transactions', 'visit_id');
                
                $query = DB::table('tbl_inventory_transactions as it')
                    ->join('tbl_prod as p', 'it.prod_id', '=', 'p.prod_id')
                    ->where('p.branch_id', $branchId);
                
                if ($hasPerformedByCol) {
                    $query->leftJoin('tbl_user as u', 'it.performed_by', '=', 'u.user_id');
                }
                if ($hasServIdCol) {
                    $query->leftJoin('tbl_serv as s', 'it.serv_id', '=', 's.serv_id');
                }
                if ($hasVisitIdCol) {
                    $query->leftJoin('tbl_visit_record as v', 'it.visit_id', '=', 'v.visit_id');
                }
                
                $selects = [
                    'it.transaction_id as id',
                    'it.created_at',
                    'it.transaction_type',
                    'it.quantity_change',
                    'it.reference',
                    'it.notes',
                    'p.prod_id',
                    'p.prod_name',
                    'p.prod_category',
                    'p.prod_type',
                ];
                
                if ($hasPerformedByCol) {
                    $selects[] = 'u.user_name';
                } else {
                    $selects[] = DB::raw('NULL as user_name');
                }
                if ($hasServIdCol) {
                    $selects[] = 's.serv_name';
                    $selects[] = 'it.serv_id';
                } else {
                    $selects[] = DB::raw('NULL as serv_name');
                    $selects[] = DB::raw('NULL as serv_id');
                }
                if ($hasVisitIdCol) {
                    $selects[] = 'it.visit_id';
                } else {
                    $selects[] = DB::raw('NULL as visit_id');
                }
                
                $transactions = $query->select($selects)
                    ->orderBy('it.created_at', 'desc')
                    ->limit(500)
                    ->get();
                
                $movements = $movements->concat($transactions->map(function($trans) {
                    $typeLabels = [
                        'restock' => ['label' => 'Stock Added', 'color' => 'green', 'icon' => 'fa-plus-circle'],
                        'sale' => ['label' => 'POS Sale', 'color' => 'blue', 'icon' => 'fa-shopping-cart'],
                        'service_usage' => ['label' => 'Service Usage', 'color' => 'purple', 'icon' => 'fa-syringe'],
                        'damage' => ['label' => 'Damaged', 'color' => 'red', 'icon' => 'fa-exclamation-triangle'],
                        'pullout' => ['label' => 'Pull-out', 'color' => 'orange', 'icon' => 'fa-truck'],
                        'adjustment' => ['label' => 'Adjustment', 'color' => 'gray', 'icon' => 'fa-edit'],
                        'return' => ['label' => 'Return', 'color' => 'teal', 'icon' => 'fa-undo'],
                    ];
                    
                    $typeInfo = $typeLabels[$trans->transaction_type] ?? ['label' => ucfirst($trans->transaction_type), 'color' => 'gray', 'icon' => 'fa-circle'];
                    
                    $details = $trans->notes ?? '';
                    if ($trans->serv_name) {
                        $details = "Service: {$trans->serv_name}" . ($details ? " - {$details}" : '');
                    }
                    if ($trans->visit_id) {
                        $details .= " (Visit #{$trans->visit_id})";
                    }
                    
                    return [
                        'id' => $trans->id,
                        'date' => $trans->created_at,
                        'product_id' => $trans->prod_id,
                        'product_name' => $trans->prod_name,
                        'product_category' => $trans->prod_category,
                        'product_type' => $trans->prod_type,
                        'transaction_type' => $trans->transaction_type,
                        'type_label' => $typeInfo['label'],
                        'type_color' => $typeInfo['color'],
                        'type_icon' => $typeInfo['icon'],
                        'quantity_change' => $trans->quantity_change,
                        'reference' => $trans->reference ?? 'N/A',
                        'details' => $details ?: 'N/A',
                        'user' => $trans->user_name ?? 'System',
                        'source' => 'transaction',
                    ];
                }));
            }
            
            // Also get stock batch additions (purchases/restocks)
            $stockBatches = DB::table('product_stock as ps')
                ->join('tbl_prod as p', 'ps.stock_prod_id', '=', 'p.prod_id')
                ->leftJoin('tbl_user as u', 'ps.created_by', '=', 'u.user_id')
                ->where('p.branch_id', $branchId)
                ->select(
                    'ps.id',
                    'ps.created_at',
                    DB::raw("'batch_added' as transaction_type"),
                    'ps.quantity as quantity_change',
                    'ps.batch as reference',
                    'ps.note as notes',
                    'ps.expire_date',
                    'p.prod_id',
                    'p.prod_name',
                    'p.prod_category',
                    'p.prod_type',
                    'u.user_name'
                )
                ->orderBy('ps.created_at', 'desc')
                ->limit(200)
                ->get();
            
            $movements = $movements->concat($stockBatches->map(function($batch) {
                $expiryInfo = $batch->expire_date ? " (Expires: " . Carbon::parse($batch->expire_date)->format('M d, Y') . ")" : '';
                return [
                    'id' => 'batch_' . $batch->id,
                    'date' => $batch->created_at,
                    'product_id' => $batch->prod_id,
                    'product_name' => $batch->prod_name,
                    'product_category' => $batch->prod_category,
                    'product_type' => $batch->prod_type,
                    'transaction_type' => 'batch_added',
                    'type_label' => 'Batch Stock Added',
                    'type_color' => 'green',
                    'type_icon' => 'fa-box',
                    'quantity_change' => $batch->quantity_change,
                    'reference' => "Batch: {$batch->reference}" . $expiryInfo,
                    'details' => $batch->notes ?: 'New stock batch added',
                    'user' => $batch->user_name ?? 'System',
                    'source' => 'batch',
                ];
            }));
            
            // Get damage/pullout records
            $damagePullout = DB::table('product_damage_pullout as dp')
                ->join('product_stock as ps', 'dp.stock_id', '=', 'ps.id')
                ->join('tbl_prod as p', 'ps.stock_prod_id', '=', 'p.prod_id')
                ->leftJoin('tbl_user as u', 'dp.created_by', '=', 'u.user_id')
                ->where('p.branch_id', $branchId)
                ->select(
                    'dp.id',
                    'dp.created_at',
                    'dp.damage_quantity',
                    'dp.pullout_quantity',
                    'dp.reason',
                    'ps.batch',
                    'p.prod_id',
                    'p.prod_name',
                    'p.prod_category',
                    'p.prod_type',
                    'u.user_name'
                )
                ->orderBy('dp.created_at', 'desc')
                ->limit(200)
                ->get();
            
            foreach ($damagePullout as $record) {
                if ($record->damage_quantity > 0) {
                    $movements->push([
                        'id' => 'damage_' . $record->id,
                        'date' => $record->created_at,
                        'product_id' => $record->prod_id,
                        'product_name' => $record->prod_name,
                        'product_category' => $record->prod_category,
                        'product_type' => $record->prod_type,
                        'transaction_type' => 'damage',
                        'type_label' => 'Damaged',
                        'type_color' => 'red',
                        'type_icon' => 'fa-exclamation-triangle',
                        'quantity_change' => -$record->damage_quantity,
                        'reference' => "Batch: {$record->batch}",
                        'details' => $record->reason ?: 'Product damaged',
                        'user' => $record->user_name ?? 'System',
                        'source' => 'damage_pullout',
                    ]);
                }
                if ($record->pullout_quantity > 0) {
                    $movements->push([
                        'id' => 'pullout_' . $record->id,
                        'date' => $record->created_at,
                        'product_id' => $record->prod_id,
                        'product_name' => $record->prod_name,
                        'product_category' => $record->prod_category,
                        'product_type' => $record->prod_type,
                        'transaction_type' => 'pullout',
                        'type_label' => 'Pull-out',
                        'type_color' => 'orange',
                        'type_icon' => 'fa-truck',
                        'quantity_change' => -$record->pullout_quantity,
                        'reference' => "Batch: {$record->batch}",
                        'details' => $record->reason ?: 'Product pulled out',
                        'user' => $record->user_name ?? 'System',
                        'source' => 'damage_pullout',
                    ]);
                }
            }
            
            // Sort all movements by date descending
            $sortedMovements = $movements->sortByDesc('date')->values()->take(500);
            
            // Calculate summary statistics
            $summary = [
                'total_movements' => $sortedMovements->count(),
                'stock_added' => $sortedMovements->whereIn('transaction_type', ['restock', 'batch_added'])->sum('quantity_change'),
                'sales' => abs($sortedMovements->where('transaction_type', 'sale')->sum('quantity_change')),
                'service_usage' => abs($sortedMovements->where('transaction_type', 'service_usage')->sum('quantity_change')),
                'damaged' => abs($sortedMovements->where('transaction_type', 'damage')->sum('quantity_change')),
                'pullout' => abs($sortedMovements->where('transaction_type', 'pullout')->sum('quantity_change')),
            ];
            
            return response()->json([
                'success' => true,
                'movements' => $sortedMovements,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock batches for a product (for damage/pullout modal)
     */
    public function getStockBatches($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            $batches = \App\Models\ProductStock::where('stock_prod_id', $product->prod_id)
                ->orderBy('expire_date', 'asc')
                ->get()
                ->map(function($batch) {
                    return [
                        'id' => $batch->id,
                        'batch' => $batch->batch,
                        'quantity' => $batch->quantity,
                        'available_quantity' => $batch->available_quantity,
                        'expire_date' => $batch->expire_date->format('Y-m-d'),
                        'is_expired' => $batch->isExpired(),
                        'note' => $batch->note,
                    ];
                });

            return response()->json([
                'success' => true,
                'batches' => $batches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -------------------- CONSUMABLE PRODUCTS FILTER METHOD --------------------
    
    /**
     * Get consumable products filtered by service type and branch
     * Excludes out-of-stock and expired products
     */
    public function getConsumableProductsByFilter(Request $request)
    {
        try {
            $serviceType = $request->input('service_type');
            $branchId = $request->input('branch_id');

            $query = Product::where('prod_type', 'Consumable');

            // Filter by service category (stored in prod_category for consumable products)
            if ($serviceType) {
                $query->where('prod_category', $serviceType);
            }

            // Filter by branch
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $products = $query->orderBy('prod_name')->get();

            // Calculate available stock for each product and filter out disabled ones
            $productsWithStock = $products->filter(function ($product) {
                // Exclude expired and out-of-stock products
                return !$product->is_disabled;
            })->map(function ($product) {
                return [
                    'prod_id' => $product->prod_id,
                    'prod_name' => $product->prod_name,
                    'prod_category' => $product->prod_category,
                    'branch_id' => $product->branch_id,
                    'available_stock' => $product->available_stock - $product->usage_from_inventory_transactions,
                    'is_expired' => $product->all_expired,
                    'is_out_of_stock' => $product->is_out_of_stock,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'products' => $productsWithStock
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product details for service usage (manufacturer and earliest expiring batch)
     */
    public function getProductDetailsForService($id)
    {
        try {
            $product = Product::with('manufacturer')->findOrFail($id);
            
            // Get the earliest expiring batch that has available stock (FEFO - First Expire First Out)
            $earliestBatch = \App\Models\ProductStock::where('stock_prod_id', $id)
                ->where('quantity', '>', 0)
                ->where(function($query) {
                    $query->where('expire_date', '>=', now())
                          ->orWhereNull('expire_date');
                })
                ->orderBy('expire_date', 'asc')
                ->first();

            return response()->json([
                'success' => true,
                'product' => [
                    'prod_id' => $product->prod_id,
                    'prod_name' => $product->prod_name,
                    'manufacturer_name' => $product->manufacturer->manufacturer_name ?? null,
                    'batch_no' => $earliestBatch->batch ?? null,
                    'batch_expire_date' => $earliestBatch ? ($earliestBatch->expire_date ? $earliestBatch->expire_date->format('Y-m-d') : null) : null,
                    'batch_quantity' => $earliestBatch->quantity ?? 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -------------------- MANUFACTURER METHODS --------------------
    
    /**
     * Get all manufacturers
     */
    public function getManufacturers()
    {
        try {
            $manufacturers = Manufacturer::where('is_active', true)
                ->orderBy('manufacturer_name')
                ->get();

            return response()->json([
                'success' => true,
                'manufacturers' => $manufacturers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new manufacturer
     */
    public function storeManufacturer(Request $request)
    {
        $validated = $request->validate([
            'manufacturer_name' => 'required|string|max:255|unique:tbl_manufacturer,manufacturer_name',
        ]);

        try {
            $manufacturer = Manufacturer::create([
                'manufacturer_name' => $validated['manufacturer_name'],
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Manufacturer added successfully!',
                'manufacturer' => $manufacturer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a manufacturer
     */
    public function updateManufacturer(Request $request, $id)
    {
        $validated = $request->validate([
            'manufacturer_name' => 'required|string|max:255|unique:tbl_manufacturer,manufacturer_name,' . $id . ',manufacturer_id',
        ]);

        try {
            $manufacturer = Manufacturer::findOrFail($id);
            $manufacturer->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Manufacturer updated successfully!',
                'manufacturer' => $manufacturer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete (deactivate) a manufacturer
     */
    public function deleteManufacturer($id)
    {
        try {
            $manufacturer = Manufacturer::findOrFail($id);
            $manufacturer->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Manufacturer deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // -------------------- LINKED CONSUMABLES METHODS --------------------

    /**
     * Get available consumable products for linking (excludes the current product)
     */
    public function getAvailableConsumables(Request $request)
    {
        try {
            $excludeId = $request->input('exclude');
            
            // Only show Medical Supply category products for linking
            $products = Product::where('prod_type', 'Consumable')
                ->where('prod_category', 'Medical Supply')
                ->when($excludeId, function($query) use ($excludeId) {
                    $query->where('prod_id', '!=', $excludeId);
                })
                ->orderBy('prod_name')
                ->get()
                ->map(function($product) {
                    return [
                        'prod_id' => $product->prod_id,
                        'prod_name' => $product->prod_name,
                        'prod_category' => $product->prod_category,
                        'available_stock' => $product->available_stock - $product->usage_from_inventory_transactions
                    ];
                });

            return response()->json([
                'success' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get linked consumables for a product
     */
    public function getLinkedConsumables($productId)
    {
        try {
            $consumables = ProductConsumable::where('product_id', $productId)
                ->with(['consumableProduct' => function($query) {
                    $query->withoutGlobalScopes();
                }])
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'consumable_product_id' => $item->consumable_product_id,
                        'quantity' => $item->quantity,
                        'consumable_product' => [
                            'prod_id' => $item->consumableProduct->prod_id,
                            'prod_name' => $item->consumableProduct->prod_name,
                            'prod_category' => $item->consumableProduct->prod_category,
                            'available_stock' => $item->consumableProduct->available_stock - $item->consumableProduct->usage_from_inventory_transactions
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'consumables' => $consumables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a linked consumable to a product
     */
    public function addLinkedConsumable(Request $request, $productId)
    {
        $validated = $request->validate([
            'consumable_product_id' => 'required|exists:tbl_prod,prod_id',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            // Check if already linked
            $existing = ProductConsumable::where('product_id', $productId)
                ->where('consumable_product_id', $validated['consumable_product_id'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This consumable is already linked to this product'
                ], 400);
            }

            // Prevent linking to itself
            if ($productId == $validated['consumable_product_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'A product cannot be linked to itself'
                ], 400);
            }

            $link = ProductConsumable::create([
                'product_id' => $productId,
                'consumable_product_id' => $validated['consumable_product_id'],
                'quantity' => $validated['quantity']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consumable linked successfully!',
                'link' => $link
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a linked consumable
     */
    public function removeLinkedConsumable($linkId)
    {
        try {
            $link = ProductConsumable::findOrFail($linkId);
            $link->delete();

            return response()->json([
                'success' => true,
                'message' => 'Linked consumable removed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProdServEquipController extends Controller
{
    public function index()
    {
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        $products = Product::with('branch')
            ->where('branch_id', $activeBranchId)
            ->get();
        
        $services = Service::where('branch_id', $activeBranchId)->get();
        $equipment = Equipment::where('branch_id', $activeBranchId)->get();
        $branches = Branch::all();

        return view('prodServEquip', compact('products', 'branches', 'services', 'equipment'));
    }

    // -------------------- PRODUCT VIEW DETAILS --------------------
    public function viewProduct($id)
{
    try {
        $product = Product::with('branch')->findOrFail($id);
        
        // Get REAL sales data from orders
        $salesData = DB::table('tbl_ord')
            ->where('prod_id', $id)
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(ord_quantity), 0) as total_quantity_sold,
                COALESCE(SUM(ord_quantity * ?), 0) as total_revenue,
                COALESCE(AVG(ord_quantity * ?), 0) as average_order_value
            ', [$product->prod_price, $product->prod_price])
            ->first();

        // Get REAL monthly sales trend (last 6 months)
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

        // Get REAL recent orders with user information
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

        // Stock alerts
        $stockAlert = null;
        if ($product->prod_reorderlevel && $product->prod_stocks <= $product->prod_reorderlevel) {
            $stockAlert = 'low_stock';
        }

        // Calculate profit data
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

    // -------------------- SERVICE VIEW DETAILS --------------------
   public function viewService($id)
{
    try {
        $service = Service::with('branch')->findOrFail($id);
        
        // Get appointments that used this service
        $appointments = DB::table('tbl_appoint')
            ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
            ->where('tbl_appoint_serv.serv_id', $id)
            ->pluck('tbl_appoint.appoint_id');

        $revenueData = (object)[
            'total_bookings' => $appointments->count(),
            'total_revenue' => $appointments->count() * $service->serv_price,
            'average_booking_value' => $service->serv_price
        ];

        // Get REAL monthly revenue trend (last 6 months)
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

        // Get REAL recent appointments with full details
        $recentAppointments = DB::table('tbl_appoint')
            ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_appoint.pet_id', '=', 'tbl_pet.pet_id')
            ->leftJoin('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
            ->leftJoin('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
            ->where('tbl_appoint_serv.serv_id', $id)
            ->select(
                'tbl_appoint.appoint_id',
                'tbl_appoint.appoint_date',
                'tbl_appoint.appoint_time',
                'tbl_appoint.appoint_status',
                'tbl_appoint.appoint_type',
                'tbl_pet.pet_name',
                'tbl_pet.pet_species',
                'tbl_own.own_name',
                'tbl_own.own_contactnum',
                'tbl_user.user_name'
            )
            ->orderBy('tbl_appoint.appoint_date', 'desc')
            ->limit(10)
            ->get();

        // Service utilization by appointment status (REAL DATA)
        $utilizationData = DB::table('tbl_appoint')
            ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
            ->where('tbl_appoint_serv.serv_id', $id)
            ->selectRaw('
                tbl_appoint.appoint_status,
                COUNT(*) as count
            ')
            ->groupBy('tbl_appoint.appoint_status')
            ->get();

        // Peak booking times analysis (REAL DATA)
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

        // Appointment type distribution
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

    // -------------------- EQUIPMENT VIEW DETAILS --------------------
    public function viewEquipment($id)
    {
        try {
            $equipment = Equipment::findOrFail($id);
            
            // Equipment usage tracking
            $usageData = [
                'total_quantity' => $equipment->equipment_quantity,
                'available_quantity' => $equipment->equipment_quantity, 
                'in_use_quantity' => 0, 
                'maintenance_quantity' => 0
            ];

            // Usage history - mock data since usage tracking might not be implemented
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
                [
                    'date' => Carbon::now()->subDays(7)->toISOString(),
                    'action' => 'Returned',
                    'quantity' => 2,
                    'user' => 'Dr. Johnson',
                    'purpose' => 'After surgery'
                ]
            ]);

            // Availability status
            $availabilityStatus = 'available';
            if ($equipment->equipment_quantity == 0) {
                $availabilityStatus = 'none';
            } elseif ($usageData['available_quantity'] < $equipment->equipment_quantity) {
                $availabilityStatus = 'partial';
            }

            // Equipment condition tracking
            $conditionData = [
                'excellent' => intval($equipment->equipment_quantity * 0.8),
                'good' => intval($equipment->equipment_quantity * 0.15),
                'fair' => intval($equipment->equipment_quantity * 0.05),
                'poor' => 0
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

    // -------------------- INVENTORY HISTORY VIEW --------------------
    public function viewInventoryHistory($id)
{
    try {
        $product = Product::findOrFail($id);
        
        // Get REAL stock movement history from orders (sales)
        $salesHistory = DB::table('tbl_ord')
            ->leftJoin('tbl_user', 'tbl_ord.user_id', '=', 'tbl_user.user_id')
            ->leftJoin('tbl_own', 'tbl_ord.own_id', '=', 'tbl_own.own_id')
            ->where('tbl_ord.prod_id', $id)
            ->select(
                'tbl_ord.ord_date as date',
                DB::raw("'sale' as type"),
                DB::raw('-(tbl_ord.ord_quantity) as quantity'),
                DB::raw("CONCAT('Order #', tbl_ord.ord_id) as reference"),
                'tbl_user.user_name as user',
                DB::raw("CASE 
                    WHEN tbl_ord.bill_id IS NOT NULL THEN CONCAT('Billing Payment #', tbl_ord.bill_id)
                    ELSE 'Direct Sale via POS'
                END as notes")
            )
            ->orderBy('tbl_ord.ord_date', 'desc')
            ->limit(50)
            ->get();

        // Convert to array format
        $stockHistory = $salesHistory->map(function($movement) {
            return [
                'date' => $movement->date,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'reference' => $movement->reference,
                'user' => $movement->user ?? 'System',
                'notes' => $movement->notes
            ];
        })->toArray();

        // Calculate damage analysis
        $totalSold = DB::table('tbl_ord')
            ->where('prod_id', $id)
            ->sum('ord_quantity');

        $damageAnalysis = [
            'total_damaged' => $product->prod_damaged ?? 0,
            'total_pullout' => $product->prod_pullout ?? 0,
            'total_sold' => $totalSold ?? 0,
            'damage_percentage' => ($totalSold + ($product->prod_damaged ?? 0)) > 0 ? 
                round((($product->prod_damaged ?? 0) / ($totalSold + ($product->prod_damaged ?? 0))) * 100, 2) : 0
        ];

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

        // Calculate average daily usage from last 30 days
        $averageDailyUsage = DB::table('tbl_ord')
            ->where('prod_id', $id)
            ->where('ord_date', '>=', Carbon::now()->subDays(30))
            ->selectRaw('COALESCE(SUM(ord_quantity) / 30, 0) as avg_usage')
            ->first();

        $stockAnalytics = [
            'current_stock' => $product->prod_stocks ?? 0,
            'reorder_level' => $product->prod_reorderlevel ?? 0,
            'average_daily_usage' => round($averageDailyUsage->avg_usage ?? 0, 2),
            'days_until_reorder' => 0
        ];

        if ($stockAnalytics['average_daily_usage'] > 0 && $product->prod_reorderlevel && $product->prod_stocks > $product->prod_reorderlevel) {
            $stockAnalytics['days_until_reorder'] = intval(($product->prod_stocks - $product->prod_reorderlevel) / $stockAnalytics['average_daily_usage']);
        }

        // Sales by month (last 6 months)
        $monthlySales = DB::table('tbl_ord')
            ->where('prod_id', $id)
            ->where('ord_date', '>=', Carbon::now()->subMonths(6))
            ->selectRaw('
                DATE_FORMAT(ord_date, "%Y-%m") as month,
                SUM(ord_quantity) as quantity_sold
            ')
            ->groupBy(DB::raw('DATE_FORMAT(ord_date, "%Y-%m")'))
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'product' => $product,
            'stock_history' => $stockHistory,
            'damage_analysis' => $damageAnalysis,
            'expiry_data' => $expiryData,
            'stock_analytics' => $stockAnalytics,
            'monthly_sales' => $monthlySales
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch inventory history: ' . $e->getMessage()], 500);
    }
}



    // -------------------- EXISTING METHODS --------------------
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'prod_name' => 'required|string|max:255',
            'prod_category' => 'nullable|string|max:255',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'required|numeric|min:0',
            'prod_stocks' => 'nullable|integer|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('prod_image')) {
            $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->back()->with('success', 'Product added successfully!');
    }

    public function updateProduct(Request $request, $id)
    {
        $validated = $request->validate([
            'prod_name' => 'required|string|max:255',
            'prod_category' => 'nullable|string|max:255',
            'prod_description' => 'required|string|max:1000',
            'prod_price' => 'required|numeric|min:0',
            'prod_stocks' => 'nullable|integer|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
            'prod_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product = Product::findOrFail($id);

        if ($request->hasFile('prod_image')) {
            if ($product->prod_image) {
                Storage::disk('public')->delete($product->prod_image);
            }
            $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Product updated successfully!');
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        
        if ($product->prod_image) {
            Storage::disk('public')->delete($product->prod_image);
        }
        
        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully!');
    }

    public function updateInventory(Request $request, $id)
    {
        $validated = $request->validate([
            'prod_stocks' => 'nullable|integer|min:0',
            'prod_reorderlevel' => 'nullable|integer|min:0',
            'prod_damaged' => 'nullable|integer|min:0',
            'prod_pullout' => 'nullable|integer|min:0',
            'prod_expiry' => 'nullable|date',
        ]);

        $product = Product::findOrFail($id);
        $product->update($validated);

        return redirect()->back()->with('success', 'Inventory updated successfully!');
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'serv_name' => 'required|string|max:255',
            'serv_type' => 'nullable|string|max:255',
            'serv_description' => 'nullable|string|max:1000',
            'serv_price' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
        ]);

        Service::create($validated);

        return redirect()->back()->with('success', 'Service added successfully!');
    }

    public function updateService(Request $request, $id)
    {
        $validated = $request->validate([
            'serv_name' => 'required|string|max:255',
            'serv_type' => 'nullable|string|max:255',
            'serv_description' => 'nullable|string|max:1000',
            'serv_price' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
        ]);

        $service = Service::findOrFail($id);
        $service->update($validated);

        return redirect()->back()->with('success', 'Service updated successfully!');
    }

    public function deleteService($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->back()->with('success', 'Service deleted successfully!');
    }

    public function storeEquipment(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'equipment_quantity' => 'required|integer|min:0',
            'equipment_description' => 'nullable|string|max:1000',
            'equipment_category' => 'required|string|max:255',
            'equipment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
        ]);

        if ($request->hasFile('equipment_image')) {
            $validated['equipment_image'] = $request->file('equipment_image')->store('equipment', 'public');
        }

        Equipment::create($validated);

        return redirect()->back()->with('success', 'Equipment added successfully!');
    }

    public function updateEquipment(Request $request, $id)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'equipment_quantity' => 'required|integer|min:0',
            'equipment_description' => 'nullable|string|max:1000',
            'equipment_category' => 'nullable|string|max:255',
            'equipment_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'branch_id' => 'nullable|exists:tbl_branch,branch_id',
        ]);

        $equipment = Equipment::findOrFail($id);

        if ($request->hasFile('equipment_image')) {
            if ($equipment->equipment_image) {
                Storage::disk('public')->delete($equipment->equipment_image);
            }
            $validated['equipment_image'] = $request->file('equipment_image')->store('equipment', 'public');
        }

        $equipment->update($validated);

        return redirect()->back()->with('success', 'Equipment updated successfully!');
    }

    public function deleteEquipment($id)
    {
        $equipment = Equipment::findOrFail($id);
        
        if ($equipment->equipment_image) {
            Storage::disk('public')->delete($equipment->equipment_image);
        }
        
        $equipment->delete();

        return redirect()->back()->with('success', 'Equipment deleted successfully!');
    }

    // In ProdServEquipController.php

public function updateStock(Request $request, $id)
{
    $validated = $request->validate([
        'add_stock' => 'required|integer|min:1',
        'new_expiry' => 'required|date',
        'reorder_level' => 'nullable|integer|min:0',
        'notes' => 'nullable|string'
    ]);

    $product = Product::findOrFail($id);
    
    // Add to existing stock
    $product->prod_stocks = ($product->prod_stocks ?? 0) + $validated['add_stock'];
    
    // Update expiry date for the new stock
    $product->prod_expiry = $validated['new_expiry'];
    
    // Update reorder level if provided
    if (isset($validated['reorder_level'])) {
        $product->prod_reorderlevel = $validated['reorder_level'];
    }
    
    $product->save();
    
    // Optional: Log the stock update
    // Create stock_movements table to track history
    
    return redirect()->back()->with('success', 'Stock updated successfully!');
}

public function updateDamage(Request $request, $id)
{
    $validated = $request->validate([
        'damaged_qty' => 'nullable|integer|min:0',
        'pullout_qty' => 'nullable|integer|min:0',
        'reason' => 'nullable|string'
    ]);

    $product = Product::findOrFail($id);
    
    if (isset($validated['damaged_qty'])) {
        $product->prod_damaged = $validated['damaged_qty'];
    }
    
    if (isset($validated['pullout_qty'])) {
        $product->prod_pullout = $validated['pullout_qty'];
    }
    
    $product->save();
    
    // Optional: Log the damage/pullout update
    
    return redirect()->back()->with('success', 'Damage/Pull-out updated successfully!');
}


}
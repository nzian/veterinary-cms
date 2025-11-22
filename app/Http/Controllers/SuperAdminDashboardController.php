<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Pet;
use App\Models\Order;
use App\Models\Owner;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Visit;
use Carbon\Carbon;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        // Ensure only super admin can access
        if (!auth()->check() || auth()->user()->user_role !== 'superadmin') {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();

        // Overview Statistics
        $totalBranches = Branch::count();
        $activeBranches = Branch::count(); // All branches are active
        $totalStaff = User::where('user_role', '!=', 'superadmin')->count();
        $totalRevenue = Order::sum('ord_total');
        $todayRevenue = Order::whereDate('ord_date', $today)->sum('ord_total');

        // Branch Performance Data
        $branchPerformance = Branch::withCount([
            'users',
            'services',
            'products'
        ])->get()->map(function ($branch) {
            $branchOrders = Order::whereHas('user', function($q) use ($branch) {
                $q->where('branch_id', $branch->branch_id);
            })->sum('ord_total');

            $branchVisits = Visit::whereHas('user', function($q) use ($branch) {
                $q->where('branch_id', $branch->branch_id);
            })->count();

            return [
                'branch_id' => $branch->branch_id,
                'branch_name' => $branch->branch_name,
                'branch_location' => $branch->branch_location ?? 'N/A',
                'branch_status' => 'active', // All branches are active
                'staff_count' => $branch->users_count,
                'services_count' => $branch->services_count,
                'products_count' => $branch->products_count,
                'total_revenue' => $branchOrders,
                'visits_count' => $branchVisits,
            ];
        });

        // Top Performing Branches (by revenue)
        $topBranches = $branchPerformance->sortByDesc('total_revenue')->take(5)->values();

        // Monthly Revenue Comparison (All Branches)
        $monthlyBranchRevenue = [];
        $branches = Branch::all();
        
        foreach ($branches as $branch) {
            $monthlyData = [];
            for ($i = 1; $i <= 12; $i++) {
                $revenue = Order::whereHas('user', function($q) use ($branch) {
                    $q->where('branch_id', $branch->branch_id);
                })
                ->whereYear('ord_date', now()->year)
                ->whereMonth('ord_date', $i)
                ->sum('ord_total');
                
                $monthlyData[] = (float) $revenue;
            }
            
            $monthlyBranchRevenue[] = [
                'branch_name' => $branch->branch_name,
                'data' => $monthlyData
            ];
        }

        // Last 7 Days Revenue by Branch
        $last7DaysRevenue = [];
        $dates = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dates[] = $date->format('M d');
            
            foreach ($branches as $branch) {
                $revenue = Order::whereHas('user', function($q) use ($branch) {
                    $q->where('branch_id', $branch->branch_id);
                })->whereDate('ord_date', $date)->sum('ord_total');
                
                if (!isset($last7DaysRevenue[$branch->branch_name])) {
                    $last7DaysRevenue[$branch->branch_name] = [];
                }
                $last7DaysRevenue[$branch->branch_name][] = (float) $revenue;
            }
        }

        // Branch Statistics Summary
        $branchStats = [
            'total_visits' => Visit::count(),
            'today_visits' => Visit::whereDate('visit_date', $today)->count(),
            'total_services' => Service::count(),
            'total_products' => Product::count(),
            'total_pets' => Pet::count(),
            'total_owners' => Owner::count(),
        ];

        // Visit Overview by Status
        $visitsByStatus = Visit::selectRaw('visit_status, COUNT(*) as count')
            ->groupBy('visit_status')
            ->get()
            ->pluck('count', 'visit_status')
            ->toArray();

        // Visit Overview by Patient Type
        $visitsByPatientType = Visit::selectRaw('patient_type, COUNT(*) as count')
            ->groupBy('patient_type')
            ->get()
            ->mapWithKeys(function($item) {
                $patientType = is_object($item->patient_type) ? $item->patient_type->value : $item->patient_type;
                return [$patientType => $item->count];
            })
            ->toArray();

        // Recent Activities Across All Branches
        $recentOrders = Order::with(['user.branch'])
            ->orderBy('ord_date', 'desc')
            ->limit(10)
            ->get();

        $recentAppointments = Appointment::with(['pet.owner', 'user.branch'])
            ->orderBy('appoint_date', 'desc')
            ->limit(10)
            ->get();

        // Staff Distribution by Branch
        $staffDistribution = Branch::withCount('users')->get()->map(function($branch) {
            return [
                'branch_name' => $branch->branch_name,
                'staff_count' => $branch->users_count
            ];
        });

        // Appointment Status Distribution
        $appointmentsByStatus = Appointment::selectRaw('appoint_status, COUNT(*) as count')
            ->groupBy('appoint_status')
            ->get()
            ->pluck('count', 'appoint_status')
            ->toArray();

        // Month names for charts
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create()->month($i)->format('F');
        }

        return view('superadmin.dashboard', compact(
            'totalBranches',
            'activeBranches',
            'totalStaff',
            'totalRevenue',
            'todayRevenue',
            'branchPerformance',
            'topBranches',
            'monthlyBranchRevenue',
            'last7DaysRevenue',
            'dates',
            'branchStats',
            'recentOrders',
            'recentAppointments',
            'staffDistribution',
            'appointmentsByStatus',
            'visitsByStatus',
            'visitsByPatientType',
            'months'
        ));
    }

    // Get detailed branch view
    public function showBranch($branchId)
    {
        if (!auth()->check() || auth()->user()->user_role !== 'superadmin') {
            abort(403, 'Unauthorized access');
        }

        $branch = Branch::with(['users', 'services', 'products'])->findOrFail($branchId);
        
        $today = Carbon::today();

        // Branch specific metrics
        $branchRevenue = Order::whereHas('user', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->sum('ord_total');

        $todayRevenue = Order::whereHas('user', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->whereDate('ord_date', $today)->sum('ord_total');

        $totalVisits = Visit::whereHas('user', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->count();

        $todayVisits = Visit::whereHas('user', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->whereDate('visit_date', $today)->count();

        // Monthly performance
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenue = Order::whereHas('user', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->whereYear('ord_date', now()->year)
            ->whereMonth('ord_date', $i)
            ->sum('ord_total');
            
            $monthlyRevenue[] = (float) $revenue;
        }

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create()->month($i)->format('F');
        }

        return view('superadmin.branch-detail', compact(
            'branch',
            'branchRevenue',
            'todayRevenue',
            'totalVisits',
            'todayVisits',
            'monthlyRevenue',
            'months'
        ));
    }
}
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
use App\Models\Billing;
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
        
        // Get total revenue from Billing table (all branches)
        $totalRevenue = Billing::sum('total_amount');
        $todayRevenue = Billing::whereDate('bill_date', $today)->sum('total_amount');

        // Branch Performance Data
        $branchPerformance = Branch::withCount([
            'users',
            'services',
            'products'
        ])->get()->map(function ($branch) {
            // Get revenue from Billing table directly by branch_id
            $branchRevenue = Billing::where('branch_id', $branch->branch_id)->sum('total_amount');

            // Get visits without global scope to count all visits for users in this branch
            $branchUserIds = User::where('branch_id', $branch->branch_id)->pluck('user_id');
            $branchVisits = Visit::withoutGlobalScopes()->whereIn('user_id', $branchUserIds)->count();

            return [
                'branch_id' => $branch->branch_id,
                'branch_name' => $branch->branch_name,
                'branch_location' => $branch->branch_location ?? 'N/A',
                'branch_status' => 'active', // All branches are active
                'staff_count' => $branch->users_count,
                'services_count' => $branch->services_count,
                'products_count' => $branch->products_count,
                'total_revenue' => $branchRevenue,
                'visits_count' => $branchVisits,
            ];
        });

        // Top Performing Branches (by revenue)
        $topBranches = $branchPerformance->sortByDesc('total_revenue')->take(5)->values();

        // Monthly Revenue Comparison (All Branches) - Using Billing table
        $monthlyBranchRevenue = [];
        $branches = Branch::all();
        
        foreach ($branches as $branch) {
            $monthlyData = [];
            for ($i = 1; $i <= 12; $i++) {
                $revenue = Billing::where('branch_id', $branch->branch_id)
                    ->whereYear('bill_date', now()->year)
                    ->whereMonth('bill_date', $i)
                    ->sum('total_amount');
                
                $monthlyData[] = (float) $revenue;
            }
            
            $monthlyBranchRevenue[] = [
                'branch_name' => $branch->branch_name,
                'data' => $monthlyData
            ];
        }

        // Last 7 Days Revenue by Branch - Using Billing table
        $last7DaysRevenue = [];
        $dates = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dates[] = $date->format('M d');
            
            foreach ($branches as $branch) {
                $revenue = Billing::where('branch_id', $branch->branch_id)
                    ->whereDate('bill_date', $date)
                    ->sum('total_amount');
                
                if (!isset($last7DaysRevenue[$branch->branch_name])) {
                    $last7DaysRevenue[$branch->branch_name] = [];
                }
                $last7DaysRevenue[$branch->branch_name][] = (float) $revenue;
            }
        }

        // Branch Statistics Summary - Use withoutGlobalScopes for accurate counts
        $branchStats = [
            'total_visits' => Visit::withoutGlobalScopes()->count(),
            'today_visits' => Visit::withoutGlobalScopes()->whereDate('visit_date', $today)->count(),
            'total_services' => Service::count(),
            'total_products' => Product::count(),
            'total_pets' => Pet::count(),
            'total_owners' => Owner::count(),
        ];

        // Visit Overview by Status - Use withoutGlobalScopes for all branches
        $visitsByStatus = Visit::withoutGlobalScopes()
            ->selectRaw('visit_status, COUNT(*) as count')
            ->groupBy('visit_status')
            ->get()
            ->pluck('count', 'visit_status')
            ->toArray();

        // Visit Overview by Patient Type - Use withoutGlobalScopes for all branches
        $visitsByPatientType = Visit::withoutGlobalScopes()
            ->selectRaw('patient_type, COUNT(*) as count')
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

        // Branch specific metrics - Using Billing table
        $branchRevenue = Billing::where('branch_id', $branchId)->sum('total_amount');

        $todayRevenue = Billing::where('branch_id', $branchId)
            ->whereDate('bill_date', $today)
            ->sum('total_amount');

        // Get visits without global scope for accurate count
        $branchUserIds = User::where('branch_id', $branchId)->pluck('user_id');
        $totalVisits = Visit::withoutGlobalScopes()->whereIn('user_id', $branchUserIds)->count();

        $todayVisits = Visit::withoutGlobalScopes()
            ->whereIn('user_id', $branchUserIds)
            ->whereDate('visit_date', $today)
            ->count();

        // Monthly performance - Using Billing table
        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenue = Billing::where('branch_id', $branchId)
                ->whereYear('bill_date', now()->year)
                ->whereMonth('bill_date', $i)
                ->sum('total_amount');
            
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
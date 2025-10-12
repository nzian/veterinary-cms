<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Pet;
use App\Models\Order;
use App\Models\Owner;
use App\Models\Referral;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\BranchFilterable;

class DashboardController extends Controller
{
    use BranchFilterable;

    public function index()
    {
        $user = auth()->user();
        $normalizedRole = strtolower(trim($user->user_role));
        
        // Check if super admin is in branch mode
        if ($normalizedRole === 'superadmin') {
            if ($this->isInBranchMode()) {
                // Super admin viewing specific branch - show user dashboard
                return $this->branchDashboard();
            } else {
                // Super admin viewing all branches - redirect to super admin dashboard
                return redirect()->route('superadmin.dashboard');
            }
        }
        
        // Regular users always see branch dashboard
        return $this->branchDashboard();
    }

    /**
     * Branch-specific dashboard (for users and super admin in branch mode)
     */
    private function branchDashboard()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get active branch ID using trait
        $activeBranchId = $this->getActiveBranchId();
        
        // Set user info
        $userName = $user->name ?? $user->user_name ?? $user->email ?? 'User';
        $userRole = $user->user_role ?? 'user';

        // Get branch name using trait
        $branchName = $this->getActiveBranchName();

        // Get all user IDs from the active branch
        $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)
            ->pluck('user_id')
            ->toArray();

        // Filter data by branch users
        $totalPets = Pet::whereIn('user_id', $branchUserIds)->count();
        
        $totalServices = Service::where('branch_id', $activeBranchId)->count();
        $totalProducts = Product::where('branch_id', $activeBranchId)->count();
        
        // Filter appointments by user's branch
        $totalAppointments = Appointment::whereHas('user', function($q) use ($activeBranchId) {
            $q->where('branch_id', $activeBranchId);
        })->count();
        
        $todaysAppointments = Appointment::whereDate('appoint_date', $today)
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })->count();

        $dailySales = Order::whereDate('ord_date', $today)
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })->sum('ord_total');

        // Metrics
        $totalOrders = Order::whereHas('user', function($q) use ($activeBranchId) {
            $q->where('branch_id', $activeBranchId);
        })->count();
        
        $totalBranches = Branch::count();
        $totalOwners = Owner::whereIn('user_id', $branchUserIds)->count();

        // Recent Appointments - filtered by branch
        $recentAppointments = Appointment::with(['pet.owner'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->orderBy('appoint_date', 'desc')
            ->limit(5)
            ->get();

        // 7-Day Sales - filtered by branch
        $orderDates = [];
        $orderTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $orderDates[] = $date->format('M d');
            $total = Order::whereDate('ord_date', $date)
                ->whereHas('user', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                })
                ->sum('ord_total');
            $orderTotals[] = $total;
        }

        // Monthly Sales - filtered by branch
        $monthlySales = Order::selectRaw('MONTH(ord_date) as month, SUM(ord_total) as total')
            ->whereYear('ord_date', now()->year)
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->groupBy(DB::raw('MONTH(ord_date)'))
            ->orderBy(DB::raw('MONTH(ord_date)'))
            ->get();

        $months = [];
        $monthlySalesTotals = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create()->month($i)->format('F');
            $monthData = $monthlySales->firstWhere('month', $i);
            $monthlySalesTotals[] = $monthData ? (float) $monthData->total : 0;
        }

        // Complete appointment data - filtered by branch
        $appointments = Appointment::with(['pet.owner'])
            ->whereNotIn('appoint_status', ['completed','Canceled'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->appoint_date)->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'id' => $item->appoint_id,
                        'pet_id' => $item->pet_id,
                        'title' => ($item->appoint_type ?? 'Checkup') . ' - ' . ($item->pet->pet_name ?? 'Unknown'),
                        'pet_name' => $item->pet->pet_name ?? 'Unknown Pet',
                        'owner_name' => $item->pet->owner->own_name ?? 'Unknown Owner',
                        'date' => Carbon::parse($item->appoint_date)->format('Y-m-d'),
                        'time' => $item->appoint_time ?? 'No time set',
                        'status' => Str::lower($item->appoint_status ?? 'pending'),
                        'notes' => $item->appoint_notes ?? '',
                        'type' => $item->appoint_type ?? 'Checkup',
                        'pet_breed' => $item->pet->pet_breed ?? '',
                        'pet_age' => $item->pet->pet_age ?? '',
                        'pet_gender' => $item->pet->pet_gender ?? '',
                        'owner_contact' => $item->pet->owner->own_contactnum ?? '',
                    ];
                });
            });

        // Recent Referrals - filtered by branch
        $recentReferrals = Referral::with(['appointment.pet.owner', 'appointment.service'])
            ->where(function($q) use ($activeBranchId) {
                $q->where('ref_to', $activeBranchId)
                  ->orWhere('ref_by', $activeBranchId);
            })
            ->latest('ref_date')
            ->take(5)
            ->get();

        $branches = Branch::all();

        // Return the USER dashboard view (views/dashboard.blade.php)
        return view('dashboard', compact(
            'totalPets',
            'totalServices',
            'totalProducts',
            'totalOrders',
            'totalBranches',
            'totalAppointments',
            'todaysAppointments',
            'recentAppointments',
            'dailySales',
            'orderDates',
            'orderTotals',
            'months',
            'monthlySalesTotals',
            'branches',
            'branchName',
            'totalOwners',
            'appointments',
            'recentReferrals',
            'activeBranchId',
            'userName',
            'userRole'
        ));
    }
}
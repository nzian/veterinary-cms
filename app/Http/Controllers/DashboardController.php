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

             $today = now();
    $thirtyDaysFromNow = now()->addDays(30);
    
    // Get vaccination service IDs (serv_type = 'Preventive Care' for vaccinations)
    $vaccinationServiceIds = Service::where(function($query) {
        $query->where('serv_name', 'LIKE', '%Vaccination%')
              ->orWhere('serv_name', 'LIKE', '%Vaccine%')
              ->orWhere('serv_name', 'LIKE', '%Immunization%')
              ->orWhere(function($q) {
                  $q->where('serv_type', 'Preventive Care')
                    ->where('serv_name', 'Vaccination');
              });
    })->pluck('serv_id')->toArray();
    
    // Get all vaccination appointments from your branch
    $vaccinationAppointments = Appointment::with(['pet.owner', 'services', 'user'])
        ->whereHas('user', function($q) use ($activeBranchId) {
            $q->where('branch_id', $activeBranchId);
        })
        ->whereHas('services', function($q) use ($vaccinationServiceIds) {
            $q->whereIn('tbl_serv.serv_id', $vaccinationServiceIds);
        })
        ->where('appoint_status', '!=', 'cancelled')
        ->orderBy('appoint_date', 'desc')
        ->get();
    
    // Group by pet to get latest vaccination per pet
    $latestVaccinationsPerPet = $vaccinationAppointments->groupBy('pet_id')->map(function($petVaccinations) {
        $latest = $petVaccinations->sortByDesc('appoint_date')->first();
        
        // Calculate next due date (12 months after last vaccination)
        $lastVaccinationDate = Carbon::parse($latest->appoint_date);
        $nextDueDate = $lastVaccinationDate->copy()->addMonths(12);
        
        return [
            'pet_id' => $latest->pet_id,
            'pet_name' => $latest->pet->pet_name ?? 'Unknown',
            'pet_breed' => $latest->pet->pet_breed ?? 'N/A',
            'owner_name' => $latest->pet->owner->own_name ?? 'Unknown',
            'last_vaccination' => $latest->appoint_date,
            'next_due_date' => $nextDueDate,
            'vaccine_name' => $latest->services->first()->serv_name ?? 'General Vaccination',
            'days_until_due' => now()->diffInDays($nextDueDate, false),
        ];
    })->values();
    
    // Calculate vaccination statistics
    $vaccinationStats = [
        'upToDate' => $latestVaccinationsPerPet->filter(function($vac) use ($thirtyDaysFromNow) {
            return Carbon::parse($vac['next_due_date'])->gt($thirtyDaysFromNow);
        })->count(),
        
        'dueSoon' => $latestVaccinationsPerPet->filter(function($vac) use ($today, $thirtyDaysFromNow) {
            $dueDate = Carbon::parse($vac['next_due_date']);
            return $dueDate->between($today, $thirtyDaysFromNow);
        })->count(),
        
        'overdue' => $latestVaccinationsPerPet->filter(function($vac) use ($today) {
            return Carbon::parse($vac['next_due_date'])->lt($today);
        })->count(),
        
        'total' => $latestVaccinationsPerPet->count()
    ];
    
    // Get upcoming vaccinations (next 60 days) with proper structure
    $upcomingVaccinations = $latestVaccinationsPerPet
        ->filter(function($vac) use ($today) {
            $dueDate = Carbon::parse($vac['next_due_date']);
            return $dueDate->lte(now()->addDays(60));
        })
        ->sortBy('next_due_date')
        ->take(10)
        ->map(function($vac) {
            return (object)[
                'pet_name' => $vac['pet_name'],
                'vaccine_name' => $vac['vaccine_name'],
                'due_date' => $vac['next_due_date']->format('Y-m-d'),
                'owner_name' => $vac['owner_name'],
            ];
        })
        ->values();
    
    // Get vaccination types distribution (from completed appointments with vaccination services)
    $vaccinationTypesData = DB::table('tbl_appoint')
        ->join('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
        ->join('tbl_serv', 'tbl_appoint_serv.serv_id', '=', 'tbl_serv.serv_id')
        ->join('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
        ->where('tbl_user.branch_id', $activeBranchId)
        ->whereIn('tbl_appoint_serv.serv_id', $vaccinationServiceIds)
        ->whereIn('tbl_appoint.appoint_status', ['completed', 'arrived'])
        ->select(
            'tbl_serv.serv_name as vaccine_name',
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('tbl_serv.serv_name')
        ->orderBy('count', 'desc')
        ->limit(5)
        ->get();
    
    $vaccinationTypes = [
        'labels' => $vaccinationTypesData->pluck('vaccine_name')->toArray(),
        'data' => $vaccinationTypesData->pluck('count')->toArray()
    ];
    
    // Default labels if no vaccination data exists
    if (empty($vaccinationTypes['labels'])) {
        $vaccinationTypes = [
            'labels' => ['Rabies', 'Distemper', '5-in-1', 'Anti-Rabies', 'Deworming'],
            'data' => [0, 0, 0, 0, 0]
        ];
    }

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
            'userRole',
            'vaccinationStats',
        'upcomingVaccinations',
        'vaccinationTypes'
        ));
    }

    public function getVaccinationServices()
{
    $services = Service::where(function($query) {
        $query->where('serv_name', 'LIKE', '%vaccin%')
              ->orWhere('serv_name', 'LIKE', '%immun%')
              ->orWhere('serv_description', 'LIKE', '%vaccin%');
    })->get(['serv_id', 'serv_name', 'serv_category']);
    
    return response()->json($services);
}
}
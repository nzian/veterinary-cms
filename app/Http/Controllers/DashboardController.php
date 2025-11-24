<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Pet;
use App\Models\Order;
use App\Models\Owner;
use App\Models\Referral;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Billing;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\BranchFilterable;

class DashboardController extends Controller
{
    use BranchFilterable;

    public function __construct()
    {
        $this->middleware('auth');
    }

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

        $totalProducts = Product::where('branch_id', $activeBranchId)->count(); 
        $totalServices = Service::where('branch_id', $activeBranchId)->count(); 
        $totalPets = Pet::whereIn('user_id', $branchUserIds)->count();
$totalOwners = Owner::whereIn('user_id', $branchUserIds)->count();

        // --- NEW THRESHOLDS ---
        // 1. Critical Due Soon: 14 days from now.
        $dueSoonThreshold = $today->copy()->addDays(14);
        // 2. Overdue: 7 days ago. If the due date is before this, it's considered critically overdue.
        $overdueThreshold = $today->copy()->subDays(7); 
        // 3. Up To Date threshold (General Tracking): 30 days from now.
        $thirtyDaysFromNow = now()->addDays(30); 
        
        // Fetch vaccination records from tbl_vaccination_record table
        $vaccinationRecords = DB::table('tbl_vaccination_record as vr')
            ->join('tbl_pet as p', 'vr.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_visit_record as v', 'vr.visit_id', '=', 'v.visit_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->where('u.branch_id', $activeBranchId)
            ->select(
                'vr.pet_id',
                'p.pet_name',
                'p.pet_species',
                'o.own_name as owner_name',
                'vr.vaccine_name',
                'vr.date_administered',
                'vr.next_due_date',
                DB::raw('MAX(vr.date_administered) as last_vaccination')
            )
            ->groupBy('vr.pet_id', 'p.pet_name', 'p.pet_species', 'o.own_name', 'vr.vaccine_name', 'vr.date_administered', 'vr.next_due_date')
            ->orderBy('vr.date_administered', 'desc')
            ->get();

        // Process and Group by pet to get the latest vaccination record
        $latestVaccinationsPerPet = $vaccinationRecords->groupBy('pet_id')->map(function($petVaccinations) {
            $latest = $petVaccinations->sortByDesc('date_administered')->first();
            $nextDueDate = $latest->next_due_date ? Carbon::parse($latest->next_due_date) : Carbon::parse($latest->date_administered)->copy()->addMonths(12);

            return [
                'pet_id' => $latest->pet_id,
                'pet_name' => $latest->pet_name ?? 'Unknown',
                'pet_species' => $latest->pet_species ?? 'N/A',
                'owner_name' => $latest->owner_name ?? 'Unknown',
                'last_vaccination' => $latest->date_administered,
                'next_due_date' => $nextDueDate,
                'vaccine_name' => $latest->vaccine_name ?? 'General Vaccine',
                'days_until_due' => now()->diffInDays($nextDueDate, false),
            ];
        })->values();
        
        // 4. Calculate vaccination statistics - FIXED LOGIC (14-day Due Soon, 7-day Overdue Grace)
        $vaccinationStats = [
            // Overdue: Due date is 7 days or more in the past.
            'overdue' => $latestVaccinationsPerPet->filter(function($vac) use ($overdueThreshold) {
                return $vac['next_due_date']->lt($overdueThreshold);
            })->count(),
            
            // Due Soon: Due date is AFTER the Overdue Threshold AND before the 14-day mark.
            // This includes the grace period (0 to 7 days past due) AND the upcoming 1-14 days.
            'dueSoon' => $latestVaccinationsPerPet->filter(function($vac) use ($overdueThreshold, $dueSoonThreshold) {
                $dueDate = $vac['next_due_date'];
                
                // Due date is before the 14-day future cutoff (i.e., today or in the past 7 days)
                $isInDueSoonWindow = $dueDate->lte($dueSoonThreshold);

                // Is NOT critically overdue (i.e., due date is after the 7-day past mark)
                $isNotCriticallyOverdue = $dueDate->gt($overdueThreshold);
                
                return $isInDueSoonWindow && $isNotCriticallyOverdue;
            })->count(),
            
            // Up to Date: More than 14 days from now (or more than 30 days if using that range).
            'upToDate' => $latestVaccinationsPerPet->filter(function($vac) use ($dueSoonThreshold) {
                return $vac['next_due_date']->gt($dueSoonThreshold);
            })->count(),
            
            'total' => $latestVaccinationsPerPet->count()
        ];
        
        // 5. Get upcoming vaccinations (Overdue + next 60 days), ordered by due date
        $upcomingVaccinations = $latestVaccinationsPerPet
            ->filter(function($vac) {
                // Include critically overdue and anything due in the next 60 days for the table
                return $vac['next_due_date']->lte(now()->addDays(60));
            })
            ->sortBy('next_due_date')
            ->take(10)
            ->map(function($vac) {
                // Cast to an object for consistent access in the Blade view
                return (object)[
                    'pet_name' => $vac['pet_name'],
                    'pet_species' => $vac['pet_species'],
                    'vaccine_name' => $vac['vaccine_name'],
                    'due_date' => $vac['next_due_date']->format('Y-m-d'),
                    'owner_name' => $vac['owner_name'],
                ];
            })
            ->values();
        
        // 6. Get vaccination types distribution from vaccination records
        $vaccinationTypesData = DB::table('tbl_vaccination_record as vr')
            ->join('tbl_visit_record as v', 'vr.visit_id', '=', 'v.visit_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->where('u.branch_id', $activeBranchId)
            ->select(
                'vr.vaccine_name',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('vr.vaccine_name')
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

        // --- Other Dashboard Metrics (Original Logic) ---
        $totalPets = Pet::whereIn('user_id', $branchUserIds)->count();
        $totalServices = Service::where('branch_id', $activeBranchId)->count();
        $totalProducts = Product::where('branch_id', $activeBranchId)->count();
        
        // Filter visits by user's branch
        $totalVisits = Visit::whereHas('user', function($q) use ($activeBranchId) {
            $q->where('branch_id', $activeBranchId);
        })->count();
        
        $todaysVisits = Visit::whereDate('visit_date', $today)
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

        // 7-Day Revenue - filtered by branch (POS sales + Visit billing payments)
        $orderDates = [];
        $orderTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $orderDates[] = $date->format('M d');
            
            // POS Sales (direct orders)
            $posTotal = Order::whereDate('ord_date', $date)
                ->whereHas('user', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                })
                ->sum('ord_total');
            
            // Visit Billing Payments
            $visitPayments = Payment::whereDate('created_at', $date)
                ->whereHas('billing', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                })
                ->sum('pay_total');
            
            $orderTotals[] = $posTotal + $visitPayments;
        }

        // Monthly Revenue - filtered by branch (POS sales + Visit billing payments)
        // Get POS sales by month
        $posMonthly = Order::selectRaw('MONTH(ord_date) as month, SUM(ord_total) as total')
            ->whereYear('ord_date', now()->year)
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->groupBy(DB::raw('MONTH(ord_date)'))
            ->get()
            ->keyBy('month');

        // Get visit billing payments by month
        $paymentMonthly = Payment::selectRaw('MONTH(created_at) as month, SUM(pay_total) as total')
            ->whereYear('created_at', now()->year)
            ->whereHas('billing', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get()
            ->keyBy('month');

        $months = [];
        $monthlySalesTotals = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create()->month($i)->format('F');
            $posTotal = $posMonthly->get($i)->total ?? 0;
            $paymentTotal = $paymentMonthly->get($i)->total ?? 0;
            $monthlySalesTotals[] = (float) ($posTotal + $paymentTotal);
        }

        // Complete appointment data for calendar
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
       $recentReferrals = Referral::with(['appointment.pet.owner', 'appointment.services', 'pet.owner'])
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
            'totalVisits',
            'todaysVisits',
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
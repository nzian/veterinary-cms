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

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $selectedBranchId = Session::get('selected_branch_id');

        $activeBranch = Branch::find($selectedBranchId);
        $branchName = $activeBranch ? $activeBranch->branch_name : 'Main Branch';

        // Metrics
        $totalPets = Pet::count();
        $totalServices = Service::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalBranches = Branch::count();
        $totalAppointments = Appointment::count();
        $todaysAppointments = Appointment::whereDate('appoint_date', $today)->count();
        $totalOwners = Owner::count();

        // Recent Appointments
        $recentAppointments = Appointment::with(['pet.owner'])
            ->orderBy('appoint_date', 'desc')
            ->limit(5)
            ->get();

        // Daily Sales
        $dailySales = Order::whereDate('ord_date', $today)->sum('ord_total');

        // 7-Day Sales
        $orderDates = [];
        $orderTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $orderDates[] = $date->format('M d');
            $total = Order::whereDate('ord_date', $date)->sum('ord_total');
            $orderTotals[] = $total;
        }

        // Monthly Sales
        $monthlySales = DB::table('tbl_ord')
            ->selectRaw('MONTH(ord_date) as month, SUM(ord_total) as total')
            ->whereYear('ord_date', now()->year)
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

        // Complete appointment data with all pet and owner details
        $appointments = Appointment::with(['pet.owner'])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->appoint_date)->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->map(function ($item) {
                    return [
                        'id' => $item->id,
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

        $recentReferrals = Referral::with(['appointment.pet.owner', 'appointment.service'])
            ->latest('ref_date')
            ->take(5)
            ->get();
        
        $recentAppointments = Appointment::with([
            'pet.owner',
            'user.branch'
        ])
        ->latest('appoint_date')
        ->take(5)
        ->get();

        $branches = Branch::all();

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
            'recentAppointments'
        ));
    }
}
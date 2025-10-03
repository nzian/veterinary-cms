<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Models\Appointment;
use App\Models\Owner;
use App\Models\Referral;
use App\Models\Billing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Branch name
        $branchName = auth()->user()->branch->branch_name ?? 'Dashboard';
        
        // Notifications
        $notifications = NotificationService::getUserNotifications(auth()->id());
        $unreadNotificationCount = NotificationService::getUnreadCount(auth()->id());
        
        // Low stock items (your existing code)
        $lowStockItems = DB::table('tbl_prod')
            ->where('prod_stocks', '<=', 10)
            ->get();
        
        // Total appointments
        $totalAppointments = Appointment::count();
        
        // Today's appointments
        $todaysAppointments = Appointment::whereDate('appoint_date', today())->count();
        
        // Total owners
        $totalOwners = Owner::count();
        
        // Daily sales
        $dailySales = Billing::whereDate('bill_date', today())
            ->where('bill_status', 'Paid')
            ->sum('bill_total') ?? 0;
        
        // Calendar appointments grouped by date
        $appointments = Appointment::with(['pet.owner'])
            ->get()
            ->groupBy(function($appointment) {
                return Carbon::parse($appointment->appoint_date)->format('Y-m-d');
            })
            ->map(function($dayAppointments) {
                return $dayAppointments->map(function($appointment) {
                    return [
                        'id' => $appointment->appoint_id,
                        'pet_id' => $appointment->pet_id,
                        'pet_name' => $appointment->pet->pet_name ?? 'Unknown',
                        'pet_breed' => $appointment->pet->pet_breed ?? 'N/A',
                        'pet_age' => $appointment->pet->pet_age ?? 'N/A',
                        'pet_gender' => $appointment->pet->pet_gender ?? 'N/A',
                        'owner_name' => $appointment->pet->owner->own_name ?? 'Unknown',
                        'date' => $appointment->appoint_date,
                        'time' => $appointment->appoint_time,
                        'status' => strtolower($appointment->appoint_status),
                        'type' => $appointment->appoint_type,
                        'notes' => $appointment->appoint_description,
                    ];
                });
            });
        
        // Recent appointments
        $recentAppointments = Appointment::with('pet.owner')
            ->orderBy('appoint_date', 'desc')
            ->take(5)
            ->get();
        
        // Recent referrals
        $recentReferrals = Referral::with('appointment.pet.owner')
            ->orderBy('ref_date', 'desc')
            ->take(5)
            ->get();
        
        // Daily revenue chart data (last 7 days)
        $orderDates = [];
        $orderTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $orderDates[] = $date->format('M d');
            $orderTotals[] = Billing::whereDate('bill_date', $date)
                ->where('bill_status', 'Paid')
                ->sum('bill_total') ?? 0;
        }
        
        // Monthly revenue chart data (current year)
        $months = [];
        $monthlySalesTotals = [];
        for ($i = 0; $i < 12; $i++) {
            $month = Carbon::now()->startOfYear()->addMonths($i);
            $months[] = $month->format('M');
            $monthlySalesTotals[] = Billing::whereYear('bill_date', $month->year)
                ->whereMonth('bill_date', $month->month)
                ->where('bill_status', 'Paid')
                ->sum('bill_total') ?? 0;
        }
        
        return view('dashboard', compact(
            'branchName',
            'notifications',
            'unreadNotificationCount',
            'lowStockItems',
            'totalAppointments',
            'todaysAppointments',
            'totalOwners',
            'dailySales',
            'appointments',
            'recentAppointments',
            'recentReferrals',
            'orderDates',
            'orderTotals',
            'months',
            'monthlySalesTotals'
        ));
    }
}
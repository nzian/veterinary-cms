<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Pet;
use App\Models\Order;
use App\Models\Appointment;
use App\Models\Visit;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
{
    return $this->AdminBoard(); // Or redirect to your main admin dashboard logic
}

   public function AdminBoard()
{
    
    // Fetch branches and product inventory
    $inventory = Product::with('branch')->get();
    $branches = Branch::all();

    // Low stock products
    $lowStockItems = Product::with('branch')
        ->whereColumn('prod_stocks', '<=', 'prod_reorderlevel')
        ->get();

    session(['lowStockCount' => $lowStockItems->count()]);
    session(['lowStockItems' => $lowStockItems]);

    $branchName = Branch::first()->branch_name ?? 'Main Branch';

    // Totals
    $totalPets = Pet::count();
    $totalServices = Service::count();
    $totalProducts = Product::count();
    $totalSales = Order::sum('ord_total');
    $totalBranches = Branch::count();
    $totalVisits = Visit::count();

    // Today's visits
    $todaysVisits = Visit::whereDate('visit_date', today())->count();

    // Daily sales
    $dailySales = Order::whereDate('ord_date', today())->sum('ord_total');

    // Sales for last 7 days
    $salesData = Order::selectRaw('DATE(ord_date) as date, SUM(ord_total) as total')
        ->whereDate('ord_date', '>=', now()->subDays(6))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $salesDates = $salesData->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M d'));
    $salesTotals = $salesData->pluck('total');

    // Monthly sales (current year)
    $monthlySales = Order::selectRaw('MONTH(ord_date) as month, SUM(ord_total) as total')
        ->whereYear('ord_date', now()->year)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    $months = $monthlySales->pluck('month')->map(fn($m) => Carbon::create()->month($m)->format('M'));
    $monthlySalesTotals = $monthlySales->pluck('total');

    // Recent Appointments
    $recentAppointments = Appointment::orderByDesc('appoint_date')->take(5)->get();

    // Final return with all data, include $lowStockItems
    return view('admin-dashboard', compact(
        'branches',
        'inventory',
        'branchName',
        'totalPets',
        'totalServices',
        'totalProducts',
        'totalSales',
        'totalBranches',
        'totalVisits',
        'todaysVisits',
        'dailySales',
        'salesDates',
        'salesTotals',
        'months',
        'monthlySalesTotals',
        'recentAppointments',
        'lowStockItems' // <-- Add this
    ));
}
}
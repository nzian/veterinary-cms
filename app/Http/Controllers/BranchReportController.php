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
use App\Models\Referral;
use App\Models\Appointment;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
// Import Str if you use it for truncation, but since the PDF view formats raw data, 
// we might not need it in the controller for single record details.

class BranchReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get user's branch - non-superadmins can only see their own branch
        $branchId = $user->branch_id;
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return redirect()->back()->with('error', 'Branch not found');
        }

        // Get filter parameters
        $reportType = $request->get('report', 'appointments');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Initialize reports array
        $reports = [];

        // Generate reports based on type - ALL FILTERED BY BRANCH
        switch($reportType) {
            case 'appointments':
                $reports['appointments'] = [
                    'title' => 'Appointment Management Report',
                    'description' => 'Complete appointment records for ' . $branch->branch_name,
                    'data' => Appointment::whereBetween('appoint_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with(['pet.owner', 'user.branch'])
                        ->get()
                        ->map(function($appointment) {
                            return (object)[
                                'appoint_id' => $appointment->appoint_id,
                                'owner_name' => $appointment->pet->owner->own_name ?? 'N/A',
                                'owner_contact' => $appointment->pet->owner->own_contactnum ?? 'N/A',
                                'pet_name' => $appointment->pet->pet_name ?? 'N/A',
                                'pet_breed' => $appointment->pet->pet_breed ?? 'N/A',
                                'branch_name' => $appointment->user->branch->branch_name ?? 'N/A',
                                'appointment_date' => $appointment->appoint_date,
                                'appointment_time' => $appointment->appoint_time,
                                'veterinarian' => $appointment->user->name ?? 'N/A',
                                'status' => $appointment->appoint_status
                            ];
                        })
                ];
                break;

            case 'pets':
                // FIX: Filter pets by appointments that belong to the branch
                $reports['pets'] = [
                    'title' => 'Pet Registration Report',
                    'description' => 'Registered pets at ' . $branch->branch_name,
                    'data' => Pet::whereBetween('pet_registration', [$startDate, $endDate])
                        ->whereHas('appointments', function($q) use ($branchId) {
                            $q->whereHas('user', function($userQuery) use ($branchId) {
                                $userQuery->where('branch_id', $branchId);
                            });
                        })
                        ->with('owner')
                        ->get()
                        ->map(function($pet) {
                            return (object)[
                                'pet_id' => $pet->pet_id,
                                'owner_name' => $pet->owner->own_name ?? 'N/A',
                                'owner_contact' => $pet->owner->own_contactnum ?? 'N/A',
                                'pet_name' => $pet->pet_name,
                                'pet_species' => $pet->pet_species,
                                'pet_breed' => $pet->pet_breed,
                                'pet_age' => $pet->pet_age,
                                'pet_gender' => $pet->pet_gender,
                                'registration_date' => $pet->pet_registration
                            ];
                        })
                ];
                break;

            case 'billing':
                $reports['billing'] = [
                    'title' => 'Financial Billing Report',
                    'description' => 'Billing records for ' . $branch->branch_name,
                    'data' => DB::table('tbl_bill')
                        ->join('tbl_visit_record', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
                        ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                        ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                        ->join('tbl_user', 'tbl_visit_record.user_id', '=', 'tbl_user.user_id')
                        ->join('tbl_branch', 'tbl_user.branch_id', '=', 'tbl_branch.branch_id')
                        ->leftJoin('tbl_visit_service', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
                        ->leftJoin('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
                        ->whereBetween('tbl_bill.bill_date', [$startDate, $endDate])
                        ->where('tbl_user.branch_id', $branchId)
                        ->select(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name as customer_name',
                            'tbl_pet.pet_name',
                            'tbl_visit_record.visit_date as service_date',
                            DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total'),
                            'tbl_branch.branch_name',
                            'tbl_bill.bill_status as payment_status'
                        )
                        ->groupBy(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name',
                            'tbl_pet.pet_name',
                            'tbl_visit_record.visit_date',
                            'tbl_branch.branch_name',
                            'tbl_bill.bill_status'
                        )
                        ->get()
                ];
                break;

            case 'sales':
                $reports['sales'] = [
                    'title' => 'Product Sales Report',
                    'description' => 'Sales transactions for ' . $branch->branch_name,
                    'data' => Order::whereBetween('ord_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with(['product', 'owner', 'user'])
                        ->get()
                        ->map(function($order) {
                            return (object)[
                                'ord_id' => $order->ord_id,
                                'sale_date' => $order->ord_date,
                                'customer_name' => $order->owner->own_name ?? 'Walk-in',
                                'product_name' => $order->product->prod_name ?? 'N/A',
                                'quantity_sold' => $order->ord_quantity,
                                'unit_price' => $order->product->prod_price ?? 0,
                                'total_amount' => $order->ord_total,
                                'cashier' => $order->user->name ?? 'N/A'
                            ];
                        })
                ];
                break;

            case 'referrals':
                $reports['referrals'] = [
                    'title' => 'Referral Report',
                    'description' => 'Patient referrals from ' . $branch->branch_name,
                    'data' => Referral::whereBetween('ref_date', [$startDate, $endDate])
                        ->whereHas('appointment.user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with(['appointment.pet.owner', 'appointment.user'])
                        ->get()
                        ->map(function($referral) {
                            return (object)[
                                'ref_id' => $referral->ref_id,
                                'ref_date' => $referral->ref_date,
                                'owner_name' => $referral->appointment->pet->owner->own_name ?? 'N/A',
                                'pet_name' => $referral->appointment->pet->pet_name ?? 'N/A',
                                'referral_reason' => $referral->ref_description,
                                'referred_by' => $referral->appointment->user->name ?? 'N/A',
                                'referred_to' => $referral->ref_to
                            ];
                        })
                ];
                break;

            case 'equipment':
                $reports['equipment'] = [
                    'title' => 'Equipment Inventory Report',
                    'description' => 'Equipment inventory for ' . $branch->branch_name,
                    'data' => DB::table('tbl_equipment')
                        ->join('tbl_branch', 'tbl_equipment.branch_id', '=', 'tbl_branch.branch_id')
                        ->where('tbl_equipment.branch_id', $branchId)
                        ->select(
                            'tbl_equipment.equipment_id', // Select ID for action button
                            'tbl_equipment.equipment_name',
                            'tbl_equipment.equipment_description',
                            'tbl_equipment.equipment_quantity',
                            'tbl_branch.branch_name',
                            DB::raw("CASE 
                                WHEN equipment_quantity > 10 THEN 'Good Stock'
                                WHEN equipment_quantity BETWEEN 1 AND 10 THEN 'Low Stock'
                                ELSE 'Out of Stock'
                            END as stock_status")
                        )
                        ->get()
                ];
                break;

            case 'services':
                $reports['services'] = [
                    'title' => 'Service Availability Report',
                    'description' => 'Available services at ' . $branch->branch_name,
                    'data' => Service::where('branch_id', $branchId)
                        ->with('branch')
                        ->get()
                        ->map(function($service) {
                            return (object)[
                                'service_id' => $service->serv_id,
                                'service_name' => $service->serv_name,
                                'service_description' => $service->serv_description,
                                'service_price' => $service->serv_price,
                                'branch_name' => $service->branch->branch_name ?? 'N/A',
                                'status' => 'Active'
                            ];
                        })
                ];
                break;

            case 'inventory':
                $reports['inventory'] = [
                    'title' => 'Inventory Status Report',
                    'description' => 'Product inventory for ' . $branch->branch_name,
                    'data' => Product::where('branch_id', $branchId)
                        ->with('branch')
                        ->get()
                        ->map(function($product) {
                            $quantity = $product->prod_quantity ?? $product->prod_stocks ?? 0;
                            $status = $quantity > 20 ? 'Good Stock' : 
                                            ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
                            
                            return (object)[
                                'product_id' => $product->prod_id,
                                'product_name' => $product->prod_name,
                                'product_description' => $product->prod_description,
                                'quantity' => $quantity,
                                'unit_price' => $product->prod_price,
                                'branch_name' => $product->branch->branch_name ?? 'N/A',
                                'stock_status' => $status
                            ];
                        })
                ];
                break;

            case 'revenue':
                $totalRevenue = Order::whereBetween('ord_date', [$startDate, $endDate])
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->sum('ord_total');

                $reports['revenue'] = [
                    'title' => 'Revenue Analysis Report',
                    'description' => 'Revenue analysis for ' . $branch->branch_name,
                    'data' => collect([(object)[
                        'branch_name' => $branch->branch_name,
                        'period_start' => $startDate,
                        'period_end' => $endDate,
                        'total_revenue' => $totalRevenue,
                        'total_transactions' => Order::whereBetween('ord_date', [$startDate, $endDate])
                            ->whereHas('user', function($q) use ($branchId) {
                                $q->where('branch_id', $branchId);
                            })->count()
                    ]])
                ];
                break;
        }

        return view('branch-reports', compact(
            'reports',
            'reportType',
            'startDate',
            'endDate',
            'branch'
        ));
    }

    // REMOVED: public function show($reportType, $id) {} to eliminate the modal logic

    public function export(Request $request)
    {
        // ... (export function remains unchanged, as it handles CSV export)
        $user = auth()->user();
        $branchId = $user->branch_id;
        $reportType = $request->get('report', 'appointments');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $filename = $reportType . '_report_' . $branchId . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        // This would require recreating the report generation logic here to avoid code duplication
        // For brevity, assuming $this->index generates reports and we can fetch data, or 
        // passing the query to a dedicated export service. 
        // However, sticking to the provided implementation for now:
        // A proper implementation would re-run the relevant query based on $reportType, 
        // $startDate, $endDate, and $branchId to get the data to populate the CSV.
        // For the sake of modification, I will leave the original callback (which seems incomplete/placeholder).

        $callback = function() use ($reportType, $startDate, $endDate, $branchId) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Branch Report Export']);
            // NOTE: You would need the data fetching logic here to populate the CSV properly.
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    // START: Updated function to unify data retrieval for PDF
    private function getRecordForPDF($reportType, $id, $branchId)
    {
        switch($reportType) {
            case 'appointments':
                // Eager load everything needed for the universal view
                return Appointment::where('appoint_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['pet.owner', 'user.branch'])
                    ->first();

            case 'pets':
                // Need owner data and context for branch check
                return Pet::where('pet_id', $id)
                    ->whereHas('appointments', function($q) use ($branchId) {
                        $q->whereHas('user', function($userQuery) use ($branchId) {
                            $userQuery->where('branch_id', $branchId);
                        });
                    })
                    ->with('owner')
                    ->first();
            
            case 'referrals':
                return Referral::where('ref_id', $id)
                    ->whereHas('appointment.user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['appointment.pet.owner', 'appointment.user.branch'])
                    ->first();

            case 'billing':
                // Use a DB raw query to group billing details for the single bill ID
                return DB::table('tbl_bill')
                    ->where('tbl_bill.bill_id', $id)
                    ->join('tbl_appoint', 'tbl_bill.appoint_id', '=', 'tbl_appoint.appoint_id')
                    ->join('tbl_pet', 'tbl_appoint.pet_id', '=', 'tbl_pet.pet_id')
                    ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                    ->join('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
                    ->join('tbl_branch', 'tbl_user.branch_id', '=', 'tbl_branch.branch_id')
                    ->where('tbl_user.branch_id', $branchId)
                    ->select(
                        'tbl_bill.*', 
                        'tbl_own.own_name', 
                        'tbl_own.own_contactnum', 
                        'tbl_pet.pet_name',
                        'tbl_appoint.appoint_date',
                        'tbl_branch.branch_name',
                        DB::raw('(SELECT COALESCE(SUM(s.serv_price), 0) FROM tbl_appoint_serv aps JOIN tbl_serv s ON aps.serv_id = s.serv_id WHERE aps.appoint_id = tbl_appoint.appoint_id) as pay_total')
                    )
                    ->first();

            case 'sales':
                return Order::where('ord_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['product', 'owner', 'user.branch'])
                    ->first();

            case 'equipment':
                return DB::table('tbl_equipment')
                    ->where('equipment_id', $id)
                    ->where('branch_id', $branchId)
                    ->first();

            case 'services':
                return Service::where('serv_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();

            case 'inventory':
                return Product::where('prod_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();

            default:
                return null;
        }
    }

    public function showDetailedPDF($reportType, $id)
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = Branch::find($branchId);
        
        $data = $this->getRecordForPDF($reportType, $id, $branchId);

        if (!$data) {
            abort(404, 'Record not found or access denied');
        }

        // Define titles for each report type
        $titles = [
            'appointments' => 'Appointment Report',
            'pets' => 'Pet Registration Report',
            'referrals' => 'Referral Report',
            'billing' => 'Billing Statement',
            'sales' => 'Sales Receipt',
            'equipment' => 'Equipment Inventory Report',
            'services' => 'Service Information',
            'inventory' => 'Inventory Status Report',
            'revenue' => 'Revenue Analysis Report', // Not a single record report, but included for completeness
        ];

        $title = $titles[$reportType] ?? 'Branch Report Details';
        
        // Pass the single record data to the PDF view
        // The PDF view should handle presentation based on $reportType and $data content.
        $pdf = \PDF::loadView('branch-reports-pdf', compact('data', 'reportType', 'title', 'branch'));
        
        // Set paper size and orientation
        $pdf->setPaper('letter', 'portrait');
        
        // Enable remote access for images (for public_path('images/header.jpg'))
        $pdf->setOption('isRemoteEnabled', true);
        
        // Stream the PDF (opens in browser)
        return $pdf->stream($title . '_' . $id . '.pdf');
    }
}
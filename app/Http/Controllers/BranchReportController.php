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
use PDF; 

class BranchReportController extends Controller
{
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
                        ->join('tbl_appoint', 'tbl_bill.appoint_id', '=', 'tbl_appoint.appoint_id')
                        ->join('tbl_pet', 'tbl_appoint.pet_id', '=', 'tbl_pet.pet_id')
                        ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                        ->join('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
                        ->join('tbl_branch', 'tbl_user.branch_id', '=', 'tbl_branch.branch_id')
                        ->leftJoin('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                        ->leftJoin('tbl_serv', 'tbl_appoint_serv.serv_id', '=', 'tbl_serv.serv_id')
                        ->whereBetween('tbl_bill.bill_date', [$startDate, $endDate])
                        ->where('tbl_user.branch_id', $branchId)
                        ->select(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name as customer_name',
                            'tbl_pet.pet_name',
                            'tbl_appoint.appoint_date as service_date',
                            DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total'),
                            'tbl_branch.branch_name',
                            'tbl_bill.bill_status as payment_status'
                        )
                        ->groupBy(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name',
                            'tbl_pet.pet_name',
                            'tbl_appoint.appoint_date',
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
                            'tbl_equipment.*',
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

    public function show($reportType, $id)
{
    $user = auth()->user();
    $branchId = $user->branch_id;
    
    $data = null;

    switch($reportType) {
        case 'appointments':
            $data = Appointment::where('appoint_id', $id)
                ->whereHas('user', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->with(['pet.owner', 'user.branch'])
                ->first();
            
            if ($data) {
                $data = [
                    'appoint_id' => $data->appoint_id,
                    'own_name' => $data->pet->owner->own_name ?? 'N/A',
                    'own_contactnum' => $data->pet->owner->own_contactnum ?? 'N/A',
                    'pet_name' => $data->pet->pet_name ?? 'N/A',
                    'pet_breed' => $data->pet->pet_breed ?? 'N/A',
                    'pet_species' => $data->pet->pet_species ?? 'N/A',
                    'appoint_date' => $data->appoint_date,
                    'appoint_time' => $data->appoint_time,
                    'appoint_type' => $data->appoint_type,
                    'appoint_status' => $data->appoint_status,
                    'appoint_description' => $data->appoint_description,
                    'branch_name' => $data->user->branch->branch_name ?? 'N/A',
                    'user_name' => $data->user->name ?? 'N/A'
                ];
            }
            break;

        case 'pets':
            $data = Pet::where('pet_id', $id)
                ->whereHas('appointments', function($q) use ($branchId) {
                    $q->whereHas('user', function($userQuery) use ($branchId) {
                        $userQuery->where('branch_id', $branchId);
                    });
                })
                ->with('owner')
                ->first();
            
            if ($data) {
                $data = [
                    'pet_id' => $data->pet_id,
                    'pet_name' => $data->pet_name,
                    'pet_species' => $data->pet_species,
                    'pet_breed' => $data->pet_breed,
                    'pet_age' => $data->pet_age,
                    'pet_gender' => $data->pet_gender,
                    'pet_birthdate' => $data->pet_birthdate,
                    'pet_weight' => $data->pet_weight,
                    'pet_temperature' => $data->pet_temperature,
                    'pet_registration' => $data->pet_registration,
                    'own_name' => $data->owner->own_name ?? 'N/A',
                    'own_contactnum' => $data->owner->own_contactnum ?? 'N/A',
                    'own_location' => $data->owner->own_location ?? 'N/A'
                ];
            }
            break;

        case 'billing':
            $data = DB::table('tbl_bill')
                ->join('tbl_appoint', 'tbl_bill.appoint_id', '=', 'tbl_appoint.appoint_id')
                ->join('tbl_pet', 'tbl_appoint.pet_id', '=', 'tbl_pet.pet_id')
                ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                ->join('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
                ->leftJoin('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                ->leftJoin('tbl_serv', 'tbl_appoint_serv.serv_id', '=', 'tbl_serv.serv_id')
                ->where('tbl_bill.bill_id', $id)
                ->where('tbl_user.branch_id', $branchId)
                ->select(
                    'tbl_bill.bill_id',
                    'tbl_bill.bill_date',
                    'tbl_bill.bill_status',
                    'tbl_own.own_name',
                    'tbl_own.own_contactnum',
                    'tbl_pet.pet_name',
                    'tbl_appoint.appoint_date',
                    DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total')
                )
                ->groupBy(
                    'tbl_bill.bill_id',
                    'tbl_bill.bill_date',
                    'tbl_bill.bill_status',
                    'tbl_own.own_name',
                    'tbl_own.own_contactnum',
                    'tbl_pet.pet_name',
                    'tbl_appoint.appoint_date'
                )
                ->first();
            
            if ($data) {
                $data = (array) $data;
            }
            break;

        case 'sales':
            $data = Order::where('ord_id', $id)
                ->whereHas('user', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->with(['product', 'owner', 'user'])
                ->first();
            
            if ($data) {
                $data = [
                    'ord_id' => $data->ord_id,
                    'ord_date' => $data->ord_date,
                    'ord_quantity' => $data->ord_quantity,
                    'ord_total' => $data->ord_total,
                    'prod_name' => $data->product->prod_name ?? 'N/A',
                    'prod_price' => $data->product->prod_price ?? 0,
                    'own_name' => $data->owner->own_name ?? 'Walk-in Customer',
                    'user_name' => $data->user->name ?? 'N/A'
                ];
            }
            break;

        case 'equipment':
            $data = DB::table('tbl_equipment')
                ->where('equipment_id', $id)
                ->where('branch_id', $branchId)
                ->first();
            
            if ($data) {
                $qty = $data->equipment_quantity;
                $status = $qty > 10 ? 'Good Stock' : ($qty > 0 ? 'Low Stock' : 'Out of Stock');
                
                $data = [
                    'equipment_id' => $data->equipment_id,
                    'equipment_name' => $data->equipment_name,
                    'equipment_description' => $data->equipment_description,
                    'equipment_quantity' => $data->equipment_quantity,
                    'stock_status' => $status
                ];
            }
            break;

        case 'services':
            $data = Service::where('serv_id', $id)
                ->where('branch_id', $branchId)
                ->first();
            
            if ($data) {
                $data = [
                    'serv_id' => $data->serv_id,
                    'serv_name' => $data->serv_name,
                    'serv_description' => $data->serv_description,
                    'serv_price' => $data->serv_price,
                    'service_id' => $data->serv_id
                ];
            }
            break;

        case 'inventory':
            $data = Product::where('prod_id', $id)
                ->where('branch_id', $branchId)
                ->first();
            
            if ($data) {
                $quantity = $data->prod_quantity ?? $data->prod_stocks ?? 0;
                $status = $quantity > 20 ? 'Good Stock' : ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
                
                $data = [
                    'prod_id' => $data->prod_id,
                    'prod_name' => $data->prod_name,
                    'prod_description' => $data->prod_description,
                    'prod_quantity' => $quantity,
                    'prod_price' => $data->prod_price,
                    'stock_status' => $status
                ];
            }
            break;

        case 'referrals':
            $data = Referral::where('ref_id', $id)
                ->whereHas('appointment.user', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                })
                ->with(['appointment.pet.owner', 'appointment.user'])
                ->first();
            
            if ($data) {
                $data = [
                    'ref_id' => $data->ref_id,
                    'ref_date' => $data->ref_date,
                    'own_name' => $data->appointment->pet->owner->own_name ?? 'N/A',
                    'own_contactnum' => $data->appointment->pet->owner->own_contactnum ?? 'N/A',
                    'pet_name' => $data->appointment->pet->pet_name ?? 'N/A',
                    'pet_birthdate' => $data->appointment->pet->pet_birthdate ?? 'N/A',
                    'pet_gender' => $data->appointment->pet->pet_gender ?? 'N/A',
                    'pet_species' => $data->appointment->pet->pet_species ?? 'N/A',
                    'pet_breed' => $data->appointment->pet->pet_breed ?? 'N/A',
                    'medical_history' => $data->medical_history,
                    'tests_conducted' => $data->tests_conducted,
                    'medications_given' => $data->medications_given,
                    'ref_description' => $data->ref_description,
                    'ref_by' => $data->appointment->user->branch->branch_name ?? 'N/A',
                    'ref_to' => $data->ref_to,
                    'user_name' => $data->appointment->user->name ?? 'N/A'
                ];
            }
            break;

        default:
            return response()->json(['error' => 'Invalid report type'], 400);
    }

    if (!$data) {
        return response()->json(['error' => 'Record not found or access denied'], 404);
    }

    return response()->json($data);
}

    public function export(Request $request)
    {
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

        $callback = function() use ($reportType, $startDate, $endDate, $branchId) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Branch Report Export']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function showDetailedPDF($reportType, $id)
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $branch = Branch::find($branchId);
        
        $data = null;
        $title = '';

        switch($reportType) {
            case 'appointments':
                $data = Appointment::where('appoint_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['pet.owner', 'user.branch'])
                    ->first();
                $title = 'Appointment Report';
                break;

            case 'pets':
                $data = Pet::where('pet_id', $id)
                    ->whereHas('appointments', function($q) use ($branchId) {
                        $q->whereHas('user', function($userQuery) use ($branchId) {
                            $userQuery->where('branch_id', $branchId);
                        });
                    })
                    ->with('owner')
                    ->first();
                $title = 'Pet Registration Report';
                break;

            case 'referrals':
                $data = Referral::where('ref_id', $id)
                    ->whereHas('appointment.user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['appointment.pet.owner', 'appointment.user.branch'])
                    ->first();
                $title = 'Referral Report';
                break;

            case 'billing':
                $data = DB::table('tbl_bill')
                    ->join('tbl_appoint', 'tbl_bill.appoint_id', '=', 'tbl_appoint.appoint_id')
                    ->join('tbl_pet', 'tbl_appoint.pet_id', '=', 'tbl_pet.pet_id')
                    ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                    ->join('tbl_user', 'tbl_appoint.user_id', '=', 'tbl_user.user_id')
                    ->leftJoin('tbl_appoint_serv', 'tbl_appoint.appoint_id', '=', 'tbl_appoint_serv.appoint_id')
                    ->leftJoin('tbl_serv', 'tbl_appoint_serv.serv_id', '=', 'tbl_serv.serv_id')
                    ->where('tbl_bill.bill_id', $id)
                    ->where('tbl_user.branch_id', $branchId)
                    ->select(
                        'tbl_bill.*',
                        'tbl_own.own_name',
                        'tbl_own.own_contactnum',
                        'tbl_pet.pet_name',
                        'tbl_appoint.appoint_date',
                        DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total')
                    )
                    ->groupBy(
                        'tbl_bill.bill_id',
                        'tbl_bill.bill_date',
                        'tbl_bill.bill_status',
                        'tbl_own.own_name',
                        'tbl_own.own_contactnum',
                        'tbl_pet.pet_name',
                        'tbl_appoint.appoint_date'
                    )
                    ->first();
                $title = 'Billing Report';
                break;

            case 'sales':
                $data = Order::where('ord_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['product', 'owner', 'user'])
                    ->first();
                $title = 'Sales Report';
                break;

            case 'equipment':
                $data = DB::table('tbl_equipment')
                    ->where('equipment_id', $id)
                    ->where('branch_id', $branchId)
                    ->first();
                $title = 'Equipment Inventory Report';
                break;

            case 'services':
                $data = Service::where('serv_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();
                $title = 'Service Report';
                break;

            case 'inventory':
                $data = Product::where('prod_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();
                $title = 'Inventory Report';
                break;

            default:
                abort(404, 'Invalid report type');
        }

        if (!$data) {
            abort(404, 'Record not found or access denied');
        }

        // Generate PDF using dompdf
        $pdf = \PDF::loadView('branch-reports-pdf', compact('data', 'reportType', 'title', 'branch'));
        
        // Set paper size and orientation
        $pdf->setPaper('letter', 'portrait');
        
        // Enable remote access for images
        $pdf->setOption('isRemoteEnabled', true);
        
        // Stream the PDF (opens in browser)
        return $pdf->stream($title . '_' . $id . '.pdf');
    }
}
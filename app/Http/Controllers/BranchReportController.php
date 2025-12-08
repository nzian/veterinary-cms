<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Billing;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\Pet;
use App\Models\Order;
use App\Models\Owner;
use App\Models\Referral;
use App\Models\Visit;
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
        //dd(session()->all());
        $user = auth()->user();
        $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
        $branchMode = session('branch_mode') === 'active';
        $activeBranchId = session('active_branch_id');

        // Determine which branch to use
        if ($isSuperAdmin && $branchMode && $activeBranchId) {
            $branchId = $activeBranchId;
        } else {
            $branchId = $user->branch_id;
        }
        $branch = Branch::find($branchId);
        if (!$branch) {
            return redirect()->back()->with('error', 'Branch not found');
        }

        // Get filter parameters
        $reportType = $request->get('report', 'visits');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Get additional filter parameters
        $visitStatus = $request->get('visit_status', '');
        $petSpecies = $request->get('pet_species', '');
        $billingStatus = $request->get('billing_status', '');
        $serviceCategory = $request->get('service_category', '');
        $stockStatus = $request->get('stock_status', '');
        $productType = $request->get('product_type', '');
        $referralStatus = $request->get('referral_status', '');
        $equipmentCategory = $request->get('equipment_category', '');
        
        // Check if dates are valid (not empty and not placeholder format)
        $hasValidDates = !empty($startDate) && !empty($endDate) && 
                        $startDate !== 'dd/mm/yyyy' && $endDate !== 'dd/mm/yyyy';
        
        // Initialize reports array
        $reports = [];
        DB::enableQueryLog();

        // Generate reports based on type - ALL FILTERED BY BRANCH
        switch($reportType) {
            case 'visits':
                $query = Visit::whereHas('user', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });

                //dd($query->get());
                
                // Only apply date filter if valid dates provided
                if ($hasValidDates) {
                    $query->whereBetween('visit_date', [$startDate, $endDate]);
                }
                
                // Apply visit status filter
                if (!empty($visitStatus) && $visitStatus !== 'all') {
                    $query->where('visit_status', $visitStatus);
                }
                //dd($query->with(['pet.owner', 'user.branch', 'services'])->get());
                $reports['visits'] = [
                    'title' => 'Visit Management Report',
                    'description' => 'Complete visit records for ' . $branch->branch_name,
                    'data' => $query->with(['pet.owner', 'user.branch', 'services'])
                        ->get()
                        ->map(function($visit) {
                            return (object)[
                                '_id' => $visit->visit_id, // Hidden ID for PDF link
                                'owner_name' => $visit->pet->owner->own_name ?? 'N/A',
                                'owner_contact' => str_replace(',', '', $visit->pet->owner->own_contactnum ?? 'N/A'),
                                'owner_location' => $visit->pet->owner->own_location ?? 'N/A',
                                'pet_name' => $visit->pet->pet_name ?? 'N/A',
                                'pet_species' => $visit->pet->pet_species ?? 'N/A',
                                'pet_breed' => $visit->pet->pet_breed ?? 'N/A',
                                'pet_age' => $visit->pet->pet_age ?? 'N/A',
                                'pet_gender' => $visit->pet->pet_gender ?? 'N/A',
                                'branch_name' => $visit->user->branch->branch_name ?? 'N/A',
                                'veterinarian' => $visit->user->user_name ?? 'N/A',
                                'visit_date' => $visit->visit_date,
                                'patient_type' => is_object($visit->patient_type) ? $visit->patient_type->value : $visit->patient_type,
                                'status' => $visit->visit_status,
                                'workflow_status' => $visit->workflow_status ?? 'N/A',
                                'weight' => $visit->weight ?? 'N/A',
                                'temperature' => $visit->temperature ?? 'N/A',
                                'services' => $visit->services->pluck('serv_name')->join(', ')
                            ];
                        })
                ];
                //dd($reports['visits']);
                //dd(DB::getQueryLog());
                break;

            case 'pets':
                // Filter pets by visits that belong to the branch
                $petQuery = Pet::whereHas('visits', function($q) use ($branchId) {
                    $q->whereHas('user', function($userQuery) use ($branchId) {
                        $userQuery->where('branch_id', $branchId);
                    });
                });
                
                // Only apply date filter if valid dates provided
                if ($hasValidDates) {
                    $petQuery->whereBetween('pet_registration', [$startDate, $endDate]);
                }
                
                // Apply species filter
                if (!empty($petSpecies) && $petSpecies !== 'all') {
                    $petQuery->where('pet_species', $petSpecies);
                }
                
                $reports['pets'] = [
                    'title' => 'Pet Registration Report',
                    'description' => 'Registered pets at ' . $branch->branch_name,
                    'data' => $petQuery->with('owner')
                        ->get()
                        ->map(function($pet) {
                            return (object)[
                                '_id' => $pet->pet_id, // Hidden ID for PDF link
                                'owner_name' => $pet->owner->own_name ?? 'N/A',
                                'owner_contact' => str_replace(',', '', $pet->owner->own_contactnum ?? 'N/A'),
                                'owner_location' => $pet->owner->own_location ?? 'N/A',
                                'pet_name' => $pet->pet_name,
                                'pet_species' => $pet->pet_species,
                                'pet_breed' => $pet->pet_breed,
                                'pet_age' => $pet->pet_age,
                                'pet_gender' => $pet->pet_gender,
                                'pet_birthdate' => $pet->pet_birthdate ?? 'N/A',
                                'registration_date' => $pet->pet_registration
                            ];
                        })
                ];
                break;

            case 'billing':
                $billingQuery = DB::table('tbl_bill')
                    ->join('tbl_visit_record', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
                    ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                    ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                    ->join('tbl_user', 'tbl_visit_record.user_id', '=', 'tbl_user.user_id')
                    ->join('tbl_branch', 'tbl_user.branch_id', '=', 'tbl_branch.branch_id')
                    ->leftJoin('tbl_visit_service', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
                    ->leftJoin('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
                    ->where('tbl_user.branch_id', $branchId);
                
                // Only apply date filter if valid dates provided
                if ($hasValidDates) {
                    $billingQuery->whereBetween('tbl_bill.bill_date', [$startDate, $endDate]);
                }
                
                // Apply billing status filter
                if (!empty($billingStatus) && $billingStatus !== 'all') {
                    $billingQuery->where('tbl_bill.bill_status', $billingStatus);
                }
                
                $reports['billing'] = [
                    'title' => 'Financial Billing Report',
                    'description' => 'Billing records for ' . $branch->branch_name,
                    'data' => $billingQuery
                        ->select(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name as customer_name',
                            DB::raw('REPLACE(tbl_own.own_contactnum, ",", "") as owner_contact'),
                            'tbl_pet.pet_name',
                            'tbl_pet.pet_species',
                            'tbl_visit_record.visit_date as service_date',
                            DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total'),
                            'tbl_branch.branch_name',
                            'tbl_user.user_name as veterinarian',
                            'tbl_bill.bill_status as payment_status',
                            'tbl_bill.bill_date'
                        )
                        ->groupBy(
                            'tbl_bill.bill_id',
                            'tbl_own.own_name',
                            'tbl_own.own_contactnum',
                            'tbl_pet.pet_name',
                            'tbl_pet.pet_species',
                            'tbl_visit_record.visit_date',
                            'tbl_branch.branch_name',
                            'tbl_user.user_name',
                            'tbl_bill.bill_status',
                            'tbl_bill.bill_date'
                        )
                        ->get()
                        ->map(function($bill) {
                            $bill->_id = $bill->bill_id; // Hidden ID for PDF link
                            unset($bill->bill_id);
                            return $bill;
                        })
                ];
                break;

            case 'sales':
                $salesQuery = Order::whereHas('user', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
                
                // Only apply date filter if valid dates provided
                if ($hasValidDates) {
                    $salesQuery->whereBetween('ord_date', [$startDate, $endDate]);
                }
                
                // Apply product type filter
                if (!empty($productType) && $productType !== 'all') {
                    $salesQuery->whereHas('product', function($q) use ($productType) {
                        $q->where('prod_category', $productType);
                    });
                }
                
                $reports['sales'] = [
                    'title' => 'Product Sales Report',
                    'description' => 'Sales transactions for ' . $branch->branch_name,
                    'data' => $salesQuery->with(['product', 'owner', 'user'])
                        ->get()
                        ->map(function($order) {
                            return (object)[
                                '_id' => $order->ord_id, // Hidden ID for PDF link
                                'sale_date' => $order->ord_date,
                                'customer_name' => $order->owner->own_name ?? 'Walk-in',
                                'customer_contact' => $order->owner ? str_replace(',', '', $order->owner->own_contactnum ?? 'N/A') : 'N/A',
                                'product_name' => $order->product->prod_name ?? 'N/A',
                                'product_category' => $order->product->prod_category ?? 'N/A',
                                'product_description' => $order->product->prod_description ?? 'N/A',
                                'quantity_sold' => $order->ord_quantity,
                                'unit_price' => $order->product->prod_price ?? 0,
                                'total_amount' => $order->ord_total,
                                'cashier' => $order->user->user_name ?? $order->user->name ?? 'N/A',
                                'branch_name' => $order->user->branch->branch_name ?? 'N/A'
                            ];
                        })
                ];
                break;

            case 'referrals':
                $referralQuery = Referral::where(function($q) use ($branchId) {
                    $q->where('ref_from', $branchId)
                      ->orWhere('ref_to', $branchId);
                });

                // Only apply date filter if valid dates provided
                if ($hasValidDates) {
                    $referralQuery->whereBetween('ref_date', [$startDate, $endDate]);
                }

                // Apply referral status filter
                if (!empty($referralStatus) && $referralStatus !== 'all') {
                    $referralQuery->where('ref_status', $referralStatus);
                }

                $reports['referrals'] = [
                    'title' => 'Referral Report',
                    'description' => 'Patient referrals (interbranch and external) for ' . $branch->branch_name,
                    'data' => $referralQuery->with(['pet.owner', 'refFromBranch', 'refToBranch', 'visit.user'])
                        ->get()
                        ->map(function($referral) {
                            return (object)[
                                '_id' => $referral->ref_id, // Hidden ID for PDF link
                                'ref_date' => $referral->ref_date,
                                'owner_name' => $referral->pet && $referral->pet->owner ? $referral->pet->owner->own_name : 'N/A',
                                'owner_contact' => $referral->pet && $referral->pet->owner ? str_replace(',', '', $referral->pet->owner->own_contactnum ?? 'N/A') : 'N/A',
                                'pet_name' => $referral->pet ? $referral->pet->pet_name : 'N/A',
                                'pet_species' => $referral->pet ? $referral->pet->pet_species : 'N/A',
                                'pet_breed' => $referral->pet ? $referral->pet->pet_breed : 'N/A',
                                'referral_reason' => $referral->ref_description ?? 'N/A',
                                'referred_by' => optional($referral->refFromBranch)->branch_name ?? (optional(optional($referral->visit)->user)->user_name ?? 'External'),
                                'referred_to' => optional($referral->refToBranch)->branch_name ?? 'External',
                                'ref_type' => $referral->ref_type ?? 'N/A',
                                'ref_status' => $referral->ref_status ?? 'N/A'
                            ];
                        })
                ];
                break;

            case 'equipment':

                $equipmentQuery = DB::table('tbl_equipment')
                    ->join('tbl_branch', 'tbl_equipment.branch_id', '=', 'tbl_branch.branch_id')
                    ->where('tbl_equipment.branch_id', $branchId);

                // Apply equipment category filter
                if (!empty($equipmentCategory) && $equipmentCategory !== 'all') {
                    $equipmentQuery->where('tbl_equipment.equipment_category', $equipmentCategory);
                }

                $equipmentData = $equipmentQuery->select(
                        'tbl_equipment.equipment_id',
                        'tbl_equipment.equipment_name',
                        'tbl_equipment.equipment_category',
                        'tbl_equipment.equipment_description',
                        'tbl_branch.branch_name',
                        'tbl_equipment.equipment_available',
                        'tbl_equipment.equipment_maintenance',
                        'tbl_equipment.equipment_quantity as total_in_use',
                        'tbl_equipment.equipment_out_of_service'
                    )
                    ->get();

                // Map to new structure for the report
                $equipmentData = $equipmentData->map(function($equipment) {
                    return (object) [
                        '_id' => $equipment->equipment_id, // Hidden ID for PDF link
                        'equipment_name' => $equipment->equipment_name,
                        'equipment_category' => $equipment->equipment_category,
                        'equipment_description' => $equipment->equipment_description ?? 'N/A',
                        'branch_name' => $equipment->branch_name,
                        'total_in_use' => $equipment->total_in_use ?? 0,
                        'total_maintenance' => $equipment->equipment_maintenance ?? 0,
                        'total_available' => $equipment->equipment_available ?? 0,
                        'total_out_of_service' => $equipment->equipment_out_of_service ?? 0
                    ];
                });

                $reports['equipment'] = [
                    'title' => 'Equipment Inventory Report',
                    'description' => 'Equipment inventory for ' . $branch->branch_name,
                    'data' => $equipmentData
                ];
                break;

            case 'services':
                $servicesQuery = Service::where('branch_id', $branchId);
                
                // Apply service category filter
                if (!empty($serviceCategory) && $serviceCategory !== 'all') {
                    $servicesQuery->where('serv_type', $serviceCategory);
                }
                
                $reports['services'] = [
                    'title' => 'Service Availability Report',
                    'description' => 'Available services at ' . $branch->branch_name,
                    'data' => $servicesQuery->with('branch')
                        ->get()
                        ->map(function($service) {
                            return (object)[
                                '_id' => $service->serv_id, // Hidden ID for PDF link
                                'service_name' => $service->serv_name,
                                'service_type' => $service->serv_type ?? 'General',
                                'service_description' => $service->serv_description ?? 'N/A',
                                'service_price' => $service->serv_price,
                                'branch_name' => $service->branch->branch_name ?? 'N/A',
                                'branch_address' => $service->branch->branch_address ?? 'N/A',
                                'status' => 'Active'
                            ];
                        })
                ];
                break;

            case 'inventory':
                // Get all products without type filtering
                $inventoryQuery = Product::where('branch_id', $branchId);
                
                $inventoryData = $inventoryQuery->with('branch')
                    ->get()
                    ->map(function($product) use ($productType) {
                        $quantity = $product->prod_quantity ?? $product->prod_stocks ?? 0;
                        $status = $quantity > 20 ? 'Good Stock' : 
                                        ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
                        
                        return (object)[
                            '_id' => $product->prod_id, // Hidden ID for PDF link
                            'product_name' => $product->prod_name,
                            'product_type' => $product->prod_type ?? $product->prod_category ?? 'N/A',
                            'product_category' => $product->prod_category ?? 'N/A',
                            'product_description' => $product->prod_description ?? 'N/A',
                            'total_pull_out' => $product->prod_pullout ?? 0,
                            'total_damage' => $product->prod_damaged ?? 0,
                            'total_stocks' => $quantity,
                            'unit_price' => ($productType === 'sales') ? $product->prod_price : null,
                            'branch_name' => $product->branch->branch_name ?? 'N/A',
                            'branch_address' => $product->branch->branch_address ?? 'N/A',
                            'stock_status' => $status
                        ];
                    });
                
                // Apply product type filter for display filtering only
                if (!empty($productType) && $productType !== 'all') {
                    $inventoryData = $inventoryData->filter(function($product) use ($productType) {
                        return strtolower($product->product_type) === strtolower($productType);
                    });
                }
                
                // Apply stock status filter
                if (!empty($stockStatus) && $stockStatus !== 'all') {
                    $inventoryData = $inventoryData->filter(function($product) use ($stockStatus) {
                        return $product->stock_status === $stockStatus;
                    });
                }
                
                $reports['inventory'] = [
                    'title' => 'Inventory Status Report',
                    'description' => 'Product inventory for ' . $branch->branch_name,
                    'data' => $inventoryData
                ];
                break;

            case 'revenue':
                $periodStart = $hasValidDates ? $startDate : Carbon::now()->startOfYear()->format('Y-m-d');
                $periodEnd = $hasValidDates ? $endDate : Carbon::now()->format('Y-m-d');

                $totalSales = Order::whereBetween('ord_date', [$periodStart, $periodEnd])
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->sum('ord_total');

                $totalBillings = Billing::whereBetween('bill_date', [$periodStart, $periodEnd])
                    ->where('branch_id', $branchId)
                    ->sum('total_amount');

                $totalRevenue = $totalSales + $totalBillings;

                $reports['revenue'] = [
                    'title' => 'Revenue Analysis Report',
                    'description' => 'Revenue analysis for ' . $branch->branch_name,
                    'data' => collect([(object)[
                        'branch_name' => $branch->branch_name,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                        'total_sales' => $totalSales,
                        'total_billings' => $totalBillings,
                        'total_revenue' => $totalRevenue,
                        'total_transactions' => Order::whereBetween('ord_date', [$periodStart, $periodEnd])
                            ->whereHas('user', function($q) use ($branchId) {
                                $q->where('branch_id', $branchId);
                            })->count()
                    ]])
                ];
                break;
                
            /*case 'batch_history':
                $batchQuery = DB::table('tbl_product_batch')
                    ->join('tbl_product', 'tbl_product_batch.prod_id', '=', 'tbl_product.prod_id')
                    ->join('tbl_branch', 'tbl_product.branch_id', '=', 'tbl_branch.branch_id')
                    ->where('tbl_product.branch_id', $branchId);
                
                // Apply date filter if valid dates provided
                if ($hasValidDates) {
                    $batchQuery->whereBetween('tbl_product_batch.batch_expiry', [$startDate, $endDate]);
                }
                
                // Apply product type filter
                if (!empty($productType) && $productType !== 'all') {
                    $batchQuery->where('tbl_product.prod_category', $productType);
                }
                
                $reports['batch_history'] = [
                    'title' => 'Product Batch History Report',
                    'description' => 'Product batch tracking for ' . $branch->branch_name,
                    'data' => $batchQuery->select(
                            'tbl_product_batch.batch_id',
                            'tbl_product.prod_name as product_name',
                            'tbl_product.prod_category as product_type',
                            'tbl_product_batch.batch_number',
                            'tbl_product_batch.batch_quantity',
                            'tbl_product_batch.batch_received_date',
                            'tbl_product_batch.batch_expiry',
                            'tbl_branch.branch_name'
                        )
                        ->get()
                ];
                break;
                
            /*case 'service_usage_history':
                $serviceHistoryQuery = DB::table('tbl_visit_service')
                    ->join('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
                    ->join('tbl_visit_record', 'tbl_visit_service.visit_id', '=', 'tbl_visit_record.visit_id')
                    ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                    ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                    ->join('tbl_user', 'tbl_visit_record.user_id', '=', 'tbl_user.user_id')
                    ->where('tbl_serv.branch_id', $branchId);
                
                // Apply date filter if valid dates provided
                if ($hasValidDates) {
                    $serviceHistoryQuery->whereBetween('tbl_visit_record.visit_date', [$startDate, $endDate]);
                }
                
                // Apply service category filter
                if (!empty($serviceCategory) && $serviceCategory !== 'all') {
                    $serviceHistoryQuery->where('tbl_serv.serv_type', $serviceCategory);
                }
                
                $reports['service_usage_history'] = [
                    'title' => 'Service Usage History Report',
                    'description' => 'Service usage tracking for ' . $branch->branch_name,
                    'data' => $serviceHistoryQuery->select(
                            'tbl_visit_service.visit_service_id',
                            'tbl_serv.serv_name as service_name',
                            'tbl_serv.serv_type as service_category',
                            'tbl_own.own_name as owner_name',
                            'tbl_pet.pet_name',
                            'tbl_visit_record.visit_date',
                            'tbl_user.name as veterinarian',
                            'tbl_serv.serv_price as service_price'
                        )
                        ->get()
                ];
                break;
                
            /*case 'equipment_assignment_history':
                $equipmentAssignmentQuery = DB::table('tbl_equipment_assignment')
                    ->join('tbl_equipment', 'tbl_equipment_assignment.equipment_id', '=', 'tbl_equipment.equipment_id')
                    ->join('tbl_user', 'tbl_equipment_assignment.user_id', '=', 'tbl_user.user_id')
                    ->where('tbl_equipment.branch_id', $branchId);
                
                // Apply date filter if valid dates provided
                if ($hasValidDates) {
                    $equipmentAssignmentQuery->whereBetween('tbl_equipment_assignment.assigned_date', [$startDate, $endDate]);
                }
                
                // Apply equipment category filter
                if (!empty($equipmentCategory) && $equipmentCategory !== 'all') {
                    $equipmentAssignmentQuery->where('tbl_equipment.equipment_category', $equipmentCategory);
                }
                
                $reports['equipment_assignment_history'] = [
                    'title' => 'Equipment Assignment History Report',
                    'description' => 'Equipment assignment tracking for ' . $branch->branch_name,
                    'data' => $equipmentAssignmentQuery->select(
                            'tbl_equipment_assignment.assignment_id',
                            'tbl_equipment.equipment_name',
                            'tbl_equipment.equipment_category',
                            'tbl_user.name as assigned_to',
                            'tbl_equipment_assignment.assigned_date',
                            'tbl_equipment_assignment.return_date',
                            'tbl_equipment_assignment.assignment_status',
                            'tbl_equipment_assignment.notes'
                        )
                        ->get()
                ];
                break;*/
        }

        return view('branch-reports', compact(
            'reports',
            'reportType',
            'startDate',
            'endDate',
            'branch',
            'visitStatus',
            'petSpecies',
            'billingStatus',
            'serviceCategory',
            'stockStatus',
            'productType',
            'referralStatus',
            'equipmentCategory'
        ));
    }

    // REMOVED: public function show($reportType, $id) {} to eliminate the modal logic

    public function export(Request $request)
    {
        //dd($request->all());
        $user = auth()->user();
        $branchId = $user->branch_id ?? session('active_branch_id');
        $branch = Branch::find($branchId);
        $reportType = $request->get('report', 'visits');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        // Get filter parameters
        $visitStatus = $request->get('visit_status', '');
        $petSpecies = $request->get('pet_species', '');
        $billingStatus = $request->get('billing_status', '');
        $serviceCategory = $request->get('service_category', '');
        $stockStatus = $request->get('stock_status', '');
        $productType = $request->get('product_type', '');
        $referralStatus = $request->get('referral_status', '');
        $equipmentCategory = $request->get('equipment_category', '');

        $filename = $reportType . '_report_' . $branch->branch_name . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        //dd($reportType);
        $callback = function() use ($reportType, $startDate, $endDate, $branchId, $branch, $visitStatus, $petSpecies, $billingStatus, $serviceCategory, $stockStatus, $productType, $referralStatus, $equipmentCategory) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            switch($reportType) {
                case 'visits':
                    fputcsv($file, ['#', 'Visit Date', 'Owner Name', 'Contact', 'Pet Name', 'Patient Type', 'Services', 'Status', 'Branch']);
                    
                    $visitQuery = Visit::whereBetween('visit_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        });
                    
                    // Apply visit status filter
                    if (!empty($visitStatus) && $visitStatus !== 'all') {
                        $visitQuery->where('visit_status', $visitStatus);
                    }
                    
                    $visitQuery->with(['pet.owner', 'user.branch', 'services'])
                        ->chunk(100, function($visits) use ($file) {
                            static $counter = 0;
                            foreach($visits as $visit) {
                                $counter++;
                                fputcsv($file, [
                                    $counter, // # instead of visit_id
                                    $visit->visit_date,
                                    $visit->pet->owner->own_name ?? 'N/A',
                                    $visit->pet->owner->own_contactnum ?? 'N/A',
                                    $visit->pet->pet_name ?? 'N/A',
                                    is_object($visit->patient_type) ? $visit->patient_type->value : $visit->patient_type,
                                    $visit->services->pluck('serv_name')->join(', '),
                                    $visit->visit_status,
                                    $visit->user->branch->branch_name ?? 'N/A'
                                ]);
                            }
                        });
                    break;

                case 'pets':
                    fputcsv($file, ['#', 'Registration Date', 'Owner Name', 'Contact', 'Pet Name', 'Species', 'Breed', 'Age', 'Gender']);
                    
                    Pet::whereBetween('pet_registration', [$startDate, $endDate])
                        ->whereHas('visits', function($q) use ($branchId) {
                            $q->whereHas('user', function($userQuery) use ($branchId) {
                                $userQuery->where('branch_id', $branchId);
                            });
                        })
                        ->with('owner')
                        ->chunk(100, function($pets) use ($file) {
                            static $counter = 0;
                            foreach($pets as $pet) {
                                $counter++;
                                fputcsv($file, [
                                    $counter, // # instead of pet_id
                                    $pet->pet_registration,
                                    $pet->owner->own_name ?? 'N/A',
                                    $pet->owner->own_contactnum ?? 'N/A',
                                    $pet->pet_name,
                                    $pet->pet_species,
                                    $pet->pet_breed,
                                    $pet->pet_age,
                                    $pet->pet_gender
                                ]);
                            }
                        });
                    break;

                case 'billing':
                    fputcsv($file, ['#', 'Service Date', 'Pet Owner', 'Pet Name', 'Total Amount', 'Payment Status', 'Branch']);
                    
                    DB::table('tbl_bill')
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
                            'tbl_bill.bill_date',
                            'tbl_own.own_name',
                            'tbl_pet.pet_name',
                            'tbl_visit_record.visit_date',
                            DB::raw('COALESCE(SUM(tbl_serv.serv_price), 0) as pay_total'),
                            'tbl_branch.branch_name',
                            'tbl_bill.bill_status'
                        )
                        ->groupBy(
                            'tbl_bill.bill_id',
                            'tbl_bill.bill_date',
                            'tbl_own.own_name',
                            'tbl_pet.pet_name',
                            'tbl_visit_record.visit_date',
                            'tbl_branch.branch_name',
                            'tbl_bill.bill_status'
                        )
                        ->chunk(100, function($bills) use ($file) {
                            static $counter = 0;
                            foreach($bills as $bill) {
                                $counter++;
                                fputcsv($file, [
                                    $counter, // # instead of bill_id
                                    $bill->visit_date, // Service Date (visit date)
                                    $bill->own_name,
                                    $bill->pet_name,
                                    $bill->pay_total,
                                    $bill->bill_status,
                                    $bill->branch_name
                                ]);
                            }
                        });
                    break;

                case 'sales':
                    fputcsv($file, ['#', 'Sale Date', 'Customer Name', 'Product Name', 'Quantity Sold', 'Unit Price', 'Total Amount', 'Cashier']);
                    
                    Order::whereBetween('ord_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with(['product', 'owner', 'user'])
                        ->chunk(100, function($orders) use ($file) {
                            static $counter = 0;
                            foreach($orders as $order) {
                                $counter++;
                                
                                fputcsv($file, [
                                    $counter, // # instead of ord_id
                                    $order->ord_date,
                                    $order->owner->own_name ?? 'Walk-in',
                                    $order->product->prod_name ?? 'N/A',
                                    $order->ord_quantity,
                                    $order->product->prod_price ?? 0,
                                    $order->ord_total,
                                    $order->user->user_name ?? $order->user->name ?? 'N/A'
                                ]);
                            }
                        });
                    break;

                case 'referrals':
                    fputcsv($file, ['Referral ID', 'Referral Date', 'Owner Name', 'Pet Name', 'Referral Reason', 'Referred By', 'Referred To']);
                    
                    Referral::whereBetween('ref_date', [$startDate, $endDate])
                        ->whereHas('visit.user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with(['pet.owner', 'refFromBranch', 'refToBranch'])
                        ->chunk(100, function($referrals) use ($file) {
                            foreach($referrals as $referral) {
                                fputcsv($file, [
                                    $referral->ref_id,
                                    $referral->ref_date,
                                    $referral->pet->owner->own_name ?? 'N/A',
                                    $referral->pet->pet_name ?? 'N/A',
                                    $referral->ref_description,
                                    $referral->visit->user->user_name ?? $referral->visit->user->name ?? 'N/A',
                                    optional($referral->refToBranch)->branch_name ?? 'External'
                                ]);
                            }
                        });
                    break;

                case 'equipment':
                    fputcsv($file, ['Equipment ID', 'Equipment Name', 'Description', 'Quantity', 'Stock Status', 'Branch']);
                    
                    DB::table('tbl_equipment')
                        ->join('tbl_branch', 'tbl_equipment.branch_id', '=', 'tbl_branch.branch_id')
                        ->where('tbl_equipment.branch_id', $branchId)
                        ->select(
                            'tbl_equipment.equipment_id',
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
                        ->chunk(100, function($equipment) use ($file) {
                            foreach($equipment as $item) {
                                fputcsv($file, [
                                    $item->equipment_id,
                                    $item->equipment_name,
                                    $item->equipment_description,
                                    $item->equipment_quantity,
                                    $item->stock_status,
                                    $item->branch_name
                                ]);
                            }
                        });
                    break;

                case 'services':
                    fputcsv($file, ['#', 'Service Name', 'Service Type', 'Description', 'Price', 'Branch', 'Status']);
                    
                    $serviceCounter = 1;
                    Service::where('branch_id', $branchId)
                        ->with('branch')
                        ->chunk(100, function($services) use ($file, &$serviceCounter) {
                            foreach($services as $service) {
                                fputcsv($file, [
                                    $serviceCounter++,
                                    $service->serv_name,
                                    $service->serv_type ?? 'General',
                                    $service->serv_description,
                                    $service->serv_price,
                                    $service->branch->branch_name ?? 'N/A',
                                    'Active'
                                ]);
                            }
                        });
                    break;

                case 'inventory':
                    // Dynamic headers based on product type filter
                    $headers = ['#', 'Product Name', 'Product Type', 'Description', 'Total Pull Out', 'Total Damage', 'Total Stocks'];
                    if (!empty($productType) && $productType === 'sales') {
                        $headers[] = 'Unit Price';
                    }
                    $headers = array_merge($headers, ['Stock Status', 'Branch']);
                    fputcsv($file, $headers);
                    
                    $counter = 1;
                    Product::where('branch_id', $branchId)
                        ->with('branch')
                        ->chunk(100, function($products) use ($file, &$counter, $productType) {
                            foreach($products as $product) {
                                $quantity = $product->prod_quantity ?? $product->prod_stocks ?? 0;
                                $status = $quantity > 20 ? 'Good Stock' : ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
                                
                                $row = [
                                    $counter++,
                                    $product->prod_name,
                                    $product->prod_type ?? $product->prod_category ?? 'N/A',
                                    $product->prod_description,
                                    $product->prod_pullout ?? 0,
                                    $product->prod_damaged ?? 0,
                                    $quantity
                                ];
                                
                                if (!empty($productType) && $productType === 'sales') {
                                    $row[] = $product->prod_price;
                                }
                                
                                $row = array_merge($row, [
                                    $status,
                                    $product->branch->branch_name ?? 'N/A'
                                ]);
                                
                                fputcsv($file, $row);
                            }
                        });
                    break;

                case 'revenue':
                    fputcsv($file, ['Branch', 'Period Start', 'Period End', 'Total Sales', 'Total Billings', 'Total Revenue', 'Total Transactions']);

                    $totalSales = Order::whereBetween('ord_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->sum('ord_total');

                    $totalBillings = Billing::whereBetween('bill_date', [$startDate, $endDate])
                        ->where('branch_id', $branchId)
                        ->sum('total_amount');

                    $totalRevenue = $totalSales + $totalBillings;

                    $totalTransactions = Order::whereBetween('ord_date', [$startDate, $endDate])
                        ->whereHas('user', function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->count();

                    fputcsv($file, [
                        $branch->branch_name,
                        $startDate,
                        $endDate,
                        $totalSales,
                        $totalBillings,
                        $totalRevenue,
                        $totalTransactions
                    ]);
                    break;

                default:
                    fputcsv($file, ['Error']);
                    fputcsv($file, ['Invalid report type: ' . $reportType]);
                    fputcsv($file, ['Valid types are: visits, pets, billing, sales, referrals, equipment, services, inventory, revenue']);
                    break;
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    // START: Updated function to unify data retrieval for PDF - returns mapped data like table
    private function getRecordForPDF($reportType, $id, $branchId)
    {
        switch($reportType) {
            case 'visits':
                $visit = Visit::where('visit_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['pet.owner', 'user.branch', 'services'])
                    ->first();
                
                if (!$visit) return null;
                
                return (object)[
                    'owner_name' => $visit->pet->owner->own_name ?? 'N/A',
                    'owner_contact' => str_replace(',', '', $visit->pet->owner->own_contactnum ?? 'N/A'),
                    'owner_location' => $visit->pet->owner->own_location ?? 'N/A',
                    'pet_name' => $visit->pet->pet_name ?? 'N/A',
                    'pet_species' => $visit->pet->pet_species ?? 'N/A',
                    'pet_breed' => $visit->pet->pet_breed ?? 'N/A',
                    'pet_age' => $visit->pet->pet_age ?? 'N/A',
                    'pet_gender' => $visit->pet->pet_gender ?? 'N/A',
                    'branch_name' => $visit->user->branch->branch_name ?? 'N/A',
                    'veterinarian' => $visit->user->user_name ?? 'N/A',
                    'visit_date' => $visit->visit_date,
                    'patient_type' => is_object($visit->patient_type) ? $visit->patient_type->value : $visit->patient_type,
                    'status' => $visit->visit_status,
                    'workflow_status' => $visit->workflow_status ?? 'N/A',
                    'weight' => $visit->weight ?? 'N/A',
                    'temperature' => $visit->temperature ?? 'N/A',
                    'services' => $visit->services->pluck('serv_name')->join(', ')
                ];

            case 'pets':
                $pet = Pet::where('pet_id', $id)
                    ->whereHas('visits', function($q) use ($branchId) {
                        $q->whereHas('user', function($userQuery) use ($branchId) {
                            $userQuery->where('branch_id', $branchId);
                        });
                    })
                    ->with('owner')
                    ->first();
                
                if (!$pet) return null;
                
                return (object)[
                    'owner_name' => $pet->owner->own_name ?? 'N/A',
                    'owner_contact' => str_replace(',', '', $pet->owner->own_contactnum ?? 'N/A'),
                    'owner_location' => $pet->owner->own_location ?? 'N/A',
                    'pet_name' => $pet->pet_name,
                    'pet_species' => $pet->pet_species,
                    'pet_breed' => $pet->pet_breed,
                    'pet_age' => $pet->pet_age,
                    'pet_gender' => $pet->pet_gender,
                    'pet_birthdate' => $pet->pet_birthdate ?? 'N/A',
                    'registration_date' => $pet->pet_registration
                ];
            
            case 'referrals':
                $referral = Referral::where('ref_id', $id)
                    ->where(function($q) use ($branchId) {
                        $q->where('ref_from', $branchId)
                          ->orWhere('ref_to', $branchId)
                          ->orWhereHas('visit.user', function($userQuery) use ($branchId) {
                              $userQuery->where('branch_id', $branchId);
                          });
                    })
                    ->with(['pet.owner', 'visit.pet.owner', 'visit.user.branch', 'refFromBranch', 'refToBranch', 'refByBranch'])
                    ->first();
                
                if (!$referral) return null;
                
                return (object)[
                    'ref_date' => $referral->ref_date,
                    'owner_name' => $referral->pet && $referral->pet->owner ? $referral->pet->owner->own_name : 'N/A',
                    'owner_contact' => $referral->pet && $referral->pet->owner ? str_replace(',', '', $referral->pet->owner->own_contactnum ?? 'N/A') : 'N/A',
                    'pet_name' => $referral->pet ? $referral->pet->pet_name : 'N/A',
                    'pet_species' => $referral->pet ? $referral->pet->pet_species : 'N/A',
                    'pet_breed' => $referral->pet ? $referral->pet->pet_breed : 'N/A',
                    'referral_reason' => $referral->ref_description ?? 'N/A',
                    'referred_by' => optional($referral->refFromBranch)->branch_name ?? (optional(optional($referral->visit)->user)->user_name ?? 'External'),
                    'referred_to' => optional($referral->refToBranch)->branch_name ?? 'External',
                    'ref_type' => $referral->ref_type ?? 'N/A',
                    'ref_status' => $referral->ref_status ?? 'N/A'
                ];

            case 'billing':
                $bill = DB::table('tbl_bill')
                    ->where('tbl_bill.bill_id', $id)
                    ->join('tbl_visit_record', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
                    ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                    ->join('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                    ->join('tbl_user', 'tbl_visit_record.user_id', '=', 'tbl_user.user_id')
                    ->join('tbl_branch', 'tbl_user.branch_id', '=', 'tbl_branch.branch_id')
                    ->where('tbl_user.branch_id', $branchId)
                    ->select(
                        'tbl_own.own_name as customer_name',
                        DB::raw('REPLACE(tbl_own.own_contactnum, ",", "") as owner_contact'),
                        'tbl_pet.pet_name',
                        'tbl_pet.pet_species',
                        'tbl_visit_record.visit_date as service_date',
                        'tbl_branch.branch_name',
                        'tbl_user.user_name as veterinarian',
                        'tbl_bill.bill_status as payment_status',
                        'tbl_bill.bill_date',
                        DB::raw('(SELECT COALESCE(SUM(s.serv_price), 0) FROM tbl_visit_service vs JOIN tbl_serv s ON vs.serv_id = s.serv_id WHERE vs.visit_id = tbl_visit_record.visit_id) as pay_total')
                    )
                    ->first();
                
                if (!$bill) return null;
                
                return (object)[
                    'customer_name' => $bill->customer_name ?? 'N/A',
                    'owner_contact' => $bill->owner_contact ?? 'N/A',
                    'pet_name' => $bill->pet_name ?? 'N/A',
                    'pet_species' => $bill->pet_species ?? 'N/A',
                    'service_date' => $bill->service_date,
                    'bill_date' => $bill->bill_date,
                    'branch_name' => $bill->branch_name ?? 'N/A',
                    'veterinarian' => $bill->veterinarian ?? 'N/A',
                    'pay_total' => $bill->pay_total ?? 0,
                    'payment_status' => $bill->payment_status ?? 'N/A'
                ];

            case 'sales':
                $order = Order::where('ord_id', $id)
                    ->whereHas('user', function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->with(['product', 'owner', 'user.branch'])
                    ->first();
                
                if (!$order) return null;
                
                return (object)[
                    'sale_date' => $order->ord_date,
                    'customer_name' => $order->owner->own_name ?? 'Walk-in',
                    'customer_contact' => $order->owner ? str_replace(',', '', $order->owner->own_contactnum ?? 'N/A') : 'N/A',
                    'product_name' => $order->product->prod_name ?? 'N/A',
                    'product_category' => $order->product->prod_category ?? 'N/A',
                    'product_description' => $order->product->prod_description ?? 'N/A',
                    'quantity_sold' => $order->ord_quantity,
                    'unit_price' => $order->product->prod_price ?? 0,
                    'total_amount' => $order->ord_total,
                    'cashier' => $order->user->user_name ?? $order->user->name ?? 'N/A',
                    'branch_name' => $order->user->branch->branch_name ?? 'N/A'
                ];

            case 'equipment':
                $equipment = DB::table('tbl_equipment')
                    ->join('tbl_branch', 'tbl_equipment.branch_id', '=', 'tbl_branch.branch_id')
                    ->where('tbl_equipment.equipment_id', $id)
                    ->where('tbl_equipment.branch_id', $branchId)
                    ->select(
                        'tbl_equipment.equipment_name',
                        'tbl_equipment.equipment_category',
                        'tbl_equipment.equipment_description',
                        'tbl_branch.branch_name',
                        'tbl_branch.branch_address',
                        'tbl_equipment.equipment_available',
                        'tbl_equipment.equipment_maintenance',
                        'tbl_equipment.equipment_quantity as total_in_use',
                        'tbl_equipment.equipment_out_of_service'
                    )
                    ->first();
                
                if (!$equipment) return null;
                
                return (object)[
                    'equipment_name' => $equipment->equipment_name,
                    'equipment_category' => $equipment->equipment_category,
                    'equipment_description' => $equipment->equipment_description ?? 'N/A',
                    'branch_name' => $equipment->branch_name,
                    'branch_address' => $equipment->branch_address ?? 'N/A',
                    'total_in_use' => $equipment->total_in_use ?? 0,
                    'total_maintenance' => $equipment->equipment_maintenance ?? 0,
                    'total_available' => $equipment->equipment_available ?? 0,
                    'total_out_of_service' => $equipment->equipment_out_of_service ?? 0
                ];

            case 'services':
                $service = Service::where('serv_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();
                
                if (!$service) return null;
                
                return (object)[
                    'service_name' => $service->serv_name,
                    'service_type' => $service->serv_type ?? 'General',
                    'service_description' => $service->serv_description ?? 'N/A',
                    'service_price' => $service->serv_price,
                    'branch_name' => $service->branch->branch_name ?? 'N/A',
                    'branch_address' => $service->branch->branch_address ?? 'N/A',
                    'status' => 'Active'
                ];

            case 'inventory':
                $product = Product::where('prod_id', $id)
                    ->where('branch_id', $branchId)
                    ->with('branch')
                    ->first();
                
                if (!$product) return null;
                
                $quantity = $product->prod_quantity ?? $product->prod_stocks ?? 0;
                $status = $quantity > 20 ? 'Good Stock' : 
                                ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
                
                return (object)[
                    'product_name' => $product->prod_name,
                    'product_type' => $product->prod_type ?? $product->prod_category ?? 'N/A',
                    'product_category' => $product->prod_category ?? 'N/A',
                    'product_description' => $product->prod_description ?? 'N/A',
                    'total_pull_out' => $product->prod_pullout ?? 0,
                    'total_damage' => $product->prod_damaged ?? 0,
                    'total_stocks' => $quantity,
                    'unit_price' => $product->prod_price ?? null,
                    'branch_name' => $product->branch->branch_name ?? 'N/A',
                    'branch_address' => $product->branch->branch_address ?? 'N/A',
                    'stock_status' => $status
                ];

            default:
                return null;
        }
    }

    public function showDetailedPDF($reportType, $id)
    {
        $user = auth()->user();
        $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
        $branchMode = session('branch_mode') === 'active';
        $activeBranchId = session('active_branch_id');

        // Determine which branch to use (same logic as index method)
        if ($isSuperAdmin && $branchMode && $activeBranchId) {
            $branchId = $activeBranchId;
        } else {
            $branchId = $user->branch_id;
        }
        
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            abort(404, 'Branch not found');
        }
        
        $data = $this->getRecordForPDF($reportType, $id, $branchId);

        if (!$data) {
            abort(404, 'Record not found or access denied');
        }

        // Define titles for each report type
        $titles = [
            'visits' => 'Visit Report',
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
        try {
            // Check if view exists
            if (!view()->exists('branch-reports-pdf')) {
                abort(500, 'PDF view not found: branch-reports-pdf');
            }
            
            $pdf = \PDF::loadView('branch-reports-pdf', compact('data', 'reportType', 'title', 'branch'));
            
            // Set paper size and orientation - Landscape for PDF view to fit all details
            $pdf->setPaper('letter', 'landscape');
            
            // Enable remote access for images (for public_path('images/header.jpg'))
            $pdf->setOption('isRemoteEnabled', true);
            
            // Stream the PDF (opens in browser)
            return $pdf->stream($title . '_' . $id . '.pdf');
        } catch (\Illuminate\View\Exceptions\ViewNotFoundException $e) {
            abort(500, 'PDF view not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            abort(500, 'Error generating PDF: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        }
    }
}
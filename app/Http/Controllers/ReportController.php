<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        //dd(session()->all());
        $reportType = $request->get('report', 'visits');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $branch = $request->get('branch');
        $status = $request->get('status');

        $reports = $this->generateReports($startDate, $endDate, $branch, $status);
        $branches = DB::table('tbl_branch')->select('branch_id', 'branch_name')->get();

        return view('report', compact('reports', 'branches', 'reportType', 'startDate', 'endDate', 'branch', 'status'));
    }

    /**
     * Generate PDF using universal layout
     */
    public function generatePDF($reportType, $recordId)
    {
        // Validate report type
        $validReportTypes = [
            'visits', 'owner_pets', 'visit_billing', 'product_purchases', 'referrals',
            'visit_services', 'branch_visits', 'multi_service_visits', 'billing_orders',
            'product_sales', 'payment_collection', 'branch_payments', 'medical_history',
            'prescriptions', 'referral_medical', 'branch_users', 'branch_equipment',
            'damaged_products', 'service_utilization'
        ];
        
        if (!in_array($reportType, $validReportTypes)) {
            abort(404, 'Invalid report type: ' . $reportType);
        }
        
        try {
        $record = $this->getRecordByType($reportType, $recordId);
        if (!$record) {
                \Log::warning('Record not found', ['reportType' => $reportType, 'recordId' => $recordId]);
                abort(404, 'Record not found for report type: ' . $reportType . ' with ID: ' . $recordId);
            }
            
            // Convert to object if it's an array
            if (is_array($record)) {
                $record = (object) $record;
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching record', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reportType' => $reportType,
                'recordId' => $recordId
            ]);
            abort(500, 'Error fetching record: ' . $e->getMessage());
        }

        // --- Handle report-specific data transformation before PDF rendering ---
        if ($reportType === 'referrals' || $reportType === 'referral_medical') {
            // NOTE: The record fetched by getReferralDetails now includes referred_to_name
            // We just ensure the legacy 'ref_to' (ID) is handled if needed
            if (isset($record->ref_to)) {
                $referredToBranch = DB::table('tbl_branch')->where('branch_id', $record->ref_to)->select('branch_name')->first();
                $record->referred_to_name = $referredToBranch->branch_name ?? $record->ref_to;
            }
        }

        if ($reportType === 'prescriptions') {
            // FIX: Decode and format raw medication data for display in PDF detail view/modal
            if (isset($record->medication) && $record->medication) {
            $medications = json_decode($record->medication, true);
            $formattedMedication = '';
            if (is_array($medications)) {
                foreach ($medications as $med) {
                    $formattedMedication .= ($med['name'] ?? 'N/A') . ' (' . ($med['dosage'] ?? 'N/A') . ')\n';
                }
            } else {
                $formattedMedication = $record->medication; // Fallback
            }
            $record->formatted_medication = trim($formattedMedication);
            } else {
                $record->formatted_medication = 'No medication data';
            }
        }

        // Define titles for each report type
        $titles = [
            'visits' => 'Visit Details',
            'owner_pets' => 'Pet Owner and Their Pets',
            'visit_billing' => 'Visit with Billing & Payment',
            'product_purchases' => 'Product Purchase Report',
            'referrals' => 'Inter-Branch Referral Report',
            'visit_services' => 'Services in Visits',
            'branch_visits' => 'Branch Visit Schedule',
            'multi_service_visits' => 'Multiple Services Visits',
            'billing_orders' => 'Billing with Orders',
            'product_sales' => 'Product Sales by User',
            'payment_collection' => 'Payment Collection Report',
            'branch_payments' => 'Branch Payment Summary',
            'medical_history' => 'Medical History & Follow-Ups',
            'prescriptions' => 'Prescriptions by Branch',
            'referral_medical' => 'Referrals with Medical History',
            'branch_users' => 'Users Assigned per Branch',
            'branch_equipment' => 'Branch Equipment Summary',
            'damaged_products' => 'Complete Stock Movement History',
            'service_utilization' => 'Service Utilization per Branch',
        ];
        
        // Pass metadata for print layout header/footer
        $reportMetadata = [
            'report_name' => $titles[$reportType] ?? 'Report Details',
            'generated_by' => auth()->check() ? auth()->user()->user_name : 'System',
            'generated_at' => Carbon::now()->format('M d, Y h:i A'),
        ];

        // Generate PDF
        try {
            // Use the correct view path - Laravel uses dot notation for nested views
            $viewPath = 'reports.pdf.universal';
            
            // Verify view exists and file exists
            $viewFile = resource_path('views/reports/pdf/universal.blade.php');
            if (!file_exists($viewFile)) {
                \Log::error('PDF view file not found', ['path' => $viewFile]);
                abort(500, 'PDF view file does not exist at: ' . $viewFile);
            }
            
            if (!View::exists($viewPath)) {
                \Log::error('PDF view not found in Laravel', ['path' => $viewPath, 'file_exists' => file_exists($viewFile)]);
                abort(500, 'PDF view not found: ' . $viewPath);
            }
            
            // Ensure record is an object (not null)
            if (!$record || (!is_object($record) && !is_array($record))) {
                \Log::error('Invalid record data', ['reportType' => $reportType, 'recordId' => $recordId, 'record' => $record]);
                abort(500, 'Invalid record data for PDF generation');
            }
            
            // Prepare data for view
            $viewData = [
            'record' => $record,
            'reportType' => $reportType,
            'title' => $titles[$reportType] ?? 'Report Details',
            'reportMetadata' => $reportMetadata,
            ];
            
            \Log::info('Generating PDF', ['reportType' => $reportType, 'recordId' => $recordId, 'viewPath' => $viewPath]);
            
            // Generate PDF directly - let DomPDF handle the view
            $pdf = PDF::loadView($viewPath, $viewData);
            // Landscape for PDF view to fit all details
        $pdf->setPaper('letter', 'landscape');
            // Enable remote access for images
            $pdf->setOption('isRemoteEnabled', true);
        // Sanitize filename: remove all invalid chars from title and recordId
        $safeTitle = preg_replace('#[\\/\%\?\*\:\|"<>]#', '', $titles[$reportType] ?? 'report');
        $safeRecordId = preg_replace('#[\\/\%\?\*\:\|"<>]#', '', $recordId);
        $filename = $safeTitle . '_' . $safeRecordId . '.pdf';
        return $pdf->stream($filename);
        } catch (\Illuminate\View\Exceptions\ViewNotFoundException $e) {
            \Log::error('ViewNotFoundException', ['error' => $e->getMessage(), 'reportType' => $reportType]);
            abort(500, 'PDF view not found: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('PDF generation error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'reportType' => $reportType,
                'recordId' => $recordId
            ]);
            abort(500, 'Error generating PDF: ' . $e->getMessage() . ' | Check logs for details');
        }
    }

    /**
     * Get record based on report type for PDF generation
     */
    private function getRecordByType($reportType, $recordId)
    {
        switch($reportType) {
            case 'visits':
            case 'branch_visits':
            case 'multi_service_visits':
                return $this->getVisitDetails($recordId);

            case 'owner_pets':
                return $this->getOwnerPetsDetails($recordId);

            case 'visit_billing':
                return $this->getVisitBillingDetails($recordId);

            case 'product_purchases':
                return $this->getProductPurchaseDetails($recordId);

            case 'referrals':
            case 'referral_medical':
                return $this->getReferralDetails($recordId);

            case 'visit_services':
                return $this->getVisitServiceDetails($recordId);

            case 'branch_users':
                return $this->getBranchUserDetails($recordId);

            case 'billing_orders':
                return $this->getBillingDetails($recordId);

            case 'product_sales':

                return $this->getSalesDetails($recordId);

            case 'payment_collection':
            case 'branch_payments':
                return $this->getPaymentCollectionDetails($recordId);

            case 'medical_history':
                return $this->getMedicalHistoryDetails($recordId);

            case 'prescriptions':
                return $this->getPrescriptionDetails($recordId);

            case 'branch_equipment':
                return $this->getBranchEquipmentDetails($recordId);

            case 'damaged_products':
                return $this->getDamagedProductDetails($recordId);

            case 'service_utilization':
                return $this->getServiceUtilizationDetails($recordId);

            default:
                return null;
        }
    }

    public function export(Request $request)
    {
        //dd($request->all());
        $reportType = $request->get('report', 'visits');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $branch = $request->get('branch');
        $status = $request->get('status');

        $reports = $this->generateReports($startDate, $endDate, $branch, $status);
        $reportData = $reports[$reportType]['data'] ?? collect();

        if ($reportData->isEmpty()) {
            return back()->with('error', 'No data available for export.');
        }

        $filename = $reports[$reportType]['title'] . '_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reportData) {
            $file = fopen('php://output', 'w');
            
            if ($reportData->isNotEmpty()) {
                $firstRow = $reportData->first();
                $headers = array_keys((array) $firstRow);
                $cleanHeaders = array_map(function($header) {
                    $header = str_replace(['_count', '_sum', 'raw_medication_data'], ['', '', 'Medication'], $header);
                    return ucfirst(str_replace('_', ' ', $header));
                }, $headers);
                fputcsv($file, $cleanHeaders);
                
                foreach ($reportData as $row) {
                    $data = (array) $row;
                    fputcsv($file, array_values($data));
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function viewRecord(Request $request, $reportType, $recordId)
    {
        $record = $this->getRecordByType($reportType, $recordId);
        return response()->json($record);
    }

    // ==================== GENERATE REPORTS ====================

    private function generateReports($startDate = null, $endDate = null, $branch = null, $status = null)
    {
        return [
            'visits' => [
                'title' => 'Visit Management Report',
                'description' => 'Pet Owner owns the Pet who has a Visit handled by a Veterinarian with Services',
                'data' => $this->getVisitsReport($startDate, $endDate, $branch, $status)
            ],
            'owner_pets' => [
                'title' => 'Pet Owner and Their Pets',
                'description' => 'Complete list of pet owners with all their registered pets',
                'data' => $this->getOwnerPetsReport($startDate, $endDate)
            ],
            'visit_billing' => [
                'title' => 'Visit with Billing & Payment',
                'description' => 'Pet Owner has a Pet with a Visit that includes Billing and Payment',
                'data' => $this->getVisitBillingReport($startDate, $endDate, $branch)
            ],
            'product_purchases' => [
                'title' => 'Product Purchase Report',
                'description' => 'Pet Owner purchases a Product through an Order managed by a User',
                'data' => $this->getProductPurchasesReport($startDate, $endDate, $branch)
            ],
            'referrals' => [
                'title' => 'Inter-Branch Referrals Report',
                'description' => 'Pet Owner has a Pet with an Appointment and a User creates a Referral to another Branch',
                'data' => $this->getReferralsReport($startDate, $endDate, $branch)
            ],
            'visit_services' => [
                'title' => 'Services in Visits',
                'description' => 'Services provided during a Visit for a Pet owned by a Pet Owner and managed by a Veterinarian',
                'data' => $this->getVisitServicesReport($startDate, $endDate, $branch)
            ],
            'branch_users' => [
                'title' => 'Users Assigned per Branch',
                'description' => 'Users assigned per Branches with role and status information',
                'data' => $this->getBranchUsersReport($branch)
            ],
            'branch_visits' => [
                'title' => 'Branch Visit Schedule',
                'description' => 'Veterinarian handles a Visit for a Pet owned by a Pet Owner at a Branch',
                'data' => $this->getBranchVisitsReport($startDate, $endDate, $branch)
            ],
            // 'billing_orders' => [
            //     'title' => 'Billing with Orders',
            //     'description' => 'Billing information with related Orders to the Pet Owner',
            //     'data' => $this->getBillingOrdersReport($startDate, $endDate)
            // ],
            'product_sales' => [
                'title' => 'Product Sales by User',
                'description' => 'Users sell Products to Pet Owners with details of quantity and date of purchase',
                'data' => $this->getProductSalesReport($startDate, $endDate, $branch)
            ],
            'payment_collection' => [
                'title' => 'Payment Collection Report',
                'description' => 'Payments collected by assigned User for each Appointment with Billing details',
                'data' => $this->getPaymentCollectionReport($startDate, $endDate, $branch)
            ],
            'medical_history' => [
                'title' => 'Medical History & Follow-Ups',
                'description' => 'Pets with Medical History and Follow-Up Appointments',
                'data' => $this->getMedicalHistoryReport($startDate, $endDate)
            ],
            'prescriptions' => [
                'title' => 'Prescriptions by Branch',
                'description' => 'Prescriptions issued by Users per Branch',
                'data' => $this->getPrescriptionsReport($startDate, $endDate, $branch)
            ],
            'multi_service_visits' => [
                'title' => 'Multiple Services Visits',
                'description' => 'Visits with Multiple Services and Total Service Price',
                'data' => $this->getMultiServiceVisitsReport($startDate, $endDate)
            ],
            'branch_equipment' => [
                'title' => 'Branch Equipment Summary',
                'description' => 'Branch Equipment Usage Summary by category',
                'data' => $this->getBranchEquipmentReport($branch)
            ],
            'damaged_products' => [
                'title' => 'Damaged/Pullout Products',
                'description' => 'Orders with Damaged or Pullout Products',
                'data' => $this->getDamagedProductsReport($startDate, $endDate)
            ],
            'referral_medical' => [
                'title' => 'Referrals with Medical History',
                'description' => 'Appointments with Referrals and Related Medical History',
                'data' => $this->getReferralMedicalReport($startDate, $endDate)
            ],
            'branch_payments' => [
                'title' => 'Branch Payment Summary',
                'description' => 'Payments by Branch and User',
                'data' => $this->getBranchPaymentsReport($startDate, $endDate, $branch)
            ],
            'service_utilization' => [
                'title' => 'Service Utilization per Branch',
                'description' => 'Services Utilization per Branch',
                'data' => $this->getServiceUtilizationReport($startDate, $endDate, $branch)
            ]
        ];
    }

    // ==================== REPORT QUERY METHODS (FIXED) ====================

    /**
     * Get Visits Report - based on tbl_visit_record
     */
    private function getVisitsReport($startDate, $endDate, $branch, $status)
    {
        $query = DB::table('tbl_visit_record as v')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
            ->leftJoin('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->select(
                'v.visit_id',
                'o.own_name as owner_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as owner_contact'),
                'o.own_location',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'p.pet_age',
                'p.pet_gender',
                'v.visit_date',
                'v.patient_type',
                'v.visit_status as status',
                'v.workflow_status',
                'v.weight',
                'v.temperature',
                'b.branch_name',
                'b.branch_address',
                'u.user_name as veterinarian',
                DB::raw("GROUP_CONCAT(DISTINCT s.serv_name SEPARATOR ', ') as services")
            )
            ->groupBy(
                'v.visit_id', 'o.own_name', 'o.own_contactnum', 'o.own_location', 'p.pet_name', 
                'p.pet_species', 'p.pet_breed', 'p.pet_age', 'p.pet_gender', 'v.visit_date', 'v.patient_type',
                'v.visit_status', 'v.workflow_status', 'v.weight', 'v.temperature',
                'b.branch_name', 'b.branch_address', 'u.user_name'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);
        if ($status) $query->where('v.visit_status', $status);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->visit_id;
            return $item;
        });
    }

    /**
     * FIX: Remove comma from owner contact number
     */
    private function getOwnerPetsReport($startDate, $endDate)
    {
        $query = DB::table('tbl_own as o')
            ->leftJoin('tbl_pet as p', 'o.own_id', '=', 'p.own_id')
            ->select(
                'o.own_id',
                'o.own_name as owner_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'o.own_location',
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_name SEPARATOR ", ") as pet_names'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_species SEPARATOR ", ") as pet_species'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_breed SEPARATOR ", ") as pet_breeds'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_age SEPARATOR ", ") as pet_ages'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_gender SEPARATOR ", ") as pet_genders'),
                DB::raw('COUNT(p.pet_id) as total_pets')
            )
            ->groupBy('o.own_id', 'o.own_name', 'o.own_contactnum', 'o.own_location');

        // Filter by pet registration date
        if ($startDate) $query->whereDate('p.pet_registration', '>=', $startDate); 
        if ($endDate) $query->whereDate('p.pet_registration', '<=', $endDate);

        $results = $query->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->own_id;
            return $item;
        });
    }

    private function getVisitBillingReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_own as o')
            ->join('tbl_pet as p', 'o.own_id', '=', 'p.own_id')
            ->join('tbl_visit_record as v', 'p.pet_id', '=', 'v.pet_id')
            ->join('tbl_bill as b', 'v.visit_id', '=', 'b.visit_id')
            ->join('tbl_pay as pay', 'b.bill_id', '=', 'pay.bill_id')
            ->leftJoin('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
            ->select(
                'v.visit_id',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'v.patient_type',
                'b.bill_status',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name',
                'u.user_name as veterinarian'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->visit_id;
            return $item;
        });
    }

    private function getProductPurchasesReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_ord as o2')
            ->join('tbl_prod as pr', 'o2.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as o', 'o2.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'o2.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'o2.ord_id',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'pr.prod_name',
                'pr.prod_category',
                'pr.prod_description',
                'o2.ord_quantity',
                'o2.ord_total',
                'o2.ord_date',
                'u.user_name as handled_by',
                'b.branch_name',
                'b.branch_address'
            );

        if ($startDate) $query->whereDate('o2.ord_date', '>=', $startDate);
        if ($endDate) $query->whereDate('o2.ord_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('o2.ord_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->ord_id;
            // Format currency fields
            if (isset($item->ord_total)) {
                $item->ord_total_formatted = 'PHP ' . number_format($item->ord_total, 2);
            }
            if (isset($item->prod_price)) {
                $item->prod_price_formatted = 'PHP ' . number_format($item->prod_price, 2);
            }
            return $item;
        });
    }

    /**
     * Get Referrals Report - based on visits instead of appointments
     */
    private function getReferralsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return collect();
        }

        $query = DB::table('tbl_ref as r')
            ->join('tbl_visit_record as v', 'r.visit_id', '=', 'v.visit_id')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'r.ref_by', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b1', 'u.branch_id', '=', 'b1.branch_id') // Referring Branch
            ->leftJoin('tbl_branch as b2', 'r.ref_to', '=', 'b2.branch_id') // Referred To Branch
            ->select(
                'r.ref_id',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'r.ref_date',
                'r.ref_description',
                'r.ref_type',
                'r.ref_status',
                'b1.branch_name as referred_by',
                'b2.branch_name as referred_to',
                'u.user_name as created_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);
        if ($branch) $query->where('b1.branch_id', $branch);

        $results = $query->orderBy('r.ref_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->ref_id;
            return $item;
        });
    }

    private function getVisitServicesReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_visit_service as vs')
            ->join('tbl_visit_record as v', 'vs.visit_id', '=', 'v.visit_id')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'vs.id as visit_service_id',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                's.serv_name',
                's.serv_type',
                's.serv_price',
                'vs.status as service_status',
                'v.visit_date',
                'v.patient_type',
                'u.user_name as veterinarian',
                'b.branch_name',
                'b.branch_address'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->visit_service_id;
            return $item;
        });
    }

    /**
     * FIX: Contact number must be a straight phone number no comma
     */
    private function getBranchUsersReport($branch)
    {
        $query = DB::table('tbl_user as u')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'u.user_id',
                'u.user_name',
                'u.user_role',
                'u.user_email',
                'b.branch_name',
                'b.branch_address',
                'u.user_status',
                DB::raw('REPLACE(u.user_contactNum, ",", "") as user_contactNum')
            );

        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('b.branch_name')->orderBy('u.user_role')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->user_id;
            return $item;
        });
    }

    /**
     * Branch Visit Schedule Report
     */
    private function getBranchVisitsReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_visit_record as v')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
            ->leftJoin('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->select(
                'v.visit_id',
                'u.user_name as veterinarian',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'v.patient_type',
                'v.visit_status',
                'v.weight',
                'v.temperature',
                'b.branch_name',
                'b.branch_address',
                DB::raw("GROUP_CONCAT(DISTINCT s.serv_name SEPARATOR ', ') as services")
            )
            ->groupBy(
                'v.visit_id', 'u.user_name', 'o.own_name', 'o.own_contactnum', 'p.pet_name',
                'p.pet_species', 'p.pet_breed', 'v.visit_date', 'v.patient_type', 
                'v.visit_status', 'v.weight', 'v.temperature', 'b.branch_name', 'b.branch_address'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->visit_id;
            return $item;
        });
    }

    private function getBillingOrdersReport($startDate, $endDate)
    {
        $query = DB::table('tbl_bill as b')
            ->join('tbl_ord as ord', 'b.ord_id', '=', 'ord.ord_id')
            ->join('tbl_prod as pr', 'ord.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as o', 'ord.own_id', '=', 'o.own_id')
            ->select(
                'b.bill_id',
                'o.own_name',
                'b.bill_date',
                'b.bill_status',
                'pr.prod_name',
                'ord.ord_quantity',
                'ord.ord_total'
            );

        if ($startDate) $query->whereDate('b.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('b.bill_date', '<=', $endDate);

        return $query->orderBy('b.bill_date', 'desc')->get();
    }

    private function getProductSalesReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_ord as ord')
            ->join('tbl_user as u', 'ord.user_id', '=', 'u.user_id')
            ->join('tbl_prod as pr', 'ord.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as o', 'ord.own_id', '=', 'o.own_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'ord.ord_id',
                'u.user_name as seller',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'pr.prod_name',
                'pr.prod_category',
                'ord.ord_quantity',
                'pr.prod_price as unit_price',
                'ord.ord_total',
                'ord.ord_date',
                'b.branch_name',
                'b.branch_address'
            );

        if ($startDate) $query->whereDate('ord.ord_date', '>=', $startDate);
        if ($endDate) $query->whereDate('ord.ord_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('ord.ord_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->ord_id;
            // Format currency fields
            if (isset($item->ord_total)) {
                $item->ord_total_formatted = 'PHP ' . number_format($item->ord_total, 2);
            }
            if (isset($item->unit_price)) {
                $item->unit_price_formatted = 'PHP ' . number_format($item->unit_price, 2);
            }
            return $item;
        });
    }

    private function getPaymentCollectionReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay as pay')
            ->join('tbl_bill as b', 'pay.bill_id', '=', 'b.bill_id')
            ->join('tbl_visit_record as v', 'b.visit_id', '=', 'v.visit_id')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id') // FIX: Join Branch via User
            ->select(
                'pay.pay_id',
                'u.user_name as collected_by',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'v.visit_date',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'b.bill_status',
                'br.branch_name',
                'br.branch_address'
            );

        if ($startDate) $query->whereDate('b.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('b.bill_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->pay_id;
            // Format currency fields
            if (isset($item->pay_total)) {
                $item->pay_total_formatted = 'PHP ' . number_format($item->pay_total, 2);
            }
            if (isset($item->pay_cashAmount)) {
                $item->pay_cashAmount_formatted = 'PHP ' . number_format($item->pay_cashAmount, 2);
            }
            if (isset($item->pay_change)) {
                $item->pay_change_formatted = 'PHP ' . number_format($item->pay_change, 2);
            }
            return $item;
        });
    }

    private function getMedicalHistoryReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_history')) {
            return collect();
        }

        $query = DB::table('tbl_history as h')
            ->join('tbl_pet as p', 'h.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'h.user_id', '=', 'u.user_id')
            ->select(
                'h.history_id',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'o.own_name',
                'h.visit_date',
                'h.diagnosis',
                'h.treatment',
                'h.medication',
                'h.follow_up_date',
                'h.notes',
                'u.user_name as veterinarian'
            );

        if ($startDate) $query->whereDate('h.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('h.visit_date', '<=', $endDate);

        $results = $query->orderBy('h.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->history_id;
            return $item;
        });
    }

   /**
     * FIX: Concatenate product name and instruction/dosage from raw JSON data 
     * into a single semicolon-separated string for the report list view.
     */
    private function getPrescriptionsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_prescription')) {
            return collect();
        }

        // NOTE: Laravel's Query Builder doesn't natively handle JSON column functions 
        // across all MySQL versions elegantly, especially for concatenation.
        // We will stick to the previous raw selection and keep the JSON formatting in Blade
        // to maintain compatibility, but we will change the selected column to reflect the structure.

        $query = DB::table('tbl_prescription as pr')
            ->join('tbl_user as u', 'pr.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'pr.branch_id', '=', 'b.branch_id')
            ->join('tbl_pet as p', 'pr.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->select(
                'pr.prescription_id',
                'pr.prescription_date',
                // Keep the raw data selection for the controller/database layer
                'pr.medication as raw_medication_data', 
                'pr.differential_diagnosis',
                'pr.notes',
                'u.user_name as prescribed_by',
                'b.branch_name',
                'b.branch_address',
                'p.pet_name',
                'p.pet_species',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'o.own_name'
            );

        if ($startDate) $query->whereDate('pr.prescription_date', '>=', $startDate);
        if ($endDate) $query->whereDate('pr.prescription_date', '<=', $endDate);
        if ($branch) $query->where('pr.branch_id', $branch);

        $results = $query->orderBy('pr.prescription_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->prescription_id;
            return $item;
        });
    }
    private function getMultiServiceVisitsReport($startDate, $endDate)
    {
        $query = DB::table('tbl_visit_service as vs')
            ->join('tbl_visit_record as v', 'vs.visit_id', '=', 'v.visit_id')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->select(
                'v.visit_id',
                'v.visit_date',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                DB::raw('GROUP_CONCAT(s.serv_name SEPARATOR ", ") as services'),
                DB::raw('SUM(s.serv_price) as total_service_price'),
                DB::raw('COUNT(vs.serv_id) as service_count')
            )
            ->groupBy('v.visit_id', 'v.visit_date', 'p.pet_name', 'p.pet_species', 'p.pet_breed', 'o.own_name', 'o.own_contactnum')
            ->having('service_count', '>', 1);

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);

        $results = $query->orderBy('v.visit_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->visit_id;
            return $item;
        });
    }

    /**
     * FIX: Get Equipment Assignment History from tbl_boarding_record and tbl_equipment_assignment_log (like prodServEquip equipment tab)
     */
    private function getBranchEquipmentReport($branch)
    {
        $movements = collect();

        // Get equipment assignments from boarding records
        if (DB::getSchemaBuilder()->hasTable('tbl_boarding_record') && 
            DB::getSchemaBuilder()->hasColumn('tbl_boarding_record', 'equipment_id')) {
            $query = DB::table('tbl_boarding_record as br')
                ->join('tbl_visit_record as v', 'br.visit_id', '=', 'v.visit_id')
                ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
                ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
                ->leftJoin('tbl_equipment as eq', 'br.equipment_id', '=', 'eq.equipment_id')
                ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
                ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
                ->leftJoin('tbl_serv as s', 'br.serv_id', '=', 's.serv_id')
                ->whereNotNull('br.equipment_id')
                ->select(
                    DB::raw('CONCAT(br.visit_id, "-", br.pet_id) as id'),
                    'br.visit_id',
                    'br.pet_id',
                    'br.equipment_id',
                    'br.check_in_date',
                    'br.check_out_date',
                    'br.room_no',
                    'br.status as boarding_status',
                    'br.handled_by',
                    'eq.equipment_name',
                    'eq.equipment_category',
                    'pet.pet_name',
                    'pet.pet_species',
                    'pet.pet_breed',
                    'owner.own_name as owner_name',
                    's.serv_name as service_name',
                    's.serv_type as service_type',
                    'u.user_name as vet_name',
                    'v.visit_date',
                    'b.branch_name',
                    'b.branch_address'
                );

            if ($branch) $query->where('b.branch_id', $branch);

            $boardingAssignments = $query->orderBy('br.check_in_date', 'desc')->get();
            $movements = $movements->concat($boardingAssignments);
        }

        // Get from equipment assignment log if exists
        if (DB::getSchemaBuilder()->hasTable('tbl_equipment_assignment_log')) {
            $query = DB::table('tbl_equipment_assignment_log as eal')
                ->join('tbl_equipment as eq', 'eal.equipment_id', '=', 'eq.equipment_id')
                ->join('tbl_branch as b', 'eq.branch_id', '=', 'b.branch_id')
                ->leftJoin('tbl_visit_record as v', 'eal.visit_id', '=', 'v.visit_id')
                ->leftJoin('tbl_user as u', 'eal.performed_by', '=', 'u.user_id')
                ->leftJoin('tbl_pet as pet', 'eal.pet_id', '=', 'pet.pet_id')
                ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
            ->select(
                    'eal.id',
                    'eal.equipment_id',
                    'eal.action_type',
                    'eal.visit_id',
                    'eal.pet_id',
                    'eal.quantity_changed',
                    'eal.previous_status',
                    'eal.new_status',
                    'eal.reference',
                    'eal.notes as log_notes',
                    'eal.created_at',
                    'eq.equipment_name',
                    'eq.equipment_category',
                    'pet.pet_name',
                    'pet.pet_species',
                    'owner.own_name as owner_name',
                    'u.user_name as performed_by_name',
                'b.branch_name',
                    'b.branch_address'
                );

            if ($branch) $query->where('b.branch_id', $branch);

            $logAssignments = $query->orderBy('eal.created_at', 'desc')->get();
            $movements = $movements->concat($logAssignments);
        }

        // Map to add hidden _id field
        return $movements->map(function($item) {
            $item->_id = $item->id ?? ($item->visit_id . '-' . $item->pet_id);
            return $item;
        });
    }

    /**
     * FIX: Get Complete Stock Movement History from tbl_inventory_transactions (like prodServEquip product tab inventory button)
     * Shows ALL transaction types: sale, restock, damage, pullout, service_usage, adjustment, return
     */
    private function getDamagedProductsReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_inventory_transactions')) {
            return collect();
        }

        $hasPerformedByCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'performed_by');
        $hasUserIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'user_id');
        $hasServIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'serv_id');
        $hasVisitIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'visit_id');
        $hasAppointIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'appoint_id');

        $query = DB::table('tbl_inventory_transactions as it')
            ->join('tbl_prod as p', 'it.prod_id', '=', 'p.prod_id')
            ->leftJoin('tbl_branch as b', 'p.branch_id', '=', 'b.branch_id');

        // Prefer performed_by if it exists, otherwise fallback to user_id if present
        if ($hasPerformedByCol) {
            $query->leftJoin('tbl_user as u', 'it.performed_by', '=', 'u.user_id');
        } elseif ($hasUserIdCol) {
            $query->leftJoin('tbl_user as u', 'it.user_id', '=', 'u.user_id');
        }
        if ($hasServIdCol) {
            $query->leftJoin('tbl_serv as s', 'it.serv_id', '=', 's.serv_id');
        }
        if ($hasVisitIdCol) {
            $query->leftJoin('tbl_visit_record as v', 'it.visit_id', '=', 'v.visit_id');
        }

        $selects = [
            'it.transaction_id as id',
            'it.created_at',
            'it.transaction_type',
            'it.quantity_change',
            'it.reference',
            'it.notes',
            'p.prod_id',
            'p.prod_name',
            'p.prod_category',
            'p.prod_type',
            'b.branch_name',
            'b.branch_address'
        ];

        if ($hasPerformedByCol || $hasUserIdCol) {
            $selects[] = 'u.user_name';
        } else {
            $selects[] = DB::raw('NULL as user_name');
        }
        if ($hasServIdCol) {
            $selects[] = 's.serv_name';
        } else {
            $selects[] = DB::raw('NULL as serv_name');
        }
        if ($hasVisitIdCol) {
            $selects[] = 'it.visit_id';
        } else {
            $selects[] = DB::raw('NULL as visit_id');
        }
        if ($hasAppointIdCol) {
            $selects[] = 'it.appoint_id';
        } else {
            $selects[] = DB::raw('NULL as appoint_id');
        }

        // Get ALL transaction types (not just damage/pullout)
        $query->select($selects);

        if ($startDate) $query->whereDate('it.created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('it.created_at', '<=', $endDate);

        $result = $query->orderBy('it.created_at', 'desc')->get();
        
        // Map to add hidden _id field and format data, ensuring correct column order
        $index = 0;
        return $result->map(function($item) use (&$index) {
            $index++;
            // Format transaction type for display - store as 'type' for column name
            $typeLabels = [
                'restock' => 'Stock Added',
                'sale' => 'POS Sale',
                'service_usage' => 'Service Usage',
                'damage' => 'Damaged',
                'pullout' => 'Pull-out',
                'adjustment' => 'Adjustment',
                'return' => 'Return',
            ];
            
            // Create new object with columns in the desired order
            $orderedItem = (object)[
                '_id' => $item->id,
                'row_number' => $index,
                'prod_name' => $item->prod_name ?? 'N/A',
                'prod_category' => $item->prod_category ?? 'N/A',
                'prod_type' => $item->prod_type ?? 'N/A',
                'branch_name' => $item->branch_name ?? 'N/A',
                'user_name' => $item->user_name ?? 'System',
                'serv_name' => $item->serv_name ?? 'N/A',
                'reference' => $item->reference ?? 'N/A',
                'type' => $typeLabels[$item->transaction_type] ?? ucfirst($item->transaction_type ?? 'N/A'),
                'quantity' => $item->quantity_change ?? 0,
            ];
            
            return $orderedItem;
        });
    }

    private function getReferralMedicalReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return collect();
        }

        $query = DB::table('tbl_ref as r')
            ->leftJoin('tbl_visit_record as v', 'r.visit_id', '=', 'v.visit_id')
            ->leftJoin('tbl_pet as p', function($join) {
                $join->on('p.pet_id', '=', 'r.pet_id')
                     ->orOn('p.pet_id', '=', 'v.pet_id');
            })
            ->leftJoin('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->leftJoin('tbl_user as u', 'r.ref_by', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b1', 'u.branch_id', '=', 'b1.branch_id')
            ->leftJoin('tbl_branch as b2', 'r.ref_to', '=', 'b2.branch_id')
            ->select(
                'r.ref_id',
                'r.ref_date',
                'r.ref_description',
                'r.ref_type',
                'r.ref_status',
                'r.medical_history',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'b1.branch_name as referred_by',
                'b2.branch_name as referred_to',
                'u.user_name as created_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);

        $results = $query->orderBy('r.ref_date', 'desc')->get();
        
        // Map to add hidden _id field
        return $results->map(function($item) {
            $item->_id = $item->ref_id;
            return $item;
        });
    }

    /**
     * FIX: Total Payments must be the count not a peso
     * NOTE: Requires linking Appointment -> User -> Branch for filtering.
     */
    private function getBranchPaymentsReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay as pay')
            ->join('tbl_bill as bl', 'pay.bill_id', '=', 'bl.bill_id')
            ->join('tbl_visit_record as a', 'bl.visit_id', '=', 'a.visit_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id') // FIX: Join Branch via User
            ->select(
                'b.branch_name',
                'b.branch_address',
                'u.user_name as collected_by',
                DB::raw('COUNT(pay.pay_id) as total_payments_count'),
                DB::raw('SUM(pay.pay_total) as total_amount_collected')
            )
            ->groupBy('b.branch_name', 'b.branch_address', 'u.user_name');

        if ($startDate) $query->whereDate('bl.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('bl.bill_date', '<=', $endDate);
        if ($branch) $query->where('b.branch_id', $branch);

        $results = $query->orderBy('total_amount_collected', 'desc')->get();
        
        // Map to add hidden _id field - use branch_name+user_name as identifier
        return $results->map(function($item, $index) {
            // For grouped branch payments, create a composite ID
            $item->_id = md5($item->branch_name . $item->collected_by . $index);
            // Format currency
            if (isset($item->total_amount_collected)) {
                $item->total_amount_collected_formatted = 'PHP ' . number_format($item->total_amount_collected, 2);
            }
            return $item;
        });
    }

    /**
     * FIX: Get Complete Service Usage History from tbl_visit_service (like prodServEquip service tab)
     * Only return: Visit Date, Owner Name, Pet Name, Pet Species, Pet Breed, Patient Type, Serv Name, Serv Type, Total Price, Performed By, Branch Name, Service Status
     */
    private function getServiceUtilizationReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_visit_service')) {
            return collect();
        }

        $query = DB::table('tbl_visit_service as vs')
            ->join('tbl_visit_record as v', 'vs.visit_id', '=', 'v.visit_id')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
            ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
            ->select(
                'vs.id',
                'v.visit_date',
                'owner.own_name as owner_name',
                'pet.pet_name',
                'pet.pet_species',
                'pet.pet_breed',
                'v.patient_type',
                's.serv_name',
                's.serv_type',
                'vs.total_price',
                'u.user_name as performed_by',
                'b.branch_name',
                'vs.status as service_status'
            );

        if ($startDate) $query->whereDate('vs.created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('vs.created_at', '<=', $endDate);
        if ($branch) $query->where('b.branch_id', $branch);

        $result = $query->orderBy('vs.created_at', 'desc')->get();
        
        // Map to add hidden _id field
        return $result->map(function($item) {
            $item->_id = $item->id;
            return $item;
        });
    }

    // ==================== DETAIL VIEW METHODS (FIXED) ====================

    /**
     * FIX: Join Branch via User to resolve 'tbl_appoint.branch_id' error.
     */
    private function getVisitDetails($visitId)
    {
        $visit = DB::table('tbl_visit_record as v')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->leftJoin('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
            ->leftJoin('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->leftJoin('tbl_bill', 'tbl_bill.visit_id', '=', 'v.visit_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->select(
                'o.own_name as owner_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as owner_contact'),
                'o.own_location',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'p.pet_age',
                'p.pet_gender',
                'v.visit_date',
                'v.patient_type',
                'v.visit_status as status',
                'v.workflow_status',
                'v.weight',
                'v.temperature',
                'b.branch_name',
                'b.branch_address',
                'u.user_name as veterinarian',
                DB::raw("GROUP_CONCAT(DISTINCT s.serv_name SEPARATOR ', ') as services"),
                'tbl_bill.bill_status',
                'tbl_pay.pay_total'
            )
            ->where('v.visit_id', $visitId)
            ->groupBy(
                'o.own_name', 'o.own_contactnum', 'o.own_location', 'p.pet_name', 
                'p.pet_species', 'p.pet_breed', 'p.pet_age', 'p.pet_gender', 'v.visit_date', 'v.patient_type',
                'v.visit_status', 'v.workflow_status', 'v.weight', 'v.temperature',
                'b.branch_name', 'b.branch_address', 'u.user_name', 'tbl_bill.bill_id', 'tbl_bill.bill_status', 'tbl_pay.pay_id', 'tbl_pay.pay_total'
            )
            ->first();
        
        return $visit;
    }

    private function getVisitServiceDetails($visitServiceId)
    {
        return DB::table('tbl_visit_service as vs')
            ->join('tbl_visit_record as v', 'v.visit_id', '=', 'vs.visit_id')
            ->join('tbl_serv as s', 's.serv_id', '=', 'vs.serv_id')
            ->join('tbl_pet as p', 'p.pet_id', '=', 'v.pet_id')
            ->join('tbl_own as o', 'o.own_id', '=', 'p.own_id')
            ->leftJoin('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                's.serv_name',
                's.serv_type',
                's.serv_price',
                'v.visit_date',
                'v.patient_type',
                'vs.status as service_status',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'u.user_name',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('vs.id', $visitServiceId)
            ->first();
    }

    private function getVisitBillingDetails($visitId)
    {
        return DB::table('tbl_visit_record as v')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_bill as b', 'b.visit_id', '=', 'v.visit_id')
            ->join('tbl_pay as pay', 'pay.bill_id', '=', 'b.bill_id')
            ->leftJoin('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
            ->select(
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'v.patient_type',
                'b.bill_status',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name',
                'u.user_name'
            )
            ->where('v.visit_id', $visitId)
            ->first();
    }

    private function getPetDetails($petId)
    {
        return DB::table('tbl_pet')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->select('*')
            ->where('tbl_pet.pet_id', $petId)
            ->first();
    }

    private function getBillingDetails($billId)
    {
        return DB::table('tbl_bill as b')
            ->leftJoin('tbl_ord as ord', 'b.ord_id', '=', 'ord.ord_id')
            //->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'ord.own_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'b.bill_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'ord.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('*')
            ->where('b.bill_id', $billId)
            ->first();
    }

    private function getSalesDetails($orderId)
    {
        return DB::table('tbl_ord as ord')
            ->join('tbl_prod as pr', 'pr.prod_id', '=', 'ord.prod_id')
            ->join('tbl_user as u', 'u.user_id', '=', 'ord.user_id')
            ->leftJoin('tbl_own as o', 'o.own_id', '=', 'ord.own_id')
            ->join('tbl_branch as b', 'b.branch_id', '=', 'u.branch_id')
            ->select(
                'ord.ord_date as sale_date',
                'o.own_name as customer_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as customer_contact'),
                'pr.prod_name as product_name',
                'pr.prod_category as product_category',
                'pr.prod_description as product_description',
                'ord.ord_quantity as quantity_sold',
                'pr.prod_price as unit_price',
                'ord.ord_total as total_amount',
                'u.user_name as cashier',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('ord.ord_id', $orderId)
            ->first();
    }

    private function getMedicalDetails($medicalId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_history')) {
            return null;
        }

        return DB::table('tbl_history as h')
            ->join('tbl_pet as p', 'p.pet_id', '=', 'h.pet_id')
            ->join('tbl_own as o', 'o.own_id', '=', 'p.own_id')
            ->join('tbl_user as u', 'u.user_id', '=', 'h.user_id')
            ->select(
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'o.own_name',
                'h.visit_date',
                'h.diagnosis',
                'h.treatment',
                'h.medication',
                'h.follow_up_date',
                'h.notes',
                'u.user_name as veterinarian'
            )
            ->where('h.history_id', $medicalId)
            ->first();
    }

    private function getStaffDetails($userId)
    {
        return DB::table('tbl_user as u')
            ->join('tbl_branch as b', 'b.branch_id', '=', 'u.branch_id')
            ->select(
                'u.user_name',
                'u.user_role',
                'u.user_email',
                'u.user_status',
                DB::raw('REPLACE(u.user_contactNum, ",", "") as user_contactNum'),
                'b.branch_name',
                'b.branch_address'
            )
            ->where('u.user_id', $userId)
            ->first();
    }

    private function getInventoryDetails($prodId)
    {
        $product = DB::table('tbl_prod as pr')
            ->leftJoin('tbl_branch as b', 'b.branch_id', '=', 'pr.branch_id')
            ->select(
                'pr.prod_name as product_name',
                'pr.prod_type as product_type',
                'pr.prod_category as product_category',
                'pr.prod_description as product_description',
                'pr.prod_pullout as total_pull_out',
                'pr.prod_damaged as total_damage',
                DB::raw('COALESCE(pr.prod_quantity, pr.prod_stocks, 0) as total_stocks'),
                'pr.prod_price as unit_price',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('pr.prod_id', $prodId)
            ->first();
        
        if ($product) {
            $quantity = $product->total_stocks ?? 0;
            $product->stock_status = $quantity > 20 ? 'Good Stock' : 
                                    ($quantity > 0 ? 'Low Stock' : 'Out of Stock');
        }
        
        return $product;
    }

    private function getServiceDetails($serviceId)
    {
        return DB::table('tbl_serv as s')
            ->leftJoin('tbl_branch as b', 'b.branch_id', '=', 's.branch_id')
            ->select(
                's.serv_name as service_name',
                's.serv_type as service_type',
                's.serv_description as service_description',
                's.serv_price as service_price',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('s.serv_id', $serviceId)
            ->first();
    }

    private function getRevenueDetails($paymentId)
    {
        return DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('*')
            ->where('tbl_pay.pay_id', $paymentId)
            ->first();
    }

    private function getPrescriptionDetails($prescriptionId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_prescription')) {
            return null;
        }

        return DB::table('tbl_prescription as pr')
            ->join('tbl_pet as p', 'p.pet_id', '=', 'pr.pet_id')
            ->join('tbl_own as o', 'o.own_id', '=', 'p.own_id')
            ->join('tbl_user as u', 'u.user_id', '=', 'pr.user_id')
            ->leftJoin('tbl_branch as b', 'b.branch_id', '=', 'pr.branch_id')
            ->select(
                'pr.prescription_date',
                'pr.medication as raw_medication_data',
                'pr.differential_diagnosis',
                'pr.notes',
                'p.pet_name',
                'p.pet_species',
                'o.own_name',
                'u.user_name as prescribed_by',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('pr.prescription_id', $prescriptionId)
            ->first();
    }

    private function getReferralDetails($referralId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return null;
        }

        return DB::table('tbl_ref as r')
            ->leftJoin('tbl_visit_record as v', 'v.visit_id', '=', 'r.visit_id')
            ->leftJoin('tbl_pet as p', function($join) {
                $join->on('p.pet_id', '=', 'r.pet_id')
                     ->orOn('p.pet_id', '=', 'v.pet_id');
            })
            ->leftJoin('tbl_own as o', 'o.own_id', '=', 'p.own_id')
            ->leftJoin('tbl_user as u', 'u.user_id', '=', 'r.ref_by')
            ->leftJoin('tbl_branch as b1', 'b1.branch_id', '=', 'u.branch_id') // Referring Branch Info
            ->leftJoin('tbl_branch as b2', 'b2.branch_id', '=', 'r.ref_to') // Referred To Branch Info
            ->select(
                'r.ref_id',
                'r.ref_date',
                'r.ref_description',
                'r.ref_type',
                'r.ref_status',
                'p.pet_name',
                'p.pet_birthdate',
                'p.pet_gender',
                'p.pet_species',
                'p.pet_breed',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'o.own_name',
                'u.user_name as referring_vet_name',
                'u.user_licenseNum as referring_vet_license',
                DB::raw('REPLACE(u.user_contactNum, ",", "") as referring_vet_contact'),
                'b1.branch_name as referring_branch',
                'b2.branch_name as referred_to_name'
            )
            ->where('r.ref_id', $referralId)
            ->first();
    }

    private function getEquipmentDetails($equipmentId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_equipment')) {
            return null;
        }

        return DB::table('tbl_equipment')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_equipment.branch_id')
            ->select('*')
            ->where('tbl_equipment.equipment_id', $equipmentId)
            ->first();
    }

    private function getOwnerPetsDetails($ownId)
    {
        $owner = DB::table('tbl_own')
                    ->where('own_id', $ownId)
                    ->select(DB::raw('REPLACE(own_contactnum, ",", "") as own_contactnum'), 'own_id', 'own_name', 'own_location')
                    ->first();
        
        if (!$owner) {
            return null;
        }
        
        $petInfo = DB::table('tbl_pet')
            ->where('own_id', $ownId)
            ->select(
                DB::raw('GROUP_CONCAT(DISTINCT pet_name SEPARATOR ", ") as pet_names'),
                DB::raw('GROUP_CONCAT(DISTINCT pet_species SEPARATOR ", ") as pet_species'),
                DB::raw('GROUP_CONCAT(DISTINCT pet_breed SEPARATOR ", ") as pet_breeds'),
                DB::raw('GROUP_CONCAT(DISTINCT pet_age SEPARATOR ", ") as pet_ages'),
                DB::raw('COUNT(pet_id) as total_pets')
            )
            ->first();
        
        return (object) array_merge(
            (array) $owner,
            (array) $petInfo
        );
    }

    private function getAppointmentBillingDetails($appointId)
    {
        return DB::table('tbl_appoint as a')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_bill as b', 'a.appoint_id', '=', 'b.appoint_id')
            ->join('tbl_pay as pay', 'b.bill_id', '=', 'pay.bill_id')
            ->leftJoin('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id') // FIX: Join Branch via User
            ->select('*')
            ->where('a.appoint_id', $appointId)
            ->first();
    }

    private function getProductPurchaseDetails($orderId)
    {
        return DB::table('tbl_ord as o')
            ->join('tbl_prod as pr', 'o.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as ow', 'o.own_id', '=', 'ow.own_id')
            ->join('tbl_user as u', 'o.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'o.ord_id',
                'o.ord_date',
                'ow.own_name',
                DB::raw('REPLACE(ow.own_contactnum, ",", "") as own_contactnum'),
                'pr.prod_name',
                'pr.prod_category',
                'pr.prod_description',
                'o.ord_quantity',
                'pr.prod_price',
                'o.ord_total',
                'u.user_name as handled_by',
                'b.branch_name',
                'b.branch_address'
            )
            ->where('o.ord_id', $orderId)
            ->first();
    }

    private function getServiceAppointmentDetails($appointServId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return null;
        }

        return DB::table('tbl_appoint_serv as aps')
            ->join('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id') // FIX: Join Branch via User
            ->select('*')
            ->where('aps.appoint_serv_id', $appointServId)
            ->first();
    }

    private function getBranchUserDetails($userId)
    {
        return DB::table('tbl_user as u')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'u.user_name',
                'u.user_role',
                'u.user_email',
                'u.user_status',
                DB::raw('REPLACE(u.user_contactNum, ",", "") as user_contactNum'),
                'b.branch_name',
                'b.branch_address'
            )
            ->where('u.user_id', $userId)
            ->first();
    }

    private function getPaymentCollectionDetails($payId)
    {
        return DB::table('tbl_pay as pay')
            ->join('tbl_bill as b', 'pay.bill_id', '=', 'b.bill_id')
            ->join('tbl_visit_record as v', 'b.visit_id', '=', 'v.visit_id')
            ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
            ->select(
                'u.user_name as collected_by',
                'o.own_name',
                DB::raw('REPLACE(o.own_contactnum, ",", "") as own_contactnum'),
                'p.pet_name',
                'p.pet_species',
                'v.visit_date',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'b.bill_status',
                'br.branch_name',
                'br.branch_address'
            )
            ->where('pay.pay_id', $payId)
            ->first();
    }

    private function getMedicalHistoryDetails($historyId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_history')) {
            return null;
        }

        return DB::table('tbl_history as h')
            ->join('tbl_pet as p', 'h.pet_id', '=', 'p.pet_id')
            ->join('tbl_user as u', 'h.user_id', '=', 'u.user_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->select(
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'o.own_name',
                'h.visit_date',
                'h.diagnosis',
                'h.treatment',
                'h.medication',
                'h.follow_up_date',
                'h.notes',
                'u.user_name as veterinarian'
            )
            ->where('h.history_id', $historyId)
            ->first();
    }

    private function getBranchEquipmentDetails($recordId)
    {
        // Try to get from boarding record first (format: visit_id-pet_id)
        if (strpos($recordId, '-') !== false) {
            list($visitId, $petId) = explode('-', $recordId, 2);
            
            if (DB::getSchemaBuilder()->hasTable('tbl_boarding_record')) {
                $record = DB::table('tbl_boarding_record as br')
                    ->join('tbl_visit_record as v', 'br.visit_id', '=', 'v.visit_id')
                    ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
                    ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
                    ->leftJoin('tbl_equipment as eq', 'br.equipment_id', '=', 'eq.equipment_id')
                    ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
                    ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
                    ->leftJoin('tbl_serv as s', 'br.serv_id', '=', 's.serv_id')
                    ->where('br.visit_id', $visitId)
                    ->where('br.pet_id', $petId)
                    ->whereNotNull('br.equipment_id')
                    ->select(
                        DB::raw('CONCAT(br.visit_id, "-", br.pet_id) as id'),
                        'br.visit_id',
                        'br.pet_id',
                        'br.equipment_id',
                        'br.check_in_date',
                        'br.check_out_date',
                        'br.room_no',
                        'br.status as boarding_status',
                        'br.handled_by',
                        'eq.equipment_name',
                        'eq.equipment_category',
                        'pet.pet_name',
                        'pet.pet_species',
                        'pet.pet_breed',
                        'owner.own_name as owner_name',
                        's.serv_name as service_name',
                        's.serv_type as service_type',
                        'u.user_name as vet_name',
                        'v.visit_date',
                        'b.branch_name',
                        'b.branch_address'
                    )
            ->first();
                
                if ($record) {
                    return $record;
                }
            }
        }

        // Try equipment assignment log
        if (DB::getSchemaBuilder()->hasTable('tbl_equipment_assignment_log')) {
            $record = DB::table('tbl_equipment_assignment_log as eal')
                ->join('tbl_equipment as eq', 'eal.equipment_id', '=', 'eq.equipment_id')
                ->join('tbl_branch as b', 'eq.branch_id', '=', 'b.branch_id')
                ->leftJoin('tbl_visit_record as v', 'eal.visit_id', '=', 'v.visit_id')
                ->leftJoin('tbl_user as u', 'eal.performed_by', '=', 'u.user_id')
                ->leftJoin('tbl_pet as pet', 'eal.pet_id', '=', 'pet.pet_id')
                ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
            ->select(
                    'eal.id',
                    'eal.equipment_id',
                    'eal.action_type',
                    'eal.visit_id',
                    'eal.pet_id',
                    'eal.quantity_changed',
                    'eal.previous_status',
                    'eal.new_status',
                    'eal.reference',
                    'eal.notes as log_notes',
                    'eal.created_at',
                    'eq.equipment_name',
                    'eq.equipment_category',
                    'pet.pet_name',
                    'pet.pet_species',
                    'owner.own_name as owner_name',
                    'u.user_name as performed_by_name',
                    'b.branch_name',
                    'b.branch_address'
                )
                ->where('eal.id', $recordId)
                ->first();
            
            if ($record) {
                return $record;
            }
        }

        return null;
    }

    private function getDamagedProductDetails($recordId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_inventory_transactions')) {
            return null;
        }

        $hasPerformedByCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'performed_by');
        $hasUserIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'user_id');
        $hasServIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'serv_id');
        $hasVisitIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'visit_id');
        $hasAppointIdCol = DB::getSchemaBuilder()->hasColumn('tbl_inventory_transactions', 'appoint_id');

        $query = DB::table('tbl_inventory_transactions as it')
            ->join('tbl_prod as p', 'it.prod_id', '=', 'p.prod_id')
            ->leftJoin('tbl_branch as b', 'p.branch_id', '=', 'b.branch_id');

        // Prefer performed_by if it exists, otherwise fallback to user_id if present
        if ($hasPerformedByCol) {
            $query->leftJoin('tbl_user as u', 'it.performed_by', '=', 'u.user_id');
        } elseif ($hasUserIdCol) {
            $query->leftJoin('tbl_user as u', 'it.user_id', '=', 'u.user_id');
        }
        if ($hasServIdCol) {
            $query->leftJoin('tbl_serv as s', 'it.serv_id', '=', 's.serv_id');
        }
        if ($hasVisitIdCol) {
            $query->leftJoin('tbl_visit_record as v', 'it.visit_id', '=', 'v.visit_id');
        }

        $selects = [
            'it.transaction_id as id',
            'it.transaction_type',
            'it.quantity_change',
            'it.reference',
            'p.prod_name',
            'p.prod_category',
            'p.prod_type',
            'b.branch_name'
        ];

        if ($hasPerformedByCol || $hasUserIdCol) {
            $selects[] = 'u.user_name';
        } else {
            $selects[] = DB::raw('NULL as user_name');
        }
        // Include serv_name for display
        if ($hasServIdCol) {
            $selects[] = 'it.serv_id';
            $selects[] = 's.serv_name';
        } else {
            $selects[] = DB::raw('NULL as serv_id');
            $selects[] = DB::raw('NULL as serv_name');
        }
        if ($hasAppointIdCol) {
            $selects[] = 'it.appoint_id';
        } else {
            $selects[] = DB::raw('NULL as appoint_id');
        }

        // Get ALL transaction types (not just damage/pullout)
        $record = $query->select($selects)
            ->where('it.transaction_id', $recordId)
            ->first();
        
        if ($record) {
            // Format transaction type for display - store as 'type' for column name
            $typeLabels = [
                'restock' => 'Stock Added',
                'sale' => 'POS Sale',
                'service_usage' => 'Service Usage',
                'damage' => 'Damaged',
                'pullout' => 'Pull-out',
                'adjustment' => 'Adjustment',
                'return' => 'Return',
            ];
            
            // Create new object with columns in the desired order
            $orderedRecord = (object)[
                '_id' => $record->id,
                'row_number' => 1, // Single record in PDF view
                'prod_name' => $record->prod_name ?? 'N/A',
                'prod_category' => $record->prod_category ?? 'N/A',
                'prod_type' => $record->prod_type ?? 'N/A',
                'branch_name' => $record->branch_name ?? 'N/A',
                'user_name' => $record->user_name ?? 'System',
                'serv_name' => $record->serv_name ?? 'N/A',
                'reference' => $record->reference ?? 'N/A',
                'type' => $typeLabels[$record->transaction_type] ?? ucfirst($record->transaction_type ?? 'N/A'),
                'quantity' => $record->quantity_change ?? 0,
            ];
            
            return $orderedRecord;
        }
        
        return $record;
    }

    private function getBranchPaymentDetails($branchName)
    {
        return DB::table('tbl_pay as pay')
            ->join('tbl_bill as bl', 'pay.bill_id', '=', 'bl.bill_id')
            ->join('tbl_appoint as a', 'bl.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id') // FIX: Join Branch via User
            ->select('*')
            ->where('b.branch_name', $branchName)
            ->first();
    }

    private function getServiceUtilizationDetails($recordId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_visit_service')) {
            return null;
        }

        // Get visit service record by id - only selected columns
        $record = DB::table('tbl_visit_service as vs')
            ->join('tbl_visit_record as v', 'vs.visit_id', '=', 'v.visit_id')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->join('tbl_user as u', 'v.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_pet as pet', 'v.pet_id', '=', 'pet.pet_id')
            ->leftJoin('tbl_own as owner', 'pet.own_id', '=', 'owner.own_id')
            ->select(
                'vs.id',
                'v.visit_date',
                'owner.own_name as owner_name',
                'pet.pet_name',
                'pet.pet_species',
                'pet.pet_breed',
                'v.patient_type',
                's.serv_name',
                's.serv_type',
                'vs.total_price',
                'u.user_name as performed_by',
                'b.branch_name',
                'vs.status as service_status'
            )
            ->where('vs.id', $recordId)
            ->first();

        if (!$record) {
            return null;
        }

        return $record;
    }
}
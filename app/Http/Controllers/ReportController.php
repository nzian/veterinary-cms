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
        $record = $this->getRecordByType($reportType, $recordId);
        
        if (!$record) {
            abort(404, 'Record not found');
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
            'damaged_products' => 'Damaged/Pullout Products',
            'service_utilization' => 'Service Utilization per Branch',
        ];
        
        // Pass metadata for print layout header/footer
        $reportMetadata = [
            'report_name' => $titles[$reportType] ?? 'Report Details',
            'generated_by' => auth()->check() ? auth()->user()->user_name : 'System',
            'generated_at' => Carbon::now()->format('M d, Y h:i A'),
        ];

        $pdf = PDF::loadView('reports.pdf.universal', [
            'record' => $record,
            'reportType' => $reportType,
            'title' => $titles[$reportType] ?? 'Report Details',
            'reportMetadata' => $reportMetadata,
        ]);
        $pdf->setPaper('letter', 'landscape');
        return $pdf->stream(($titles[$reportType] ?? 'report') . '_' . $recordId . '.pdf');
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
            'billing_orders' => [
                'title' => 'Billing with Orders',
                'description' => 'Billing information with related Orders to the Pet Owner',
                'data' => $this->getBillingOrdersReport($startDate, $endDate)
            ],
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
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'v.visit_date',
                'v.patient_type',
                'v.visit_status as status',
                'v.workflow_status',
                'v.weight',
                'v.temperature',
                'b.branch_name',
                'u.user_name as veterinarian',
                DB::raw("GROUP_CONCAT(DISTINCT s.serv_name SEPARATOR ', ') as services")
            )
            ->groupBy(
                'v.visit_id', 'o.own_name', 'o.own_contactnum', 'p.pet_name', 
                'p.pet_species', 'p.pet_breed', 'v.visit_date', 'v.patient_type',
                'v.visit_status', 'v.workflow_status', 'v.weight', 'v.temperature',
                'b.branch_name', 'u.user_name'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);
        if ($status) $query->where('v.visit_status', $status);

        return $query->orderBy('v.visit_date', 'desc')->get();
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
                DB::raw('COUNT(p.pet_id) as total_pets')
            )
            ->groupBy('o.own_id', 'o.own_name', 'o.own_contactnum', 'o.own_location');

        // Filter by pet registration date
        if ($startDate) $query->whereDate('p.pet_registration', '>=', $startDate); 
        if ($endDate) $query->whereDate('p.pet_registration', '<=', $endDate);

        return $query->get();
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
                'p.pet_name',
                'v.visit_date',
                'v.patient_type',
                'b.bill_id',
                'b.bill_status',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('v.visit_date', 'desc')->get();
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
                'pr.prod_name',
                'pr.prod_category',
                'o2.ord_quantity',
                'o2.ord_total',
                'o2.ord_date',
                'u.user_name as handled_by',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('o2.ord_date', '>=', $startDate);
        if ($endDate) $query->whereDate('o2.ord_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('o2.ord_date', 'desc')->get();
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
                'p.pet_name',
                'v.visit_date',
                'r.ref_date',
                'r.ref_description',
                'b1.branch_name as referred_by',
                'b2.branch_name as referred_to',
                'u.user_name as created_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);
        if ($branch) $query->where('b1.branch_id', $branch);

        return $query->orderBy('r.ref_date', 'desc')->get();
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
                'p.pet_name',
                's.serv_name',
                's.serv_price',
                'vs.status as service_status',
                'v.visit_date',
                'u.user_name as veterinarian',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('v.visit_date', 'desc')->get();
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
                'b.branch_name',
                'b.branch_address',
                'u.user_status',
                'u.user_email',
                DB::raw('REPLACE(u.user_contactNum, ",", "") as user_contactNum')
            );

        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('b.branch_name')->orderBy('u.user_role')->get();
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
                'p.pet_name',
                'v.visit_date',
                'v.patient_type',
                'v.visit_status',
                'b.branch_name',
                DB::raw("GROUP_CONCAT(DISTINCT s.serv_name SEPARATOR ', ') as services")
            )
            ->groupBy(
                'v.visit_id', 'u.user_name', 'o.own_name', 'p.pet_name',
                'v.visit_date', 'v.patient_type', 'v.visit_status', 'b.branch_name'
            );

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('v.visit_date', 'desc')->get();
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
                'pr.prod_name',
                'ord.ord_quantity',
                'ord.ord_total',
                'ord.ord_date',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('ord.ord_date', '>=', $startDate);
        if ($endDate) $query->whereDate('ord.ord_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('ord.ord_date', 'desc')->get();
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
                'p.pet_name',
                'v.visit_date',
                'b.bill_id',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name'
            );

        if ($startDate) $query->whereDate('b.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('b.bill_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('v.visit_date', 'desc')->get();
    }

    private function getMedicalHistoryReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_history')) {
            return collect();
        }

        $query = DB::table('tbl_history as h')
            ->join('tbl_pet as p', 'h.pet_id', '=', 'p.pet_id')
            ->join('tbl_user as u', 'h.user_id', '=', 'u.user_id')
            ->select(
                'h.history_id',
                'p.pet_name',
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

        return $query->orderBy('h.visit_date', 'desc')->get();
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
            ->select(
                'pr.prescription_id',
                'pr.prescription_date',
                // Keep the raw data selection for the controller/database layer
                'pr.medication as raw_medication_data', 
                'pr.differential_diagnosis',
                'pr.notes',
                'u.user_name as prescribed_by',
                'b.branch_name',
                'p.pet_name'
            );

        if ($startDate) $query->whereDate('pr.prescription_date', '>=', $startDate);
        if ($endDate) $query->whereDate('pr.prescription_date', '<=', $endDate);
        if ($branch) $query->where('pr.branch_id', $branch);

        return $query->orderBy('pr.prescription_date', 'desc')->get();
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
                'o.own_name',
                DB::raw('GROUP_CONCAT(s.serv_name SEPARATOR ", ") as services'),
                DB::raw('SUM(s.serv_price) as total_service_price'),
                DB::raw('COUNT(vs.serv_id) as service_count')
            )
            ->groupBy('v.visit_id', 'v.visit_date', 'p.pet_name', 'o.own_name')
            ->having('service_count', '>', 1);

        if ($startDate) $query->whereDate('v.visit_date', '>=', $startDate);
        if ($endDate) $query->whereDate('v.visit_date', '<=', $endDate);

        return $query->orderBy('v.visit_date', 'desc')->get();
    }

    /**
     * FIX: Total Equipment and Total Quantity is a count, not a peso
     */
    private function getBranchEquipmentReport($branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_equipment')) {
            return collect();
        }

        $query = DB::table('tbl_equipment as e')
            ->join('tbl_branch as b', 'e.branch_id', '=', 'b.branch_id')
            ->select(
                'b.branch_name',
                'e.equipment_category',
                DB::raw('COUNT(e.equipment_id) as total_equipment_count'),
                DB::raw('SUM(e.equipment_quantity) as total_quantity_sum')
            )
            ->groupBy('b.branch_name', 'e.equipment_category');

        if ($branch) $query->where('e.branch_id', $branch);

        return $query->get();
    }

    private function getDamagedProductsReport($startDate, $endDate)
    {
        $query = DB::table('tbl_ord as ord')
            ->join('tbl_prod as pr', 'ord.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as o', 'ord.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'ord.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'ord.ord_id',
                'o.own_name',
                'u.user_name as handled_by',
                'pr.prod_name',
                'pr.prod_damaged',
                'pr.prod_pullout',
                'ord.ord_quantity',
                'ord.ord_date'
            )
            ->where(function($q) {
                $q->where('pr.prod_damaged', '>', 0)
                  ->orWhere('pr.prod_pullout', '>', 0);
            });

        if ($startDate) $query->whereDate('ord.ord_date', '>=', $startDate);
        if ($endDate) $query->whereDate('ord.ord_date', '<=', $endDate);

        return $query->orderBy('ord.ord_date', 'desc')->get();
    }

    private function getReferralMedicalReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return collect();
        }

        $query = DB::table('tbl_ref as r')
            ->join('tbl_appoint as a', 'r.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'r.ref_by', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b2', 'r.ref_to', '=', 'b2.branch_id')
            ->select(
                'a.appoint_id',
                'a.appoint_date',
                'p.pet_name',
                'o.own_name',
                'r.ref_date',
                'r.ref_description',
                'r.medical_history',
                'b2.branch_name as referred_to',
                'u.user_name as referred_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);

        return $query->orderBy('r.ref_date', 'desc')->get();
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
                'u.user_name as collected_by',
                DB::raw('COUNT(pay.pay_id) as total_payments_count'),
                DB::raw('SUM(pay.pay_total) as total_amount_collected')
            )
            ->groupBy('b.branch_name', 'u.user_name');

        if ($startDate) $query->whereDate('bl.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('bl.bill_date', '<=', $endDate);
        if ($branch) $query->where('b.branch_id', $branch);

        return $query->orderBy('total_amount_collected', 'desc')->get();
    }

    /**
     * FIX: total used is a count, not a peso.
     * NOTE: This report links service to branch directly, assuming tbl_serv.branch_id exists.
     */
    private function getServiceUtilizationReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return collect();
        }

        $query = DB::table('tbl_appoint_serv as aps')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            // Assuming tbl_serv has branch_id, keeping this as is based on context.
            ->join('tbl_branch as b', 's.branch_id', '=', 'b.branch_id') 
            ->leftJoin('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
            ->select(
                'b.branch_name',
                's.serv_name',
                DB::raw('COUNT(aps.appoint_serv_id) as total_used_count')
            )
            ->groupBy('b.branch_name', 's.serv_name');

        if ($startDate || $endDate) {
            if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
            if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        }
        
        if ($branch) $query->where('b.branch_id', $branch);

        return $query->orderBy('total_used_count', 'desc')->get();
    }

    // ==================== DETAIL VIEW METHODS (FIXED) ====================

    /**
     * FIX: Join Branch via User to resolve 'tbl_appoint.branch_id' error.
     */
    private function getVisitDetails($visitId)
    {
        return DB::table('tbl_visit_record')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_visit_record.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_visit_record.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->leftJoin('tbl_bill', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->select('tbl_visit_record.*', 'tbl_pet.*', 'tbl_own.*', 'tbl_user.user_name', 'tbl_branch.branch_name', 
                     'tbl_bill.bill_id', 'tbl_bill.bill_status', 'tbl_pay.pay_total')
            ->where('tbl_visit_record.visit_id', $visitId)
            ->first();
    }

    private function getVisitServiceDetails($visitServiceId)
    {
        return DB::table('tbl_visit_service')
            ->join('tbl_visit_record', 'tbl_visit_record.visit_id', '=', 'tbl_visit_service.visit_id')
            ->join('tbl_serv', 'tbl_serv.serv_id', '=', 'tbl_visit_service.serv_id')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_visit_record.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_visit_record.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('tbl_visit_service.*', 'tbl_visit_record.visit_date', 'tbl_serv.serv_name', 'tbl_serv.serv_price',
                     'tbl_pet.pet_name', 'tbl_own.own_name', 'tbl_user.user_name', 'tbl_branch.branch_name')
            ->where('tbl_visit_service.id', $visitServiceId)
            ->first();
    }

    private function getVisitBillingDetails($visitId)
    {
        return DB::table('tbl_visit_record')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_visit_record.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->join('tbl_bill', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
            ->join('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_visit_record.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('tbl_visit_record.*', 'tbl_pet.*', 'tbl_own.*', 'tbl_user.user_name', 'tbl_branch.branch_name',
                     'tbl_bill.*', 'tbl_pay.*')
            ->where('tbl_visit_record.visit_id', $visitId)
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
        return DB::table('tbl_bill')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('*')
            ->where('tbl_bill.bill_id', $billId)
            ->first();
    }

    private function getSalesDetails($orderId)
    {
        return DB::table('tbl_ord')
            ->join('tbl_prod', 'tbl_prod.prod_id', '=', 'tbl_ord.prod_id')
            ->join('tbl_user', 'tbl_user.user_id', '=', 'tbl_ord.user_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_ord.own_id')
            ->join('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('*')
            ->where('tbl_ord.ord_id', $orderId)
            ->first();
    }

    private function getMedicalDetails($medicalId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_history')) {
            return null;
        }

        return DB::table('tbl_history')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_history.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->join('tbl_user', 'tbl_user.user_id', '=', 'tbl_history.user_id')
            ->select('*')
            ->where('tbl_history.history_id', $medicalId)
            ->first();
    }

    private function getStaffDetails($userId)
    {
        return DB::table('tbl_user')
            ->join('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select('*')
            ->where('tbl_user.user_id', $userId)
            ->first();
    }

    private function getInventoryDetails($prodId)
    {
        return DB::table('tbl_prod')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_prod.branch_id')
            ->select('*')
            ->where('tbl_prod.prod_id', $prodId)
            ->first();
    }

    private function getServiceDetails($serviceId)
    {
        return DB::table('tbl_serv')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_serv.branch_id')
            ->select('*')
            ->where('tbl_serv.serv_id', $serviceId)
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

        return DB::table('tbl_prescription')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_prescription.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->join('tbl_user', 'tbl_user.user_id', '=', 'tbl_prescription.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_prescription.branch_id')
            ->select('*')
            ->where('tbl_prescription.prescription_id', $prescriptionId)
            ->first();
    }

    private function getReferralDetails($referralId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return null;
        }

        return DB::table('tbl_ref')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_ref.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_ref.ref_by')
            ->leftJoin('tbl_branch as b1', 'b1.branch_id', '=', 'tbl_user.branch_id') // Referring Branch Info
            ->leftJoin('tbl_branch as b2', 'b2.branch_id', '=', 'tbl_ref.ref_to') // Referred To Branch Info
            ->select(
                'tbl_ref.*',
                'tbl_pet.pet_name',
                'tbl_pet.pet_birthdate',
                'tbl_pet.pet_gender',
                'tbl_pet.pet_species',
                'tbl_pet.pet_breed',
                DB::raw('REPLACE(tbl_own.own_contactnum, ",", "") as own_contactnum'),
                'tbl_own.own_name',
                'tbl_user.user_name as referring_vet_name',
                'tbl_user.user_licenseNum as referring_vet_license',
                DB::raw('REPLACE(tbl_user.user_contactNum, ",", "") as referring_vet_contact'),
                'b1.branch_name as referring_branch',
                'b2.branch_name as referred_to_name'
            )
            ->where('tbl_ref.ref_id', $referralId)
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
            ->select('*')
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
            ->select('*')
            ->where('u.user_id', $userId)
            ->first();
    }

    private function getPaymentCollectionDetails($payId)
    {
        return DB::table('tbl_pay as pay')
            ->join('tbl_bill as b', 'pay.bill_id', '=', 'b.bill_id')
            ->join('tbl_appoint as a', 'b.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id') // FIX: Join Branch via User
            ->select('*')
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
            ->select('*')
            ->where('h.history_id', $historyId)
            ->first();
    }

    private function getBranchEquipmentDetails($equipmentId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_equipment')) {
            return null;
        }

        return DB::table('tbl_equipment as e')
            ->join('tbl_branch as b', 'e.branch_id', '=', 'b.branch_id')
            ->select('*')
            ->where('e.equipment_id', $equipmentId)
            ->first();
    }

    private function getDamagedProductDetails($orderId)
    {
        return DB::table('tbl_ord as ord')
            ->join('tbl_prod as pr', 'ord.prod_id', '=', 'pr.prod_id')
            ->join('tbl_own as o', 'ord.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'ord.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select('*')
            ->where('ord.ord_id', $orderId)
            ->first();
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

    private function getServiceUtilizationDetails($servId)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return null;
        }

        return DB::table('tbl_appoint_serv as aps')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            ->join('tbl_branch as b', 's.branch_id', '=', 'b.branch_id')
            ->select('*')
            ->where('s.serv_id', $servId)
            ->first();
    }
}
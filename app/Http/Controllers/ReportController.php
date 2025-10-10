<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\View;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->get('report', 'appointments');
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

        // Define titles for each report type
        $titles = [
            'appointments' => 'Appointment Details',
            'owner_pets' => 'Pet Owner and Their Pets',
            'appointment_billing' => 'Appointment with Billing & Payment',
            'product_purchases' => 'Product Purchase Report',
            'referrals' => 'Inter-Branch Referral Report',
            'service_appointments' => 'Services in Appointments',
            'branch_appointments' => 'Branch Appointment Schedule',
            'multi_service_appointments' => 'Multiple Services Appointments',
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

        $pdf = PDF::loadView('reports.pdf.universal', [
            'record' => $record,
            'reportType' => $reportType,
            'title' => $titles[$reportType] ?? 'Report Details'
        ]);

        return $pdf->stream($titles[$reportType] ?? 'report' . '_' . $recordId . '.pdf');
    }

    /**
     * Get record based on report type for PDF generation
     */
    private function getRecordByType($reportType, $recordId)
    {
        switch($reportType) {
            case 'appointments':
            case 'branch_appointments':
            case 'multi_service_appointments':
                return $this->getAppointmentDetails($recordId);

            case 'owner_pets':
                return $this->getOwnerPetsDetails($recordId);

            case 'appointment_billing':
                return $this->getAppointmentBillingDetails($recordId);

            case 'product_purchases':
                return $this->getProductPurchaseDetails($recordId);

            case 'referrals':
            case 'referral_medical':
                return $this->getReferralDetails($recordId);

            case 'service_appointments':
                return $this->getServiceAppointmentDetails($recordId);

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
        $reportType = $request->get('report', 'appointments');
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
                    return ucfirst(str_replace('_', ' ', $header));
                }, $headers);
                fputcsv($file, $cleanHeaders);
                
                foreach ($reportData as $row) {
                    fputcsv($file, array_values((array) $row));
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
            'appointments' => [
                'title' => 'Appointment Management Report',
                'description' => 'Pet Owner owns the Pet who has an Appointment handled by a User',
                'data' => $this->getAppointmentsReport($startDate, $endDate, $branch, $status)
            ],
            'owner_pets' => [
                'title' => 'Pet Owner and Their Pets',
                'description' => 'Complete list of pet owners with all their registered pets',
                'data' => $this->getOwnerPetsReport($startDate, $endDate)
            ],
            'appointment_billing' => [
                'title' => 'Appointment with Billing & Payment',
                'description' => 'Pet Owner has a Pet with an Appointment that includes Billing and Payment',
                'data' => $this->getAppointmentBillingReport($startDate, $endDate, $branch)
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
            'service_appointments' => [
                'title' => 'Services in Appointments',
                'description' => 'Services provided during an Appointment for a Pet owned by a Pet Owner and managed by an assigned User',
                'data' => $this->getServiceAppointmentsReport($startDate, $endDate, $branch)
            ],
            'branch_users' => [
                'title' => 'Users Assigned per Branch',
                'description' => 'Users assigned per Branches with role and status information',
                'data' => $this->getBranchUsersReport($branch)
            ],
            'branch_appointments' => [
                'title' => 'Branch Appointment Schedule',
                'description' => 'User schedules an Appointment for a Pet owned by a Pet Owner at a Branch',
                'data' => $this->getBranchAppointmentsReport($startDate, $endDate, $branch)
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
            'multi_service_appointments' => [
                'title' => 'Multiple Services Appointments',
                'description' => 'Appointments with Multiple Services and Total Service Price',
                'data' => $this->getMultiServiceAppointmentsReport($startDate, $endDate)
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

    // ==================== REPORT QUERY METHODS ====================

    private function getAppointmentsReport($startDate, $endDate, $branch, $status)
    {
        $query = DB::table('tbl_own as o')
            ->join('tbl_pet as p', 'o.own_id', '=', 'p.own_id')
            ->join('tbl_appoint as a', 'p.pet_id', '=', 'a.pet_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'a.appoint_id',
                'o.own_name as owner_name',
                'o.own_contactnum as owner_contact',
                'p.pet_name',
                'p.pet_species',
                'p.pet_breed',
                'a.appoint_date',
                'a.appoint_time',
                'a.appoint_status as status',
                'a.appoint_type',
                'u.user_name as handled_by',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
        if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);
        if ($status) $query->where('a.appoint_status', $status);

        return $query->orderBy('a.appoint_date', 'desc')->get();
    }

    private function getOwnerPetsReport($startDate, $endDate)
    {
        $query = DB::table('tbl_own as o')
            ->join('tbl_pet as p', 'o.own_id', '=', 'p.own_id')
            ->select(
                'o.own_id',
                'o.own_name as owner_name',
                'o.own_contactnum',
                'o.own_location',
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_name SEPARATOR ", ") as pet_names'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_species SEPARATOR ", ") as pet_species'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_breed SEPARATOR ", ") as pet_breeds'),
                DB::raw('GROUP_CONCAT(DISTINCT p.pet_age SEPARATOR ", ") as pet_ages'),
                DB::raw('COUNT(p.pet_id) as total_pets')
            )
            ->groupBy('o.own_id', 'o.own_name', 'o.own_contactnum', 'o.own_location');

        if ($startDate) $query->whereDate('p.pet_registration', '>=', $startDate);
        if ($endDate) $query->whereDate('p.pet_registration', '<=', $endDate);

        return $query->get();
    }

    private function getAppointmentBillingReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_own as o')
            ->join('tbl_pet as p', 'o.own_id', '=', 'p.own_id')
            ->join('tbl_appoint as a', 'p.pet_id', '=', 'a.pet_id')
            ->join('tbl_bill as b', 'a.appoint_id', '=', 'b.appoint_id')
            ->join('tbl_pay as pay', 'b.bill_id', '=', 'pay.bill_id')
            ->leftJoin('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
            ->select(
                'a.appoint_id',
                'o.own_name',
                'p.pet_name',
                'a.appoint_date',
                'a.appoint_type',
                'b.bill_id',
                'b.bill_status',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name'
            );

        if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
        if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('a.appoint_date', 'desc')->get();
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

    private function getReferralsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return collect();
        }

        $query = DB::table('tbl_ref as r')
            ->join('tbl_appoint as a', 'r.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'r.ref_by', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b1', 'u.branch_id', '=', 'b1.branch_id')
            ->select(
                'r.ref_id',
                'o.own_name',
                'p.pet_name',
                'a.appoint_date',
                'r.ref_date',
                'r.ref_description',
                'b1.branch_name as referred_by',
                'r.ref_to as referred_to',
                'u.user_name as created_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);

        return $query->orderBy('r.ref_date', 'desc')->get();
    }

    private function getServiceAppointmentsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return collect();
        }

        $query = DB::table('tbl_appoint_serv as aps')
            ->join('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'aps.appoint_serv_id',
                'o.own_name',
                'p.pet_name',
                's.serv_name',
                's.serv_price',
                'a.appoint_date',
                'u.user_name as handled_by',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
        if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('a.appoint_date', 'desc')->get();
    }

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
                'u.user_contactNum'
            );

        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('b.branch_name')->orderBy('u.user_role')->get();
    }

    private function getBranchAppointmentsReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_appoint as a')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'a.appoint_id',
                'u.user_name as scheduler',
                'o.own_name',
                'p.pet_name',
                'a.appoint_date',
                'a.appoint_time',
                'a.appoint_type',
                'b.branch_name'
            );

        if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
        if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('a.appoint_date', 'desc')->get();
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
            ->join('tbl_appoint as a', 'b.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
            ->select(
                'pay.pay_id',
                'u.user_name as collected_by',
                'o.own_name',
                'p.pet_name',
                'a.appoint_date',
                'b.bill_id',
                'pay.pay_total',
                'pay.pay_cashAmount',
                'pay.pay_change',
                'br.branch_name'
            );

        if ($startDate) $query->whereDate('b.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('b.bill_date', '<=', $endDate);
        if ($branch) $query->where('u.branch_id', $branch);

        return $query->orderBy('a.appoint_date', 'desc')->get();
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

    private function getPrescriptionsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_prescription')) {
            return collect();
        }

        $query = DB::table('tbl_prescription as pr')
            ->join('tbl_user as u', 'pr.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'pr.branch_id', '=', 'b.branch_id')
            ->join('tbl_pet as p', 'pr.pet_id', '=', 'p.pet_id')
            ->select(
                'pr.prescription_id',
                'pr.prescription_date',
                'pr.medication',
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

    private function getMultiServiceAppointmentsReport($startDate, $endDate)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return collect();
        }

        $query = DB::table('tbl_appoint_serv as aps')
            ->join('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
            ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
            ->select(
                'a.appoint_id',
                'a.appoint_date',
                'p.pet_name',
                'o.own_name',
                DB::raw('GROUP_CONCAT(s.serv_name SEPARATOR ", ") as services'),
                DB::raw('SUM(s.serv_price) as total_service_price')
            )
            ->groupBy('a.appoint_id', 'a.appoint_date', 'p.pet_name', 'o.own_name');

        if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
        if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);

        return $query->orderBy('a.appoint_date', 'desc')->get();
    }

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
                DB::raw('COUNT(e.equipment_id) as total_equipment'),
                DB::raw('SUM(e.equipment_quantity) as total_quantity')
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
            ->select(
                'a.appoint_id',
                'a.appoint_date',
                'p.pet_name',
                'o.own_name',
                'r.ref_date',
                'r.ref_description',
                'r.medical_history',
                'u.user_name as referred_by'
            );

        if ($startDate) $query->whereDate('r.ref_date', '>=', $startDate);
        if ($endDate) $query->whereDate('r.ref_date', '<=', $endDate);

        return $query->orderBy('r.ref_date', 'desc')->get();
    }

    private function getBranchPaymentsReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay as pay')
            ->join('tbl_bill as bl', 'pay.bill_id', '=', 'bl.bill_id')
            ->join('tbl_appoint as a', 'bl.appoint_id', '=', 'a.appoint_id')
            ->join('tbl_user as u', 'a.user_id', '=', 'u.user_id')
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
            ->select(
                'b.branch_name',
                'u.user_name as collected_by',
                DB::raw('COUNT(pay.pay_id) as total_payments'),
                DB::raw('SUM(pay.pay_total) as total_amount_collected')
            )
            ->groupBy('b.branch_name', 'u.user_name');

        if ($startDate) $query->whereDate('bl.bill_date', '>=', $startDate);
        if ($endDate) $query->whereDate('bl.bill_date', '<=', $endDate);
        if ($branch) $query->where('b.branch_id', $branch);

        return $query->orderBy('total_amount_collected', 'desc')->get();
    }

    private function getServiceUtilizationReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_appoint_serv')) {
            return collect();
        }

        $query = DB::table('tbl_appoint_serv as aps')
            ->join('tbl_serv as s', 'aps.serv_id', '=', 's.serv_id')
            ->join('tbl_branch as b', 's.branch_id', '=', 'b.branch_id')
            ->leftJoin('tbl_appoint as a', 'aps.appoint_id', '=', 'a.appoint_id')
            ->select(
                'b.branch_name',
                's.serv_name',
                DB::raw('COUNT(aps.appoint_serv_id) as total_used')
            )
            ->groupBy('b.branch_name', 's.serv_name');

        if ($startDate || $endDate) {
            if ($startDate) $query->whereDate('a.appoint_date', '>=', $startDate);
            if ($endDate) $query->whereDate('a.appoint_date', '<=', $endDate);
        }
        
        if ($branch) $query->where('b.branch_id', $branch);

        return $query->orderBy('total_used', 'desc')->get();
    }

    // ==================== DETAIL VIEW METHODS ====================

    private function getAppointmentDetails($appointmentId)
    {
        return DB::table('tbl_appoint')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->leftJoin('tbl_bill', 'tbl_bill.appoint_id', '=', 'tbl_appoint.appoint_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->select('*')
            ->where('tbl_appoint.appoint_id', $appointmentId)
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
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select(
                'tbl_ref.*',
                'tbl_pet.pet_name',
                'tbl_pet.pet_birthdate',
                'tbl_pet.pet_gender',
                'tbl_pet.pet_species',
                'tbl_pet.pet_breed',
                'tbl_own.own_name',
                'tbl_own.own_contactnum',
                'tbl_user.user_name as referring_vet_name',
                'tbl_user.user_licenseNum as referring_vet_license',
                'tbl_user.user_contactNum as referring_vet_contact',
                'tbl_branch.branch_name as referring_branch'
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
    // First, get the owner details
    $owner = DB::table('tbl_own')->where('own_id', $ownId)->first();
    
    if (!$owner) {
        return null;
    }
    
    // Then get aggregated pet information
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
    
    // Merge the results
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
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
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
            ->leftJoin('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
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
            ->leftJoin('tbl_branch as br', 'u.branch_id', '=', 'br.branch_id')
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
            ->join('tbl_branch as b', 'u.branch_id', '=', 'b.branch_id')
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
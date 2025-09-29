<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $record = null;
        
        switch ($reportType) {
            case 'appointments':
                $record = $this->getAppointmentDetails($recordId);
                break;
            case 'pets':
                $record = $this->getPetDetails($recordId);
                break;
            case 'billing':
                $record = $this->getBillingDetails($recordId);
                break;
            case 'sales':
                $record = $this->getSalesDetails($recordId);
                break;
            case 'medical':
                $record = $this->getMedicalDetails($recordId);
                break;
            case 'staff':
                $record = $this->getStaffDetails($recordId);
                break;
            case 'inventory':
                $record = $this->getInventoryDetails($recordId);
                break;
            case 'services':
                $record = $this->getServiceDetails($recordId);
                break;
            case 'revenue':
                $record = $this->getRevenueDetails($recordId);
                break;
            case 'prescriptions':
                $record = $this->getPrescriptionDetails($recordId);
                break;
            case 'referrals':
                $record = $this->getReferralDetails($recordId);
                break;
            case 'equipment':
                $record = $this->getEquipmentDetails($recordId);
                break;
            default:
                $record = null;
        }

        return response()->json($record);
    }

    private function generateReports($startDate = null, $endDate = null, $branch = null, $status = null)
    {
        return [
            'appointments' => [
                'title' => 'Appointment Management Report',
                'description' => 'Comprehensive view of all appointments with pet owner, pet details, and veterinarian information',
                'data' => $this->getAppointmentsReport($startDate, $endDate, $branch, $status)
            ],
            'pets' => [
                'title' => 'Pet Registration Report',
                'description' => 'Complete pet registry with owner contact information and pet demographics',
                'data' => $this->getPetsReport($startDate, $endDate, $branch)
            ],
            'billing' => [
                'title' => 'Financial Billing Report',
                'description' => 'Detailed billing information including appointments, services, and payment status',
                'data' => $this->getBillingReport($startDate, $endDate, $branch, $status)
            ],
            'sales' => [
                'title' => 'Product Sales Report',
                'description' => 'Product purchase transactions with customer and staff information',
                'data' => $this->getSalesReport($startDate, $endDate, $branch)
            ],
            'medical' => [
                'title' => 'Medical History Report',
                'description' => 'Pet medical records with diagnosis, treatment, and veterinarian details',
                'data' => $this->getMedicalReport($startDate, $endDate, $branch)
            ],
            'services' => [
                'title' => 'Service Availability Report',
                'description' => 'Available services across branches with pricing information',
                'data' => $this->getServicesReport($startDate, $endDate, $branch)
            ],
            'staff' => [
                'title' => 'Staff Assignment Report',
                'description' => 'Staff distribution across branches with roles and employment status',
                'data' => $this->getStaffReport($branch)
            ],
            'inventory' => [
                'title' => 'Inventory Status Report',
                'description' => 'Product stock levels, expiry dates, and inventory status by branch',
                'data' => $this->getInventoryReport($startDate, $endDate, $branch)
            ],
            'revenue' => [
                'title' => 'Revenue Analysis Report',
                'description' => 'Financial performance analysis with payment collection details',
                'data' => $this->getRevenueReport($startDate, $endDate, $branch)
            ],
            'branch_performance' => [
                'title' => 'Branch Performance Report',
                'description' => 'Branch comparison with staff count, appointments, and revenue metrics',
                'data' => $this->getBranchPerformanceReport($startDate, $endDate, $branch)
            ],
            'prescriptions' => [
                'title' => 'Prescription Report',
                'description' => 'Medication prescriptions issued with pet and veterinarian details',
                'data' => $this->getPrescriptionsReport($startDate, $endDate, $branch)
            ],
            'referrals' => [
                'title' => 'Inter-Branch Referrals Report',
                'description' => 'Patient referrals between branches with medical history and recommendations',
                'data' => $this->getReferralsReport($startDate, $endDate, $branch)
            ],
            'equipment' => [
                'title' => 'Equipment Inventory Report',
                'description' => 'Medical and clinic equipment tracking across all branches',
                'data' => $this->getEquipmentReport($startDate, $endDate, $branch)
            ]
        ];
    }

    private function getAppointmentsReport($startDate, $endDate, $branch, $status)
    {
        $query = DB::table('tbl_appoint')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select(
                'tbl_appoint.appoint_id',
                'tbl_own.own_name as owner_name',
                'tbl_own.own_contactnum as owner_contact',
                'tbl_pet.pet_name',
                'tbl_pet.pet_breed',
                'tbl_pet.pet_species',
                DB::raw('COALESCE(tbl_branch.branch_name, "Not Assigned") as branch_name'),
                'tbl_appoint.appoint_type as appointment_type',
                'tbl_appoint.appoint_date as appointment_date',
                'tbl_appoint.appoint_time as appointment_time',
                DB::raw('COALESCE(tbl_user.user_name, "Not Assigned") as veterinarian'),
                DB::raw('COALESCE(tbl_user.user_role, "N/A") as staff_role'),
                'tbl_appoint.appoint_status as status',
                'tbl_appoint.appoint_description as description',
                'tbl_appoint.ref_id'
            );

        if ($startDate) {
            $query->whereDate('tbl_appoint.appoint_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_appoint.appoint_date', '<=', $endDate);
        }
        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }
        if ($status) {
            $query->where('tbl_appoint.appoint_status', $status);
        }

        return $query->orderBy('tbl_appoint.appoint_date', 'desc')
                    ->orderBy('tbl_appoint.appoint_time', 'desc')
                    ->get();
    }

    private function getPetsReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pet')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->select(
                'tbl_pet.pet_id',
                'tbl_own.own_name as owner_name',
                'tbl_own.own_contactnum as owner_contact',
                'tbl_own.own_location as owner_location',
                'tbl_pet.pet_name',
                'tbl_pet.pet_species',
                'tbl_pet.pet_breed',
                'tbl_pet.pet_age',
                'tbl_pet.pet_gender',
                'tbl_pet.pet_registration as registration_date',
                'tbl_pet.pet_weight',
                'tbl_pet.pet_birthdate',
                'tbl_pet.pet_temperature'
            );

        if ($startDate) {
            $query->whereDate('tbl_pet.pet_registration', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_pet.pet_registration', '<=', $endDate);
        }

        return $query->orderBy('tbl_pet.pet_registration', 'desc')->get();
    }

    private function getBillingReport($startDate, $endDate, $branch, $status)
    {
        $query = DB::table('tbl_bill')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->leftJoin('tbl_pay', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
            ->select(
                'tbl_bill.bill_id',
                DB::raw('COALESCE(tbl_own.own_name, "Walk-in Customer") as customer_name'),
                DB::raw('COALESCE(tbl_pet.pet_name, "N/A") as pet_name'),
                DB::raw('COALESCE(tbl_appoint.appoint_type, "Direct Billing") as service_type'),
                DB::raw('COALESCE(tbl_appoint.appoint_date, tbl_bill.bill_date) as service_date'),
                'tbl_bill.bill_date as billing_date',
                'tbl_pay.pay_total as bill_amount',
                'tbl_pay.pay_cashAmount as cash_received',
                'tbl_pay.pay_change as change_given',
                DB::raw('COALESCE(tbl_branch.branch_name, "Main Office") as branch_name'),
                DB::raw('COALESCE(tbl_user.user_name, "System") as handled_by'),
                'tbl_bill.bill_status as payment_status'
            );

        if ($startDate) {
            $query->whereDate('tbl_bill.bill_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_bill.bill_date', '<=', $endDate);
        }
        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }
        if ($status) {
            $query->where('tbl_bill.bill_status', $status);
        }

        return $query->orderBy('tbl_bill.bill_date', 'desc')->get();
    }

    private function getSalesReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_ord')
            ->join('tbl_prod', 'tbl_prod.prod_id', '=', 'tbl_ord.prod_id')
            ->join('tbl_user', 'tbl_user.user_id', '=', 'tbl_ord.user_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_ord.own_id')
            ->join('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select(
                'tbl_ord.ord_id',
                'tbl_ord.ord_date as sale_date',
                'tbl_own.own_name as customer_name',
                'tbl_prod.prod_name as product_name',
                'tbl_prod.prod_category as category',
                'tbl_ord.ord_quantity as quantity_sold',
                'tbl_prod.prod_price as unit_price',
                'tbl_ord.ord_total as total_amount',
                'tbl_user.user_name as cashier',
                'tbl_branch.branch_name'
            );

        if ($startDate) {
            $query->whereDate('tbl_ord.ord_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_ord.ord_date', '<=', $endDate);
        }
        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }

        return $query->orderBy('tbl_ord.ord_date', 'desc')->get();
    }

    private function getMedicalReport($startDate, $endDate, $branch)
    {
        // Check if medical history table exists
        if (!DB::getSchemaBuilder()->hasTable('tbl_medical_history')) {
            return collect();
        }

        $query = DB::table('tbl_medical_history')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_medical_history.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->select(
                'tbl_medical_history.id',
                'tbl_pet.pet_name',
                'tbl_own.own_name as owner_name',
                'tbl_medical_history.visit_date',
                'tbl_medical_history.diagnosis',
                'tbl_medical_history.treatment',
                'tbl_medical_history.medication',
                'tbl_medical_history.veterinarian_name',
                'tbl_medical_history.follow_up_date',
                'tbl_medical_history.notes'
            );

        if ($startDate) {
            $query->whereDate('tbl_medical_history.visit_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_medical_history.visit_date', '<=', $endDate);
        }

        return $query->orderBy('tbl_medical_history.visit_date', 'desc')->get();
    }

    private function getServicesReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_serv')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_serv.branch_id')
            ->select(
                'tbl_serv.serv_id',
                'tbl_serv.serv_name as service_name',
                'tbl_serv.serv_type as service_category',
                'tbl_serv.serv_description as description',
                'tbl_serv.serv_price as service_price',
                'tbl_branch.branch_name',
                DB::raw('"Available" as service_status')
            );

        if ($branch) {
            $query->where('tbl_serv.branch_id', $branch);
        }

        return $query->orderBy('tbl_serv.serv_name')->get();
    }

    private function getStaffReport($branch)
    {
        $query = DB::table('tbl_user')
            ->join('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select(
                'tbl_user.user_id',
                'tbl_branch.branch_name',
                'tbl_branch.branch_address',
                'tbl_user.user_name as staff_name',
                'tbl_user.user_role as position',
                'tbl_user.user_status as employment_status',
                'tbl_user.user_email as email',
                'tbl_user.user_contactNum as contact_number',
                'tbl_user.user_licenseNum as license_number',
                'tbl_user.registered_by',
                'tbl_user.last_login_at'
            );

        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }

        return $query->orderBy('tbl_branch.branch_name')->orderBy('tbl_user.user_role')->get();
    }

    private function getInventoryReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_prod')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_prod.branch_id')
            ->select(
                'tbl_prod.prod_id',
                'tbl_prod.prod_name as product_name',
                'tbl_prod.prod_category as category',
                'tbl_prod.prod_description as description',
                'tbl_prod.prod_price as unit_price',
                'tbl_prod.prod_stocks as current_stock',
                'tbl_prod.prod_reorderlevel as reorder_level',
                'tbl_prod.prod_expiry as expiry_date',
                'tbl_prod.prod_damaged as damaged_quantity',
                'tbl_prod.prod_pullout as pullout_quantity',
                'tbl_branch.branch_name',
                DB::raw('CASE 
                    WHEN tbl_prod.prod_expiry <= CURDATE() THEN "Expired"
                    WHEN tbl_prod.prod_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN "Expiring Soon"
                    WHEN tbl_prod.prod_stocks <= COALESCE(tbl_prod.prod_reorderlevel, 10) THEN "Low Stock"
                    ELSE "Good"
                END as stock_status')
            );

        if ($branch) {
            $query->where('tbl_prod.branch_id', $branch);
        }

        return $query->orderBy('tbl_prod.prod_category')->orderBy('tbl_prod.prod_name')->get();
    }

    private function getRevenueReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
            ->select(
                'tbl_pay.pay_id',
                DB::raw('COALESCE(tbl_appoint.appoint_date, tbl_bill.bill_date) as transaction_date'),
                'tbl_bill.bill_date as billing_date',
                DB::raw('COALESCE(tbl_own.own_name, "Walk-in Customer") as customer_name'),
                'tbl_pay.pay_total as revenue_amount',
                'tbl_pay.pay_cashAmount as cash_received',
                'tbl_pay.pay_change as change_given',
                DB::raw('COALESCE(tbl_user.user_name, "System") as collected_by'),
                DB::raw('COALESCE(tbl_branch.branch_name, "Main Office") as branch_name')
            );

        if ($startDate) {
            $query->whereDate('tbl_bill.bill_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_bill.bill_date', '<=', $endDate);
        }
        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }

        return $query->orderBy('tbl_bill.bill_date', 'desc')->get();
    }

    private function getBranchPerformanceReport($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_branch')
            ->select(
                'tbl_branch.branch_id',
                'tbl_branch.branch_name',
                'tbl_branch.branch_address',
                'tbl_branch.branch_contactNum as branch_contact',
                DB::raw('(SELECT COUNT(*) FROM tbl_user WHERE tbl_user.branch_id = tbl_branch.branch_id) as total_staff'),
                DB::raw('(SELECT COUNT(*) FROM tbl_appoint 
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id 
                          WHERE tbl_user.branch_id = tbl_branch.branch_id) as total_appointments'),
                DB::raw('(SELECT COUNT(*) FROM tbl_appoint 
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id 
                          WHERE tbl_user.branch_id = tbl_branch.branch_id 
                          AND tbl_appoint.appoint_status = "completed") as completed_appointments'),
                DB::raw('(SELECT COALESCE(SUM(tbl_pay.pay_total), 0) FROM tbl_pay 
                          JOIN tbl_bill ON tbl_bill.bill_id = tbl_pay.bill_id
                          LEFT JOIN tbl_appoint ON tbl_appoint.appoint_id = tbl_bill.appoint_id
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id
                          WHERE tbl_user.branch_id = tbl_branch.branch_id) as total_revenue'),
                DB::raw('(SELECT COALESCE(AVG(tbl_pay.pay_total), 0) FROM tbl_pay 
                          JOIN tbl_bill ON tbl_bill.bill_id = tbl_pay.bill_id
                          LEFT JOIN tbl_appoint ON tbl_appoint.appoint_id = tbl_bill.appoint_id
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id
                          WHERE tbl_user.branch_id = tbl_branch.branch_id) as average_transaction')
            );

        if ($branch) {
            $query->where('tbl_branch.branch_id', $branch);
        }

        return $query->orderBy('tbl_branch.branch_name')->get();
    }

    private function getPrescriptionsReport($startDate, $endDate, $branch)
    {
        // Check if prescription table exists
        if (!DB::getSchemaBuilder()->hasTable('tbl_prescription')) {
            return collect();
        }

        $query = DB::table('tbl_prescription')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_prescription.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_prescription.branch_id')
            ->select(
                'tbl_prescription.prescription_id',
                'tbl_pet.pet_name',
                'tbl_own.own_name as owner_name',
                'tbl_prescription.prescription_date',
                'tbl_prescription.medication',
                'tbl_prescription.notes',
                'tbl_branch.branch_name'
            );

        if ($startDate) {
            $query->whereDate('tbl_prescription.prescription_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_prescription.prescription_date', '<=', $endDate);
        }
        if ($branch) {
            $query->where('tbl_prescription.branch_id', $branch);
        }

        return $query->orderBy('tbl_prescription.prescription_date', 'desc')->get();
    }

    private function getReferralsReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_ref')) {
            return collect();
        }

        $query = DB::table('tbl_ref')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_ref.appoint_id')
            ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
            ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->select(
                'tbl_ref.ref_id',
                'tbl_ref.ref_date',
                DB::raw('COALESCE(tbl_own.own_name, "Unknown") as owner_name'),
                DB::raw('COALESCE(tbl_pet.pet_name, "Unknown") as pet_name'),
                'tbl_ref.ref_description as referral_reason',
                'tbl_ref.medical_history',
                'tbl_ref.tests_conducted',
                'tbl_ref.medications_given',
                'tbl_ref.ref_by as referred_by',
                'tbl_ref.ref_to as referred_to'
            );

        if ($startDate) {
            $query->whereDate('tbl_ref.ref_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tbl_ref.ref_date', '<=', $endDate);
        }

        return $query->orderBy('tbl_ref.ref_date', 'desc')->get();
    }

    private function getEquipmentReport($startDate, $endDate, $branch)
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_equipment')) {
            return collect();
        }

        $query = DB::table('tbl_equipment')
            ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_equipment.branch_id')
            ->select(
                'tbl_equipment.equipment_id',
                'tbl_equipment.equipment_name',
                'tbl_equipment.equipment_quantity',
                'tbl_equipment.equipment_description',
                DB::raw('COALESCE(tbl_branch.branch_name, "All Branches") as branch_name'),
                DB::raw('CASE 
                    WHEN tbl_equipment.equipment_quantity > 5 THEN "Good Stock"
                    WHEN tbl_equipment.equipment_quantity > 0 THEN "Low Stock"
                    ELSE "Out of Stock"
                END as stock_status')
            );

        if ($branch) {
            $query->where('tbl_equipment.branch_id', $branch);
        }

        return $query->orderBy('tbl_equipment.equipment_name')->get();
    }

    // Detail view methods - COMPLETED
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
        if (!DB::getSchemaBuilder()->hasTable('tbl_medical_history')) {
            return null;
        }

        return DB::table('tbl_medical_history')
            ->join('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_medical_history.pet_id')
            ->join('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
            ->select('*')
            ->where('tbl_medical_history.id', $medicalId)
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
            ->select('*')
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

    // Additional utility methods for enhanced reporting

    /**
     * Get report statistics for dashboard
     */
    public function getReportStats(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $branch = $request->get('branch');

        $stats = [
            'appointments' => [
                'total' => $this->getAppointmentCount($startDate, $endDate, $branch),
                'completed' => $this->getAppointmentCount($startDate, $endDate, $branch, 'completed'),
                'pending' => $this->getAppointmentCount($startDate, $endDate, $branch, 'pending'),
                'cancelled' => $this->getAppointmentCount($startDate, $endDate, $branch, 'cancelled'),
            ],
            'revenue' => [
                'total' => $this->getTotalRevenue($startDate, $endDate, $branch),
                'average_transaction' => $this->getAverageTransaction($startDate, $endDate, $branch),
                'payment_count' => $this->getPaymentCount($startDate, $endDate, $branch),
            ],
            'pets' => [
                'new_registrations' => $this->getNewPetRegistrations($startDate, $endDate),
                'total_active' => $this->getTotalActivePets(),
            ],
            'inventory' => [
                'low_stock_count' => $this->getLowStockCount($branch),
                'expired_products' => $this->getExpiredProductsCount($branch),
                'total_products' => $this->getTotalProductsCount($branch),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Helper methods for statistics
     */
    private function getAppointmentCount($startDate, $endDate, $branch, $status = null)
    {
        $query = DB::table('tbl_appoint')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_appoint.appoint_date', '>=', $startDate)
            ->whereDate('tbl_appoint.appoint_date', '<=', $endDate);

        if ($branch) {
            $query->where('tbl_user.branch_id', $branch);
        }

        if ($status) {
            $query->where('tbl_appoint.appoint_status', $status);
        }

        return $query->count();
    }

    private function getTotalRevenue($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_bill.bill_date', '>=', $startDate)
            ->whereDate('tbl_bill.bill_date', '<=', $endDate);

        if ($branch) {
            $query->where('tbl_user.branch_id', $branch);
        }

        return $query->sum('tbl_pay.pay_total') ?? 0;
    }

    private function getAverageTransaction($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_bill.bill_date', '>=', $startDate)
            ->whereDate('tbl_bill.bill_date', '<=', $endDate);

        if ($branch) {
            $query->where('tbl_user.branch_id', $branch);
        }

        return $query->avg('tbl_pay.pay_total') ?? 0;
    }

    private function getPaymentCount($startDate, $endDate, $branch)
    {
        $query = DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_bill.bill_date', '>=', $startDate)
            ->whereDate('tbl_bill.bill_date', '<=', $endDate);

        if ($branch) {
            $query->where('tbl_user.branch_id', $branch);
        }

        return $query->count();
    }

    private function getNewPetRegistrations($startDate, $endDate)
    {
        return DB::table('tbl_pet')
            ->whereDate('pet_registration', '>=', $startDate)
            ->whereDate('pet_registration', '<=', $endDate)
            ->count();
    }

    private function getTotalActivePets()
    {
        return DB::table('tbl_pet')->count();
    }

    private function getLowStockCount($branch)
    {
        $query = DB::table('tbl_prod')
            ->whereRaw('prod_stocks <= COALESCE(prod_reorderlevel, 10)');

        if ($branch) {
            $query->where('branch_id', $branch);
        }

        return $query->count();
    }

    private function getExpiredProductsCount($branch)
    {
        $query = DB::table('tbl_prod')
            ->whereDate('prod_expiry', '<=', Carbon::now()->format('Y-m-d'));

        if ($branch) {
            $query->where('branch_id', $branch);
        }

        return $query->count();
    }

    private function getTotalProductsCount($branch)
    {
        $query = DB::table('tbl_prod');

        if ($branch) {
            $query->where('branch_id', $branch);
        }

        return $query->count();
    }

    /**
     * Diagnostic method for troubleshooting database issues
     */
    public function diagnoseData()
    {
        $diagnosis = [];
        
        // Check basic table counts
        $diagnosis['table_counts'] = [
            'appointments' => DB::table('tbl_appoint')->count(),
            'pets' => DB::table('tbl_pet')->count(),
            'owners' => DB::table('tbl_own')->count(),
            'users' => DB::table('tbl_user')->count(),
            'branches' => DB::table('tbl_branch')->count(),
            'products' => DB::table('tbl_prod')->count(),
            'orders' => DB::table('tbl_ord')->count(),
            'bills' => DB::table('tbl_bill')->count(),
            'payments' => DB::table('tbl_pay')->count(),
            'services' => DB::table('tbl_serv')->count(),
            'medical_histories' => DB::getSchemaBuilder()->hasTable('tbl_medical_history') ? 
                DB::table('tbl_medical_history')->count() : 'Table not found',
            'prescriptions' => DB::getSchemaBuilder()->hasTable('tbl_prescription') ? 
                DB::table('tbl_prescription')->count() : 'Table not found',
            'referrals' => DB::getSchemaBuilder()->hasTable('tbl_ref') ? 
                DB::table('tbl_ref')->count() : 'Table not found',
            'equipment' => DB::getSchemaBuilder()->hasTable('tbl_equipment') ? 
                DB::table('tbl_equipment')->count() : 'Table not found',
        ];
        
        // Sample data from each table
        $diagnosis['sample_data'] = [
            'latest_appointment' => DB::table('tbl_appoint')->orderBy('appoint_id', 'desc')->first(),
            'latest_pet' => DB::table('tbl_pet')->orderBy('pet_id', 'desc')->first(),
            'latest_order' => DB::table('tbl_ord')->orderBy('ord_id', 'desc')->first(),
            'latest_billing' => DB::table('tbl_bill')->orderBy('bill_id', 'desc')->first(),
            'latest_payment' => DB::table('tbl_pay')->orderBy('pay_id', 'desc')->first(),
            'branches' => DB::table('tbl_branch')->get(),
            'service_types' => DB::table('tbl_serv')->select('serv_type')->distinct()->get(),
        ];

        // Data integrity checks
        $diagnosis['data_integrity'] = [
            'orphaned_pets' => DB::table('tbl_pet')
                ->leftJoin('tbl_own', 'tbl_own.own_id', '=', 'tbl_pet.own_id')
                ->whereNull('tbl_own.own_id')
                ->count(),
            'appointments_without_pets' => DB::table('tbl_appoint')
                ->leftJoin('tbl_pet', 'tbl_pet.pet_id', '=', 'tbl_appoint.pet_id')
                ->whereNull('tbl_pet.pet_id')
                ->count(),
            'bills_without_appointments' => DB::table('tbl_bill')
                ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
                ->whereNull('tbl_appoint.appoint_id')
                ->count(),
            'payments_without_bills' => DB::table('tbl_pay')
                ->leftJoin('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
                ->whereNull('tbl_bill.bill_id')
                ->count(),
            'users_without_branches' => DB::table('tbl_user')
                ->leftJoin('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id')
                ->whereNull('tbl_branch.branch_id')
                ->count(),
        ];

        return response()->json($diagnosis, JSON_PRETTY_PRINT);
    }

    /**
     * Generate summary report across all modules
     */
    public function getSummaryReport(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $branch = $request->get('branch');

        $summary = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
            ],
            'appointments' => $this->getAppointmentsSummary($startDate, $endDate, $branch),
            'revenue' => $this->getRevenueSummary($startDate, $endDate, $branch),
            'sales' => $this->getSalesSummary($startDate, $endDate, $branch),
            'inventory' => $this->getInventorySummary($branch),
            'staff' => $this->getStaffSummary($branch),
            'branch_performance' => $this->getBranchPerformanceSummary($startDate, $endDate)
        ];

        return response()->json($summary);
    }

    private function getAppointmentsSummary($startDate, $endDate, $branch)
    {
        $baseQuery = DB::table('tbl_appoint')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_appoint.appoint_date', '>=', $startDate)
            ->whereDate('tbl_appoint.appoint_date', '<=', $endDate);

        if ($branch) {
            $baseQuery->where('tbl_user.branch_id', $branch);
        }

        return [
            'total' => $baseQuery->count(),
            'completed' => (clone $baseQuery)->where('tbl_appoint.appoint_status', 'completed')->count(),
            'pending' => (clone $baseQuery)->where('tbl_appoint.appoint_status', 'pending')->count(),
            'cancelled' => (clone $baseQuery)->where('tbl_appoint.appoint_status', 'cancelled')->count(),
            'completion_rate' => $baseQuery->count() > 0 ? 
                round((clone $baseQuery)->where('tbl_appoint.appoint_status', 'completed')->count() / $baseQuery->count() * 100, 2) : 0
        ];
    }

    private function getRevenueSummary($startDate, $endDate, $branch)
    {
        $baseQuery = DB::table('tbl_pay')
            ->join('tbl_bill', 'tbl_bill.bill_id', '=', 'tbl_pay.bill_id')
            ->leftJoin('tbl_appoint', 'tbl_appoint.appoint_id', '=', 'tbl_bill.appoint_id')
            ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_appoint.user_id')
            ->whereDate('tbl_bill.bill_date', '>=', $startDate)
            ->whereDate('tbl_bill.bill_date', '<=', $endDate);

        if ($branch) {
            $baseQuery->where('tbl_user.branch_id', $branch);
        }

        $total = $baseQuery->sum('tbl_pay.pay_total') ?? 0;
        $count = $baseQuery->count();

        return [
            'total_revenue' => $total,
            'transaction_count' => $count,
            'average_transaction' => $count > 0 ? round($total / $count, 2) : 0,
            'daily_average' => round($total / (Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1), 2)
        ];
    }

    private function getSalesSummary($startDate, $endDate, $branch)
    {
        $baseQuery = DB::table('tbl_ord')
            ->join('tbl_user', 'tbl_user.user_id', '=', 'tbl_ord.user_id')
            ->whereDate('tbl_ord.ord_date', '>=', $startDate)
            ->whereDate('tbl_ord.ord_date', '<=', $endDate);

        if ($branch) {
            $baseQuery->where('tbl_user.branch_id', $branch);
        }

        return [
            'total_orders' => $baseQuery->count(),
            'total_quantity' => $baseQuery->sum('tbl_ord.ord_quantity') ?? 0,
            'total_amount' => $baseQuery->sum('tbl_ord.ord_total') ?? 0,
            'average_order_value' => $baseQuery->count() > 0 ? 
                round($baseQuery->sum('tbl_ord.ord_total') / $baseQuery->count(), 2) : 0
        ];
    }

    private function getInventorySummary($branch)
    {
        $baseQuery = DB::table('tbl_prod');

        if ($branch) {
            $baseQuery->where('branch_id', $branch);
        }

        $totalProducts = $baseQuery->count();
        $lowStock = (clone $baseQuery)->whereRaw('prod_stocks <= COALESCE(prod_reorderlevel, 10)')->count();
        $expired = (clone $baseQuery)->whereDate('prod_expiry', '<=', Carbon::now()->format('Y-m-d'))->count();
        $expiringSoon = (clone $baseQuery)->whereDate('prod_expiry', '<=', Carbon::now()->addDays(30)->format('Y-m-d'))->count();

        return [
            'total_products' => $totalProducts,
            'low_stock_items' => $lowStock,
            'expired_items' => $expired,
            'expiring_soon' => $expiringSoon,
            'total_stock_value' => $baseQuery->selectRaw('SUM(prod_price * prod_stocks)')->value('SUM(prod_price * prod_stocks)') ?? 0,
            'health_percentage' => $totalProducts > 0 ? 
                round(($totalProducts - $lowStock - $expired) / $totalProducts * 100, 2) : 100
        ];
    }

    private function getStaffSummary($branch)
    {
        $baseQuery = DB::table('tbl_user')
            ->join('tbl_branch', 'tbl_branch.branch_id', '=', 'tbl_user.branch_id');

        if ($branch) {
            $baseQuery->where('tbl_user.branch_id', $branch);
        }

        return [
            'total_staff' => $baseQuery->count(),
            'active_staff' => (clone $baseQuery)->where('tbl_user.user_status', 'active')->count(),
            'veterinarians' => (clone $baseQuery)->where('tbl_user.user_role', 'veterinarian')->count(),
            'staff_by_role' => DB::table('tbl_user')
                ->select('user_role', DB::raw('COUNT(*) as count'))
                ->when($branch, function($query) use ($branch) {
                    return $query->where('branch_id', $branch);
                })
                ->groupBy('user_role')
                ->get()
        ];
    }

    private function getBranchPerformanceSummary($startDate, $endDate)
    {
        return DB::table('tbl_branch')
            ->select(
                'tbl_branch.branch_name',
                DB::raw('(SELECT COUNT(*) FROM tbl_user WHERE tbl_user.branch_id = tbl_branch.branch_id) as staff_count'),
                DB::raw('(SELECT COUNT(*) FROM tbl_appoint 
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id 
                          WHERE tbl_user.branch_id = tbl_branch.branch_id 
                          AND DATE(tbl_appoint.appoint_date) BETWEEN "' . $startDate . '" AND "' . $endDate . '") as appointments'),
                DB::raw('(SELECT COALESCE(SUM(tbl_pay.pay_total), 0) FROM tbl_pay 
                          JOIN tbl_bill ON tbl_bill.bill_id = tbl_pay.bill_id
                          LEFT JOIN tbl_appoint ON tbl_appoint.appoint_id = tbl_bill.appoint_id
                          LEFT JOIN tbl_user ON tbl_user.user_id = tbl_appoint.user_id
                          WHERE tbl_user.branch_id = tbl_branch.branch_id 
                          AND DATE(tbl_bill.bill_date) BETWEEN "' . $startDate . '" AND "' . $endDate . '") as revenue')
            )
            ->orderBy('revenue', 'desc')
            ->get();
    }
}
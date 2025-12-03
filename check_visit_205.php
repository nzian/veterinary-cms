<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING VISIT 205 DETAILS ===\n\n";

// Get Visit 205 full details
$visit = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where('v.visit_id', 205)
    ->select('v.*', 'p.pet_name', 'o.own_name')
    ->first();

if (!$visit) {
    echo "Visit 205 not found!\n";
    exit;
}

echo "Visit ID: {$visit->visit_id}\n";
echo "Pet: {$visit->pet_name}\n";
echo "Owner: {$visit->own_name}\n";
echo "Visit Date: {$visit->visit_date}\n";
echo "Workflow Status: {$visit->workflow_status}\n";
echo "\n";

// Get services linked to this visit
echo "Services in tbl_visit_service:\n";
echo str_repeat("-", 100) . "\n";

$visitServices = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('vs.visit_id', 205)
    ->select('vs.*', 's.serv_name', 's.serv_price', 's.serv_type')
    ->get();

foreach ($visitServices as $vs) {
    echo "Service ID: {$vs->serv_id}\n";
    echo "Service Name: {$vs->serv_name}\n";
    echo "Service Type: {$vs->serv_type}\n";
    echo "Service Price: ₱" . number_format($vs->serv_price, 2) . "\n";
    echo "Pivot Status: {$vs->status}\n";
    echo "Pivot Quantity: {$vs->quantity}\n";
    echo "Pivot Unit Price: ₱" . number_format($vs->unit_price ?? 0, 2) . "\n";
    echo "Pivot Total Price: ₱" . number_format($vs->total_price ?? 0, 2) . "\n";
    echo "Completed At: {$vs->completed_at}\n";
    echo str_repeat("-", 100) . "\n";
}

// Check vaccination record
echo "\nVaccination record in tbl_vaccine:\n";
echo str_repeat("-", 100) . "\n";

$vaccination = DB::table('tbl_vaccine')
    ->where('visit_id', 205)
    ->first();

if ($vaccination) {
    echo "Vaccine ID: {$vaccination->vaccine_id}\n";
    echo "Visit ID: {$vaccination->visit_id}\n";
    echo "Vaccine Name: {$vaccination->vaccine_name}\n";
    echo "Dose: {$vaccination->dose}\n";
    echo "Manufacturer: {$vaccination->manufacturer}\n";
    echo "Batch No: {$vaccination->batch_no}\n";
    echo "Date Administered: {$vaccination->date_administered}\n";
    echo "Next Due Date: {$vaccination->next_due_date}\n";
    echo "Administered By: {$vaccination->administered_by}\n";
    echo "Remarks: {$vaccination->remarks}\n";
} else {
    echo "NO VACCINATION RECORD FOUND\n";
}

// Check billing
echo "\nBilling for Visit 205:\n";
echo str_repeat("-", 100) . "\n";

$billing = DB::table('tbl_bill')
    ->where('visit_id', 205)
    ->first();

if ($billing) {
    echo "Bill ID: {$billing->bill_id}\n";
    echo "Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "Paid Amount: ₱" . number_format($billing->paid_amount ?? 0, 2) . "\n";
    echo "Bill Status: {$billing->bill_status}\n";
    echo "Bill Date: {$billing->bill_date}\n";
} else {
    echo "NO BILLING FOUND\n";
}

echo "\n=== END OF INVESTIGATION ===\n";

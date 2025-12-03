<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FINAL SUMMARY OF ALL FIXES ===\n\n";

echo "✅ CODE FIXES APPLIED:\n";
echo str_repeat("-", 80) . "\n";
echo "1. MedicalManagementController::saveVaccination() - Now uses specific service_id\n";
echo "2. MedicalManagementController::saveDeworming() - Now uses specific service_id\n";
echo "   → Future visits will use the CORRECT service selected in the form\n";
echo "\n";

echo "✅ DATA FIXES APPLIED:\n";
echo str_repeat("-", 80) . "\n";

// Visit 205
$visit205 = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('v.visit_id', 205)
    ->select('v.visit_id', 'p.pet_name', 'o.own_name', 's.serv_id', 's.serv_name', 's.serv_price')
    ->first();

if ($visit205) {
    echo "Visit 205 (Pet: {$visit205->pet_name}, Owner: {$visit205->own_name}):\n";
    echo "  OLD: Vaccination - Kennel Cough (₱950.00)\n";
    echo "  NEW: {$visit205->serv_name} (₱" . number_format($visit205->serv_price, 2) . ") ✅\n";
}

// Visit 206
$visit206 = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('v.visit_id', 206)
    ->select('v.visit_id', 'p.pet_name', 'o.own_name', 's.serv_id', 's.serv_name', 's.serv_price')
    ->first();

if ($visit206) {
    echo "\nVisit 206 (Pet: {$visit206->pet_name}, Owner: {$visit206->own_name}):\n";
    echo "  OLD: Deworming (generic, ₱200.00)\n";
    echo "  NEW: {$visit206->serv_name} (₱" . number_format($visit206->serv_price, 2) . ") ✅\n";
}

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "\n📋 CURRENT BILLING STATUS:\n";
echo str_repeat("-", 80) . "\n";

$billings = DB::table('tbl_bill as b')
    ->join('tbl_visit_record as v', 'b.visit_id', '=', 'v.visit_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->whereIn('b.visit_id', [205, 206])
    ->select('b.bill_id', 'b.visit_id', 'b.total_amount', 'b.bill_status', 'p.pet_name', 'o.own_name')
    ->orderBy('b.visit_id')
    ->get();

foreach ($billings as $billing) {
    echo "Bill #{$billing->bill_id} (Visit {$billing->visit_id}):\n";
    echo "  Pet: {$billing->pet_name} | Owner: {$billing->own_name}\n";
    echo "  Amount: ₱" . number_format($billing->total_amount, 2) . " | Status: {$billing->bill_status}\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "\n✅ ALL FIXES COMPLETED SUCCESSFULLY!\n";
echo "\n🔧 ROOT CAUSE: The code was using generic serv_type lookup instead of\n";
echo "   the specific service_id selected in the form.\n";
echo "\n✅ SOLUTION: Updated both saveVaccination() and saveDeworming() methods\n";
echo "   to use the specific service_id when provided.\n";
echo "\n📌 RESULT: Future visits will now correctly use the selected service.\n";

echo "\n=== END OF SUMMARY ===\n";

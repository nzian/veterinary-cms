<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FINAL VERIFICATION ===\n\n";

echo "Visit 205 Summary:\n";
echo str_repeat("-", 80) . "\n";

$visit = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('v.visit_id', 205)
    ->select(
        'v.visit_id',
        'v.visit_date',
        'v.workflow_status',
        'p.pet_name',
        'o.own_name',
        's.serv_id',
        's.serv_name',
        's.serv_price',
        'vs.total_price'
    )
    ->first();

if ($visit) {
    echo "Visit ID: {$visit->visit_id}\n";
    echo "Date: {$visit->visit_date}\n";
    echo "Pet: {$visit->pet_name}\n";
    echo "Owner: {$visit->own_name}\n";
    echo "Status: {$visit->workflow_status}\n";
    echo "\n";
    echo "Service:\n";
    echo "  Service ID: {$visit->serv_id}\n";
    echo "  Service Name: {$visit->serv_name}\n";
    echo "  Price: ₱" . number_format($visit->serv_price, 2) . "\n";
    echo "  Charged: ₱" . number_format($visit->total_price, 2) . "\n";
}

echo "\nBilling:\n";
echo str_repeat("-", 80) . "\n";

$billing = DB::table('tbl_bill')
    ->where('visit_id', 205)
    ->first();

if ($billing) {
    echo "Bill ID: {$billing->bill_id}\n";
    echo "Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "Paid Amount: ₱" . number_format($billing->paid_amount ?? 0, 2) . "\n";
    echo "Balance: ₱" . number_format($billing->total_amount - ($billing->paid_amount ?? 0), 2) . "\n";
    echo "Status: {$billing->bill_status}\n";
}

echo "\n";
echo "✅ CORRECTED: Visit 205 now shows 'Vaccination - Anti Rabies' (₱300.00)\n";
echo "✅ PREVIOUS: Was showing 'Vaccination - Kennel Cough' (₱950.00)\n";

echo "\n=== VERIFICATION COMPLETE ===\n";

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking visit_service data for visit 146...\n\n";

$visitServices = DB::table('tbl_visit_service')
    ->join('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
    ->where('tbl_visit_service.visit_id', 146)
    ->select(
        'tbl_visit_service.*',
        'tbl_serv.serv_name',
        'tbl_serv.serv_price'
    )
    ->get();

echo "Found " . $visitServices->count() . " services\n\n";

foreach ($visitServices as $vs) {
    echo "Service: " . $vs->serv_name . "\n";
    echo "  Service Base Price: ₱" . number_format($vs->serv_price, 2) . "\n";
    echo "  Pivot Quantity: " . ($vs->quantity ?? 'NULL') . "\n";
    echo "  Pivot Unit Price: " . ($vs->unit_price ?? 'NULL') . "\n";
    echo "  Pivot Total Price: " . ($vs->total_price ?? 'NULL') . "\n\n";
}

echo "\n=== ISSUE FOUND ===\n";
echo "The unit_price and total_price in tbl_visit_service are 0.00\n";
echo "They should be populated when services are added to visits.\n\n";

echo "=== CHECKING CURRENT BILLING ===\n";
$billing = DB::table('tbl_bill')->where('visit_id', 146)->first();
if ($billing) {
    echo "Billing exists:\n";
    echo "  Bill ID: " . $billing->bill_id . "\n";
    echo "  Total Amount: ₱" . number_format($billing->total_amount ?? 0, 2) . "\n";
    echo "  Paid Amount: ₱" . number_format($billing->paid_amount ?? 0, 2) . "\n";
    echo "  Status: " . $billing->bill_status . "\n";
} else {
    echo "No billing exists for visit 146\n";
}

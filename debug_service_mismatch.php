<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visit;
use App\Models\Billing;
use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING BILLING SERVICE MISMATCH ===\n\n";

// Check visit 146 details
$visit = Visit::with(['services', 'pet.owner'])->find(146);

echo "Visit ID: " . $visit->visit_id . "\n";
echo "Pet: " . $visit->pet->pet_name . "\n";
echo "Owner: " . $visit->pet->owner->own_name . "\n\n";

echo "=== Services attached to this visit ===\n";
$visitServices = DB::table('tbl_visit_service')
    ->join('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
    ->where('tbl_visit_service.visit_id', 146)
    ->select('tbl_visit_service.*', 'tbl_serv.serv_name', 'tbl_serv.serv_price', 'tbl_serv.serv_type')
    ->get();

foreach ($visitServices as $vs) {
    echo "Service ID: " . $vs->serv_id . "\n";
    echo "Service Name: " . $vs->serv_name . "\n";
    echo "Service Type: " . $vs->serv_type . "\n";
    echo "Service Price: ₱" . number_format($vs->serv_price, 2) . "\n\n";
}

echo "=== Via Eloquent Relationship ===\n";
foreach ($visit->services as $service) {
    echo "Service ID: " . $service->serv_id . "\n";
    echo "Service Name: " . $service->serv_name . "\n";
    echo "Service Type: " . $service->serv_type . "\n";
    echo "Service Price: ₱" . number_format($service->serv_price, 2) . "\n\n";
}

// Check billing
echo "=== Billing for this visit ===\n";
$billing = Billing::with('visit.services')->where('visit_id', 146)->latest()->first();

if ($billing) {
    echo "Bill ID: " . $billing->bill_id . "\n";
    echo "Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n\n";
    
    echo "Services in billing:\n";
    foreach ($billing->visit->services as $service) {
        echo "  - " . $service->serv_name . " (ID: " . $service->serv_id . ")\n";
    }
} else {
    echo "No billing found\n";
}

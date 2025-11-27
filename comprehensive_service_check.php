<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visit;
use App\Models\Billing;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

echo "=== COMPREHENSIVE SERVICE INVESTIGATION ===\n\n";

// Check all services in the database
echo "=== ALL SERVICES ===\n";
$allServices = DB::table('tbl_serv')
    ->select('serv_id', 'serv_name', 'serv_price', 'serv_type')
    ->orderBy('serv_id')
    ->get();

foreach ($allServices as $svc) {
    echo "ID: {$svc->serv_id} | Name: {$svc->serv_name} | Type: {$svc->serv_type} | Price: ₱" . number_format($svc->serv_price, 2) . "\n";
}

echo "\n=== VISIT 146 INVESTIGATION ===\n";
$visit146 = Visit::with(['services', 'pet.owner'])->find(146);

echo "Visit ID: 146\n";
echo "Pet: " . $visit146->pet->pet_name . "\n";
echo "Owner: " . $visit146->pet->owner->own_name . "\n";
echo "Workflow Status: " . $visit146->workflow_status . "\n\n";

echo "Services attached to visit (via relationship):\n";
foreach ($visit146->services as $service) {
    echo "  - ID: {$service->serv_id} | Name: {$service->serv_name} | Type: {$service->serv_type}\n";
    echo "    Price: ₱" . number_format($service->serv_price, 2) . "\n";
    echo "    Pivot data:\n";
    echo "      - Quantity: " . ($service->pivot->quantity ?? 'NULL') . "\n";
    echo "      - Unit Price: " . ($service->pivot->unit_price ?? 'NULL') . "\n";
    echo "      - Total Price: " . ($service->pivot->total_price ?? 'NULL') . "\n";
}

echo "\n=== BILLING FOR VISIT 146 ===\n";
$billing = Billing::with(['visit.services'])->where('visit_id', 146)->orderBy('bill_id', 'desc')->first();

if ($billing) {
    echo "Bill ID: {$billing->bill_id}\n";
    echo "Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "Status: {$billing->bill_status}\n\n";
    
    echo "Services in billing (via visit relationship):\n";
    foreach ($billing->visit->services as $service) {
        echo "  - ID: {$service->serv_id} | Name: {$service->serv_name} | Type: {$service->serv_type}\n";
    }
    
    // Simulate what the controller does
    echo "\n=== SIMULATING CONTROLLER RESPONSE ===\n";
    $services = $billing->visit->services->map(function($service) {
        $quantity = $service->pivot->quantity ?? 1;
        $unitPrice = $service->pivot->unit_price ?? $service->serv_price;
        return $service->serv_name . ' (x' . $quantity . ')';
    })->implode(', ');
    
    echo "Services string: " . $services . "\n";
} else {
    echo "No billing found for visit 146\n";
}

// Check if there's any other data that might be confusing
echo "\n=== CHECKING FOR DATA ANOMALIES ===\n";
$visitService = DB::table('tbl_visit_service')
    ->where('visit_id', 146)
    ->get();

echo "Records in tbl_visit_service for visit 146:\n";
foreach ($visitService as $vs) {
    $service = DB::table('tbl_serv')->where('serv_id', $vs->serv_id)->first();
    echo "  - Visit Service ID: {$vs->id}\n";
    echo "    Service ID: {$vs->serv_id}\n";
    echo "    Service Name: " . ($service ? $service->serv_name : 'NOT FOUND') . "\n";
    echo "    Unit Price: " . ($vs->unit_price ?? 'NULL') . "\n";
    echo "    Total Price: " . ($vs->total_price ?? 'NULL') . "\n\n";
}

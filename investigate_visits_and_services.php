<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visit;
use App\Models\Billing;
use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING VISIT AND SERVICE ISSUES ===\n\n";

// Get all visits today
$today = \Carbon\Carbon::today()->toDateString();
$visits = Visit::with(['services', 'pet.owner', 'billing'])
    ->whereDate('visit_date', $today)
    ->get();

echo "Total visits today: " . $visits->count() . "\n\n";

foreach ($visits as $visit) {
    echo "========================================\n";
    echo "VISIT ID: {$visit->visit_id}\n";
    echo "Pet: " . $visit->pet->pet_name . "\n";
    echo "Owner: " . $visit->pet->owner->own_name . "\n";
    echo "Service Type: " . $visit->service_type . "\n";
    echo "Visit Service Type: " . ($visit->visit_service_type ?? 'NULL') . "\n";
    echo "Workflow Status: " . $visit->workflow_status . "\n";
    echo "Visit Status: " . ($visit->visit_status ?? 'NULL') . "\n\n";
    
    echo "--- Services in tbl_visit_service ---\n";
    $visitServices = DB::table('tbl_visit_service')
        ->join('tbl_serv', 'tbl_visit_service.serv_id', '=', 'tbl_serv.serv_id')
        ->where('tbl_visit_service.visit_id', $visit->visit_id)
        ->select('tbl_visit_service.*', 'tbl_serv.serv_name', 'tbl_serv.serv_type', 'tbl_serv.serv_price')
        ->get();
    
    if ($visitServices->count() > 0) {
        foreach ($visitServices as $vs) {
            echo "  Service ID: {$vs->serv_id}\n";
            echo "  Service Name: {$vs->serv_name}\n";
            echo "  Service Type: {$vs->serv_type}\n";
            echo "  Service Price: ₱" . number_format($vs->serv_price, 2) . "\n";
            echo "  Pivot Unit Price: ₱" . number_format($vs->unit_price ?? 0, 2) . "\n";
            echo "  Pivot Total Price: ₱" . number_format($vs->total_price ?? 0, 2) . "\n\n";
        }
    } else {
        echo "  No services attached!\n\n";
    }
    
    echo "--- Via Eloquent Relationship ---\n";
    foreach ($visit->services as $service) {
        echo "  Service ID: {$service->serv_id}\n";
        echo "  Service Name: {$service->serv_name}\n";
        echo "  Service Type: {$service->serv_type}\n";
        echo "  Service Price: ₱" . number_format($service->serv_price, 2) . "\n\n";
    }
    
    // Check billing
    if ($visit->billing) {
        echo "--- BILLING ---\n";
        echo "Bill ID: " . $visit->billing->bill_id . "\n";
        echo "Total Amount: ₱" . number_format($visit->billing->total_amount, 2) . "\n";
        echo "Paid Amount: ₱" . number_format($visit->billing->paid_amount, 2) . "\n";
        echo "Bill Status: " . $visit->billing->bill_status . "\n\n";
        
        echo "Services shown in billing (via relationship):\n";
        foreach ($visit->billing->visit->services as $service) {
            echo "  - {$service->serv_name} (ID: {$service->serv_id})\n";
        }
    } else {
        echo "--- NO BILLING YET ---\n";
    }
    
    echo "\n\n";
}

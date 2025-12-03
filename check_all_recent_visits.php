<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK FOR SERVICE MISMATCHES ===\n\n";

echo "Recent visits that might have wrong services:\n";
echo str_repeat("-", 100) . "\n";

// Check all completed visits from today and yesterday
$visits = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->whereDate('v.visit_date', '>=', now()->subDay())
    ->where('v.workflow_status', 'Completed')
    ->select('v.visit_id', 'v.visit_date', 'p.pet_name', 'o.own_name', 'v.workflow_status')
    ->orderBy('v.visit_id', 'desc')
    ->get();

foreach ($visits as $visit) {
    echo "\nVisit ID: {$visit->visit_id} | Date: {$visit->visit_date} | Pet: {$visit->pet_name} | Owner: {$visit->own_name}\n";
    
    // Get services
    $services = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', $visit->visit_id)
        ->select('s.serv_id', 's.serv_name', 's.serv_type', 's.serv_price', 'vs.total_price')
        ->get();
    
    foreach ($services as $svc) {
        echo "  - Service ID {$svc->serv_id}: {$svc->serv_name} ({$svc->serv_type}) - ₱" . number_format($svc->serv_price, 2) . "\n";
    }
    
    // Get billing
    $billing = DB::table('tbl_bill')
        ->where('visit_id', $visit->visit_id)
        ->first();
    
    if ($billing) {
        echo "  Billing: Bill #{$billing->bill_id} - ₱" . number_format($billing->total_amount, 2) . " - {$billing->bill_status}\n";
    } else {
        echo "  Billing: NOT CREATED\n";
    }
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "\nVisits that need correction:\n";
echo "1. Visit 205 - Already fixed to Anti-Rabies\n";
echo "2. Any other visits with wrong services? Please review above.\n";

echo "\n=== END ===\n";

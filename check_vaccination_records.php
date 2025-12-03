<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VACCINATION SERVICE INVESTIGATION ===\n\n";

// 1. Find all vaccination services
echo "1. All vaccination services in tbl_serv:\n";
echo str_repeat("-", 100) . "\n";

$vaccinations = DB::table('tbl_serv')
    ->where(function($query) {
        $query->where('serv_name', 'LIKE', '%Vaccination%')
              ->orWhere('serv_name', 'LIKE', '%Rabies%')
              ->orWhere('serv_name', 'LIKE', '%Kennel%');
    })
    ->select('serv_id', 'serv_name', 'serv_price', 'serv_type')
    ->orderBy('serv_id')
    ->get();

foreach ($vaccinations as $service) {
    echo sprintf("ID: %3d | %-60s | Type: %-20s | Price: ₱%s\n", 
        $service->serv_id, 
        $service->serv_name,
        $service->serv_type ?? 'N/A',
        number_format($service->serv_price, 2)
    );
}

// 2. Find the most recent visit
echo "\n2. Most recent visit created:\n";
echo str_repeat("-", 100) . "\n";

$recentVisit = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->select('v.*', 'p.pet_name', 'o.own_name')
    ->orderBy('v.visit_id', 'desc')
    ->first();

if ($recentVisit) {
    echo "Visit ID: {$recentVisit->visit_id}\n";
    echo "Pet: {$recentVisit->pet_name}\n";
    echo "Owner: {$recentVisit->own_name}\n";
    echo "Visit Date: {$recentVisit->visit_date}\n";
    echo "Workflow Status: {$recentVisit->workflow_status}\n";
    
    // Get services for this visit
    echo "\nServices for this visit:\n";
    $services = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', $recentVisit->visit_id)
        ->select('vs.*', 's.serv_name', 's.serv_price')
        ->get();
    
    foreach ($services as $service) {
        echo "  - Service ID: {$service->serv_id} | {$service->serv_name} | ₱" . number_format($service->serv_price, 2) . "\n";
    }
    
    // Check if billing exists
    echo "\nBilling for this visit:\n";
    $billing = DB::table('tbl_bill')
        ->where('visit_id', $recentVisit->visit_id)
        ->first();
    
    if ($billing) {
        echo "  Billing ID: {$billing->bill_id}\n";
        echo "  Total: ₱" . number_format($billing->total_amount, 2) . "\n";
        echo "  Status: {$billing->bill_status}\n";
    } else {
        echo "  NO BILLING CREATED\n";
    }
}

// 3. Check what service was actually recorded vs what the billing says
echo "\n3. Checking for any Anti-Rabies records today:\n";
echo str_repeat("-", 100) . "\n";

$rabiesRecords = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->whereDate('v.visit_date', now()->toDateString())
    ->where('s.serv_name', 'LIKE', '%Rabies%')
    ->select('v.visit_id', 's.serv_id', 's.serv_name')
    ->get();

if ($rabiesRecords->isEmpty()) {
    echo "NO ANTI-RABIES RECORDS FOUND TODAY\n";
    echo "This means NO visit was recorded with Anti-Rabies service.\n";
} else {
    foreach ($rabiesRecords as $record) {
        echo "Visit ID: {$record->visit_id} | Service ID: {$record->serv_id} | {$record->serv_name}\n";
    }
}

// 4. What about Kennel Cough today?
echo "\n4. Kennel Cough records today:\n";
echo str_repeat("-", 100) . "\n";

$kennelRecords = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->whereDate('v.visit_date', now()->toDateString())
    ->where('s.serv_name', 'LIKE', '%Kennel%')
    ->select('v.visit_id', 'v.visit_date', 'p.pet_name', 'o.own_name', 's.serv_id', 's.serv_name', 'v.workflow_status')
    ->get();

if ($kennelRecords->isEmpty()) {
    echo "NO KENNEL COUGH RECORDS TODAY\n";
} else {
    foreach ($kennelRecords as $record) {
        echo "Visit ID: {$record->visit_id} | Pet: {$record->pet_name} | Owner: {$record->own_name} | Service ID: {$record->serv_id} | {$record->serv_name} | Status: {$record->workflow_status}\n";
    }
}

echo "\n=== END OF INVESTIGATION ===\n";

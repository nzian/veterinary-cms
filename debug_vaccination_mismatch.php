<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING VACCINATION MISMATCH ===\n\n";

// Get the most recent visits with Anti-Rabies service
echo "1. Recent visits with Anti-Rabies service:\n";
echo str_repeat("-", 80) . "\n";

$antiRabiesVisits = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where('s.serv_name', 'LIKE', '%Rabies%')
    ->whereDate('v.visit_date', '>=', now()->subDays(2))
    ->select(
        'v.visit_id',
        'v.visit_date',
        'v.workflow_status',
        'p.pet_name',
        'o.own_name',
        'vs.serv_id',
        's.serv_name',
        's.serv_price',
        'vs.unit_price',
        'vs.total_price'
    )
    ->orderBy('v.visit_id', 'desc')
    ->get();

foreach ($antiRabiesVisits as $visit) {
    echo "Visit ID: {$visit->visit_id}\n";
    echo "Date: {$visit->visit_date}\n";
    echo "Pet: {$visit->pet_name}\n";
    echo "Owner: {$visit->own_name}\n";
    echo "Service ID: {$visit->serv_id}\n";
    echo "Service Name: {$visit->serv_name}\n";
    echo "Service Price: ₱" . number_format($visit->serv_price, 2) . "\n";
    echo "Pivot Unit Price: ₱" . number_format($visit->unit_price ?? 0, 2) . "\n";
    echo "Pivot Total Price: ₱" . number_format($visit->total_price ?? 0, 2) . "\n";
    echo "Workflow Status: {$visit->workflow_status}\n";
    
    // Check if billing exists
    $billing = DB::table('tbl_bill')
        ->where('visit_id', $visit->visit_id)
        ->first();
    
    if ($billing) {
        echo "Billing ID: {$billing->bill_id}\n";
        echo "Billing Total: ₱" . number_format($billing->total_amount, 2) . "\n";
        echo "Billing Status: {$billing->bill_status}\n";
    } else {
        echo "Billing: NOT CREATED YET\n";
    }
    echo str_repeat("-", 80) . "\n";
}

// Get the Kennel Cough visits
echo "\n2. Recent visits with Kennel Cough service:\n";
echo str_repeat("-", 80) . "\n";

$kennelCoughVisits = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where('s.serv_name', 'LIKE', '%Kennel%')
    ->whereDate('v.visit_date', '>=', now()->subDays(2))
    ->select(
        'v.visit_id',
        'v.visit_date',
        'v.workflow_status',
        'p.pet_name',
        'o.own_name',
        'vs.serv_id',
        's.serv_name',
        's.serv_price',
        'vs.unit_price',
        'vs.total_price'
    )
    ->orderBy('v.visit_id', 'desc')
    ->get();

foreach ($kennelCoughVisits as $visit) {
    echo "Visit ID: {$visit->visit_id}\n";
    echo "Date: {$visit->visit_date}\n";
    echo "Pet: {$visit->pet_name}\n";
    echo "Owner: {$visit->own_name}\n";
    echo "Service ID: {$visit->serv_id}\n";
    echo "Service Name: {$visit->serv_name}\n";
    echo "Service Price: ₱" . number_format($visit->serv_price, 2) . "\n";
    echo "Workflow Status: {$visit->workflow_status}\n";
    
    // Check if billing exists
    $billing = DB::table('tbl_bill')
        ->where('visit_id', $visit->visit_id)
        ->first();
    
    if ($billing) {
        echo "Billing ID: {$billing->bill_id}\n";
        echo "Billing Total: ₱" . number_format($billing->total_amount, 2) . "\n";
        echo "Billing Status: {$billing->bill_status}\n";
    } else {
        echo "Billing: NOT CREATED YET\n";
    }
    echo str_repeat("-", 80) . "\n";
}

// Check all service IDs in tbl_serv
echo "\n3. All vaccination services in tbl_serv:\n";
echo str_repeat("-", 80) . "\n";

$vaccinations = DB::table('tbl_serv')
    ->where('serv_name', 'LIKE', '%Vaccination%')
    ->orWhere('serv_name', 'LIKE', '%Rabies%')
    ->orWhere('serv_name', 'LIKE', '%Kennel%')
    ->select('serv_id', 'serv_name', 'serv_price', 'service_type')
    ->orderBy('serv_id')
    ->get();

foreach ($vaccinations as $service) {
    echo "Service ID: {$service->serv_id} | {$service->serv_name} | Type: {$service->service_type} | Price: ₱" . number_format($service->serv_price, 2) . "\n";
}

echo "\n=== END OF INVESTIGATION ===\n";

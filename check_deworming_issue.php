<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING DEWORMING ISSUE ===\n\n";

// 1. Find all deworming services available
echo "1. All Deworming services in tbl_serv:\n";
echo str_repeat("-", 100) . "\n";

$dewormingServices = DB::table('tbl_serv')
    ->where('serv_name', 'LIKE', '%Deworm%')
    ->orWhere('serv_type', 'LIKE', '%deworm%')
    ->select('serv_id', 'serv_name', 'serv_price', 'serv_type')
    ->orderBy('serv_id')
    ->get();

foreach ($dewormingServices as $service) {
    echo sprintf("ID: %3d | %-50s | Type: %-20s | Price: ₱%s\n", 
        $service->serv_id, 
        $service->serv_name,
        $service->serv_type ?? 'N/A',
        number_format($service->serv_price, 2)
    );
}

// 2. Find recent deworming visits
echo "\n2. Recent visits with Deworming services (last 2 days):\n";
echo str_repeat("-", 100) . "\n";

$dewormingVisits = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where(function($query) {
        $query->where('s.serv_name', 'LIKE', '%Deworm%')
              ->orWhere('s.serv_type', 'LIKE', '%deworm%');
    })
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
        'vs.total_price',
        'vs.status',
        'vs.completed_at'
    )
    ->orderBy('v.visit_id', 'desc')
    ->get();

if ($dewormingVisits->isEmpty()) {
    echo "NO DEWORMING VISITS FOUND IN LAST 2 DAYS\n";
} else {
    foreach ($dewormingVisits as $visit) {
        echo "Visit ID: {$visit->visit_id}\n";
        echo "Date: {$visit->visit_date}\n";
        echo "Pet: {$visit->pet_name}\n";
        echo "Owner: {$visit->own_name}\n";
        echo "Service ID: {$visit->serv_id}\n";
        echo "Service Name: {$visit->serv_name}\n";
        echo "Service Price: ₱" . number_format($visit->serv_price, 2) . "\n";
        echo "Pivot Unit Price: ₱" . number_format($visit->unit_price ?? 0, 2) . "\n";
        echo "Pivot Total Price: ₱" . number_format($visit->total_price ?? 0, 2) . "\n";
        echo "Status: {$visit->status}\n";
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
        echo str_repeat("-", 100) . "\n";
    }
}

// 3. Check if there's a specific visit you're concerned about
echo "\n3. What deworming service did you perform?\n";
echo "Please tell me:\n";
echo "- Which visit ID has the wrong deworming?\n";
echo "- What deworming was performed? (provide the correct service name)\n";
echo "- What is currently showing in the billing?\n";

echo "\n=== END OF INVESTIGATION ===\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX DEWORMING SERVICE FOR ANY VISIT ===\n\n";

// Show all deworming services
echo "Available Deworming Services:\n";
echo str_repeat("-", 80) . "\n";

$dewormingServices = DB::table('tbl_serv')
    ->where(function($query) {
        $query->where('serv_name', 'LIKE', '%Deworm%')
              ->orWhere('serv_type', 'LIKE', '%deworm%');
    })
    ->select('serv_id', 'serv_name', 'serv_price')
    ->orderBy('serv_id')
    ->get();

foreach ($dewormingServices as $service) {
    echo "ID: {$service->serv_id} | {$service->serv_name} | ₱" . number_format($service->serv_price, 2) . "\n";
}

echo "\n";
echo "Which visit do you want to fix? Enter Visit ID: ";
$handle = fopen("php://stdin", "r");
$visitId = trim(fgets($handle));
fclose($handle);

if (!is_numeric($visitId)) {
    echo "Invalid visit ID. Exiting.\n";
    exit;
}

// Check current service
$currentService = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where('v.visit_id', $visitId)
    ->where(function($query) {
        $query->where('s.serv_name', 'LIKE', '%Deworm%')
              ->orWhere('s.serv_type', 'LIKE', '%deworm%');
    })
    ->select('v.*', 'p.pet_name', 'o.own_name', 'p.own_id', 'vs.serv_id', 's.serv_name', 's.serv_price')
    ->first();

if (!$currentService) {
    echo "No deworming service found for Visit ID {$visitId}. Exiting.\n";
    exit;
}

echo "\nCurrent Service for Visit {$visitId}:\n";
echo "Pet: {$currentService->pet_name}\n";
echo "Owner: {$currentService->own_name}\n";
echo "Current Service: ID {$currentService->serv_id} - {$currentService->serv_name} (₱" . number_format($currentService->serv_price, 2) . ")\n";

echo "\nWhich deworming service should it be? Enter Service ID: ";
$handle = fopen("php://stdin", "r");
$newServiceId = trim(fgets($handle));
fclose($handle);

if (!is_numeric($newServiceId)) {
    echo "Invalid service ID. Exiting.\n";
    exit;
}

$newService = $dewormingServices->firstWhere('serv_id', $newServiceId);

if (!$newService) {
    echo "Service ID {$newServiceId} not found. Exiting.\n";
    exit;
}

echo "\nYou selected: {$newService->serv_name} (₱" . number_format($newService->serv_price, 2) . ")\n";
echo "Confirm change? Type 'yes': ";
$handle = fopen("php://stdin", "r");
$confirm = trim(fgets($handle));
fclose($handle);

if (strtolower($confirm) !== 'yes') {
    echo "Operation cancelled.\n";
    exit;
}

try {
    DB::beginTransaction();
    
    // 1. Delete old service
    DB::table('tbl_visit_service')
        ->where('visit_id', $visitId)
        ->where('serv_id', $currentService->serv_id)
        ->delete();
    
    echo "\n✓ Removed Service ID {$currentService->serv_id} ({$currentService->serv_name})\n";
    
    // 2. Add new service
    DB::table('tbl_visit_service')->insert([
        'visit_id' => $visitId,
        'serv_id' => $newService->serv_id,
        'status' => 'completed',
        'completed_at' => now(),
        'quantity' => 1,
        'unit_price' => $newService->serv_price,
        'total_price' => $newService->serv_price,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✓ Added Service ID {$newService->serv_id} ({$newService->serv_name})\n";
    
    // 3. Delete old billing
    $oldBillingId = DB::table('tbl_bill')
        ->where('visit_id', $visitId)
        ->value('bill_id');
    
    if ($oldBillingId) {
        DB::table('tbl_bill')->where('bill_id', $oldBillingId)->delete();
        echo "✓ Deleted old billing (Bill ID: {$oldBillingId})\n";
    }
    
    // 4. Create new billing
    $newBillingId = DB::table('tbl_bill')->insertGetId([
        'visit_id' => $visitId,
        'owner_id' => $currentService->own_id,
        'bill_date' => now()->toDateString(),
        'total_amount' => $newService->serv_price,
        'paid_amount' => 0,
        'bill_status' => 'unpaid',
        'branch_id' => $currentService->branch_id ?? null,
    ]);
    
    echo "✓ Created new billing (Bill ID: {$newBillingId}) - ₱" . number_format($newService->serv_price, 2) . "\n";
    
    DB::commit();
    
    echo "\nSUCCESS!\n";
    echo "OLD: {$currentService->serv_name} (₱" . number_format($currentService->serv_price, 2) . ")\n";
    echo "NEW: {$newService->serv_name} (₱" . number_format($newService->serv_price, 2) . ")\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETED ===\n";

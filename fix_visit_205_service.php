<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX VACCINATION SERVICE MISMATCH ===\n\n";

echo "PROBLEM IDENTIFIED:\n";
echo "- Visit 205 is currently linked to Service ID 131 (Vaccination - Kennel Cough)\n";
echo "- But you want it to be Anti-Rabies vaccination\n";
echo "\n";

// Find Anti-Rabies services
echo "Available Anti-Rabies services:\n";
echo str_repeat("-", 80) . "\n";

$antiRabiesServices = DB::table('tbl_serv')
    ->where('serv_name', 'LIKE', '%Rabies%')
    ->select('serv_id', 'serv_name', 'serv_price')
    ->get();

if ($antiRabiesServices->isEmpty()) {
    echo "ERROR: No Anti-Rabies services found in tbl_serv!\n";
    exit;
}

foreach ($antiRabiesServices as $service) {
    echo "Service ID: {$service->serv_id} | {$service->serv_name} | ₱" . number_format($service->serv_price, 2) . "\n";
}

echo "\n";
echo "Which Anti-Rabies service do you want to use?\n";
echo "Enter the Service ID (e.g., 133 for 'Vaccination - Anti Rabies' or 292 for 'Anti-Rabies'): ";

$handle = fopen("php://stdin", "r");
$selectedServiceId = trim(fgets($handle));
fclose($handle);

if (!is_numeric($selectedServiceId)) {
    echo "Invalid input. Exiting.\n";
    exit;
}

$selectedService = $antiRabiesServices->firstWhere('serv_id', $selectedServiceId);

if (!$selectedService) {
    echo "Service ID {$selectedServiceId} not found in the list. Exiting.\n";
    exit;
}

echo "\n";
echo "You selected: {$selectedService->serv_name} (ID: {$selectedService->serv_id})\n";
echo "This will:\n";
echo "1. Remove Service ID 131 (Kennel Cough) from Visit 205\n";
echo "2. Add Service ID {$selectedService->serv_id} ({$selectedService->serv_name}) to Visit 205\n";
echo "3. Regenerate the billing with the correct amount\n";
echo "\n";
echo "Are you sure? Type 'yes' to proceed: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "Operation cancelled.\n";
    exit;
}

try {
    DB::beginTransaction();
    
    // 1. Delete old service link (Kennel Cough)
    $deleted = DB::table('tbl_visit_service')
        ->where('visit_id', 205)
        ->where('serv_id', 131)
        ->delete();
    
    echo "\n✓ Removed Service ID 131 (Kennel Cough) from Visit 205\n";
    
    // 2. Add new service link (Anti-Rabies)
    DB::table('tbl_visit_service')->insert([
        'visit_id' => 205,
        'serv_id' => $selectedService->serv_id,
        'status' => 'completed',
        'completed_at' => now(),
        'quantity' => 1,
        'unit_price' => $selectedService->serv_price,
        'total_price' => $selectedService->serv_price,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✓ Added Service ID {$selectedService->serv_id} ({$selectedService->serv_name}) to Visit 205\n";
    
    // 3. Delete old billing
    $oldBillingId = DB::table('tbl_bill')
        ->where('visit_id', 205)
        ->value('bill_id');
    
    if ($oldBillingId) {
        DB::table('tbl_bill')->where('bill_id', $oldBillingId)->delete();
        echo "✓ Deleted old billing (Bill ID: {$oldBillingId})\n";
    }
    
    // 4. Regenerate billing using GroupedBillingService
    $visit = DB::table('tbl_visit_record as v')
        ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
        ->where('v.visit_id', 205)
        ->select('v.*', 'p.own_id')
        ->first();
    
    if ($visit) {
        // Calculate total from services
        $services = DB::table('tbl_visit_service as vs')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->where('vs.visit_id', 205)
            ->select('vs.total_price', 's.serv_price', 'vs.unit_price')
            ->get();
        
        $totalAmount = 0;
        foreach ($services as $service) {
            if ($service->total_price > 0) {
                $totalAmount += $service->total_price;
            } elseif ($service->unit_price > 0) {
                $totalAmount += $service->unit_price;
            } else {
                $totalAmount += $service->serv_price;
            }
        }
        
        // Create new billing
        $newBillingId = DB::table('tbl_bill')->insertGetId([
            'visit_id' => 205,
            'own_id' => $visit->own_id,
            'bill_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'bill_status' => 'unpaid',
            'branch_id' => $visit->branch_id ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✓ Created new billing (Bill ID: {$newBillingId}) with total: ₱" . number_format($totalAmount, 2) . "\n";
    }
    
    DB::commit();
    
    echo "\n";
    echo "SUCCESS! Visit 205 has been updated:\n";
    echo "- Service: {$selectedService->serv_name}\n";
    echo "- Price: ₱" . number_format($selectedService->serv_price, 2) . "\n";
    echo "- Billing Status: unpaid\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIX COMPLETED ===\n";

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GroupedBillingService;
use App\Models\Visit;
use App\Models\Billing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TESTING AUTO-GENERATE BILLING FLOW ===\n\n";

$service = new GroupedBillingService();
$today = Carbon::today()->toDateString();

// Step 1: Check for completed visits without billing
echo "Step 1: Checking for completed visits...\n";
$visits = Visit::with(['pet.owner', 'billing'])
    ->whereDate('visit_date', $today)
    ->where('workflow_status', 'Completed')
    ->get();

$visitsWithoutBilling = $visits->filter(function($visit) {
    return $visit->billing === null;
});

echo "  Found {$visitsWithoutBilling->count()} visits without billing\n\n";

if ($visitsWithoutBilling->isEmpty()) {
    echo "❌ No visits to generate billing for.\n";
    exit;
}

// Step 2: Group by owner
echo "Step 2: Grouping visits by owner...\n";
$groupedByOwner = $visitsWithoutBilling->groupBy(function($visit) {
    return $visit->pet->owner->own_id;
});

echo "  Found {$groupedByOwner->count()} owner(s) with unbilled visits\n\n";

// Step 3: Generate billings
echo "Step 3: Generating billings...\n";
$generatedCount = 0;
$errors = [];

foreach ($groupedByOwner as $ownerId => $ownerVisits) {
    $owner = $ownerVisits->first()->pet->owner;
    echo "\n  Owner: {$owner->own_name} (ID: {$ownerId})\n";
    echo "  Pets: {$ownerVisits->count()}\n";
    
    try {
        if ($ownerVisits->count() === 1) {
            echo "  Action: Generating single billing...\n";
            $billing = $service->generateSingleBilling($ownerVisits->first()->visit_id);
            echo "  ✅ Created Bill ID: {$billing->bill_id} | Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
        } else {
            echo "  Action: Generating grouped billing...\n";
            $billing = $service->generateGroupedBilling($ownerVisits->pluck('visit_id')->toArray());
            echo "  ✅ Created Bill ID: {$billing->bill_id} | Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
        }
        $generatedCount++;
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $errors[] = "Owner ID {$ownerId}: " . $e->getMessage();
    }
}

echo "\n=== SUMMARY ===\n";
echo "Billings generated: {$generatedCount}\n";
echo "Errors: " . count($errors) . "\n";

if ($generatedCount > 0) {
    echo "\n✅ SUCCESS! Auto-generate is working!\n";
    
    // Show generated billings
    echo "\n=== Generated Billings ===\n";
    $newBillings = Billing::with('visit.pet.owner')
        ->whereDate('bill_date', $today)
        ->whereNotNull('visit_id')
        ->orderBy('bill_id', 'desc')
        ->limit($generatedCount)
        ->get();
    
    foreach ($newBillings as $billing) {
        echo "Bill #{$billing->bill_id}\n";
        echo "  Owner: {$billing->visit->pet->owner->own_name}\n";
        echo "  Pet: {$billing->visit->pet->pet_name}\n";
        echo "  Total: ₱" . number_format($billing->total_amount, 2) . "\n";
        echo "  Status: {$billing->bill_status}\n\n";
    }
}

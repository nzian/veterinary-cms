<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visit;
use App\Models\Billing;
use Illuminate\Support\Facades\DB;

echo "Testing auto-generate billing logic...\n\n";

// Simulate what the controller does
$activeBranchId = 1; // Assuming branch 1
$today = \Carbon\Carbon::today()->toDateString();

echo "Looking for visits on: " . $today . "\n";
echo "Active Branch ID: " . $activeBranchId . "\n\n";

// Get all completed visits for TODAY
$visits = Visit::with(['pet.owner', 'billing'])
    ->whereDate('visit_date', $today)
    ->where('workflow_status', 'Completed')
    ->get();

echo "Total completed visits today: " . $visits->count() . "\n\n";

// Filter out visits that already have billing
$visitsWithoutBilling = $visits->filter(function($visit) {
    return $visit->billing === null;
});

echo "Visits without billing: " . $visitsWithoutBilling->count() . "\n\n";

if ($visitsWithoutBilling->isEmpty()) {
    echo "✅ Result: No completed visits found without billing for today\n";
    echo "This is why you see the 'info' message.\n\n";
    
    echo "=== All visits status ===\n";
    foreach ($visits as $visit) {
        echo "Visit ID: " . $visit->visit_id . "\n";
        echo "  Pet: " . $visit->pet->pet_name . "\n";
        echo "  Owner: " . $visit->pet->owner->own_name . "\n";
        echo "  Workflow Status: " . $visit->workflow_status . "\n";
        echo "  Has Billing: " . ($visit->billing ? "YES (Bill ID: " . $visit->billing->bill_id . ")" : "NO") . "\n\n";
    }
} else {
    echo "Would generate billings for these visits:\n";
    foreach ($visitsWithoutBilling as $visit) {
        echo "  - Visit " . $visit->visit_id . " | Pet: " . $visit->pet->pet_name . " | Owner: " . $visit->pet->owner->own_name . "\n";
    }
}

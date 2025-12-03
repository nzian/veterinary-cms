<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$today = date('Y-m-d');
echo "Checking visits for: $today\n\n";

$visits = \App\Models\Visit::with(['pet.owner', 'billing'])
    ->whereDate('visit_date', $today)
    ->get();

echo "Total visits today: " . $visits->count() . "\n\n";

foreach($visits as $visit) {
    echo "Visit ID: {$visit->visit_id}\n";
    echo "  Pet: " . ($visit->pet->pet_name ?? 'N/A') . "\n";
    echo "  Owner: " . ($visit->pet->owner->own_name ?? 'N/A') . "\n";
    echo "  workflow_status: " . ($visit->workflow_status ?? 'null') . "\n";
    echo "  service_status: " . ($visit->service_status ?? 'null') . "\n";
    echo "  Has billing: " . ($visit->billing ? 'YES' : 'NO') . "\n";
    echo "  Billing ID: " . ($visit->billing->bill_id ?? 'N/A') . "\n";
    echo "\n";
}

// Check visits that should be eligible for billing
$eligibleVisits = \App\Models\Visit::with(['pet.owner', 'billing'])
    ->whereDate('visit_date', $today)
    ->where('workflow_status', 'Completed')
    ->get();

echo "\n=== Visits with workflow_status = 'Completed' ===\n";
echo "Count: " . $eligibleVisits->count() . "\n\n";

$withoutBilling = $eligibleVisits->filter(function($v) {
    return $v->billing === null;
});

echo "Visits without billing: " . $withoutBilling->count() . "\n\n";

foreach($withoutBilling as $visit) {
    echo "Visit ID: {$visit->visit_id} | Pet: " . ($visit->pet->pet_name ?? 'N/A') . " | Owner: " . ($visit->pet->owner->own_name ?? 'N/A') . "\n";
}

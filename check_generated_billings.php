<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Billing;
use Illuminate\Support\Facades\DB;

echo "Checking generated billings...\n\n";

$billings = Billing::with('visit.pet.owner')
    ->whereIn('visit_id', [146, 151])
    ->orderBy('bill_id', 'desc')
    ->get();

foreach ($billings as $billing) {
    echo "Bill ID: " . $billing->bill_id . "\n";
    echo "  Owner: " . $billing->visit->pet->owner->own_name . "\n";
    echo "  Pet: " . $billing->visit->pet->pet_name . "\n";
    echo "  Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "  Paid Amount: ₱" . number_format($billing->paid_amount, 2) . "\n";
    echo "  Status: " . $billing->bill_status . "\n";
    echo "  Date: " . $billing->bill_date . "\n\n";
}

echo "=== Summary ===\n";
echo "Total billings: " . $billings->count() . "\n";
echo "Total amount: ₱" . number_format($billings->sum('total_amount'), 2) . "\n";
echo "All are unpaid: " . ($billings->every(function($b) { return $b->bill_status === 'unpaid'; }) ? 'YES' : 'NO') . "\n";

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GroupedBillingService;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

echo "Testing billing generation for visit 146...\n\n";

$service = new GroupedBillingService();

try {
    $billing = $service->generateSingleBilling(146);
    
    echo "✅ SUCCESS! Billing generated:\n";
    echo "  Bill ID: " . $billing->bill_id . "\n";
    echo "  Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "  Paid Amount: ₱" . number_format($billing->paid_amount, 2) . "\n";
    echo "  Status: " . $billing->bill_status . "\n";
    echo "  Owner ID: " . $billing->owner_id . "\n";
    echo "  Billing Group ID: " . ($billing->billing_group_id ?? 'NULL') . "\n";
    echo "  Is Parent: " . ($billing->is_group_parent ? 'Yes' : 'No') . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

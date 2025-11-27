<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GroupedBillingService;
use App\Models\Visit;

echo "=== TESTING BILLING REGENERATION ===\n\n";

$service = new GroupedBillingService();

// Generate billing for visit 205 (Kennel Cough)
echo "Generating billing for Visit 205 (Kennel Cough)...\n";
try {
    $billing = $service->generateSingleBilling(205);
    echo "✅ SUCCESS!\n";
    echo "  Bill ID: {$billing->bill_id}\n";
    echo "  Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "  Status: {$billing->bill_status}\n";
    echo "  Owner ID: {$billing->owner_id}\n\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Generate billing for visit 206 (Deworming)
echo "Generating billing for Visit 206 (Deworming)...\n";
try {
    $billing = $service->generateSingleBilling(206);
    echo "✅ SUCCESS!\n";
    echo "  Bill ID: {$billing->bill_id}\n";
    echo "  Total Amount: ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "  Status: {$billing->bill_status}\n";
    echo "  Owner ID: {$billing->owner_id}\n\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== VERIFICATION ===\n";
$visit205 = Visit::with(['services', 'billing'])->find(205);
$visit206 = Visit::with(['services', 'billing'])->find(206);

echo "Visit 205:\n";
echo "  Service: " . $visit205->services->first()->serv_name . " (ID: " . $visit205->services->first()->serv_id . ")\n";
echo "  Service Price: ₱" . number_format($visit205->services->first()->serv_price, 2) . "\n";
if ($visit205->billing) {
    echo "  Billing Total: ₱" . number_format($visit205->billing->total_amount, 2) . "\n";
    echo "  Billing Status: " . $visit205->billing->bill_status . "\n";
}

echo "\nVisit 206:\n";
echo "  Service: " . $visit206->services->first()->serv_name . " (ID: " . $visit206->services->first()->serv_id . ")\n";
echo "  Service Price: ₱" . number_format($visit206->services->first()->serv_price, 2) . "\n";
if ($visit206->billing) {
    echo "  Billing Total: ₱" . number_format($visit206->billing->total_amount, 2) . "\n";
    echo "  Billing Status: " . $visit206->billing->bill_status . "\n";
}

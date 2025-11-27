<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Visit;
use App\Services\GroupedBillingService;
use Illuminate\Support\Facades\DB;

echo "=== TEST PRESCRIPTION BILLING CALCULATION ===\n\n";

// Get Visit 205
$visit = Visit::with('services', 'pet')->find(205);

if (!$visit) {
    echo "Visit 205 not found\n";
    exit;
}

echo "Visit 205 Details:\n";
echo str_repeat("-", 80) . "\n";
echo "Pet: {$visit->pet->pet_name}\n";
echo "Visit Date: {$visit->visit_date}\n\n";

// Check services
echo "Services:\n";
foreach($visit->services as $service) {
    $price = $service->pivot->total_price ?? $service->serv_price;
    echo "  - {$service->serv_name}: ₱" . number_format($price, 2) . "\n";
}

// Check prescriptions
echo "\nPrescriptions for this visit:\n";
$prescriptions = DB::table('tbl_prescription')
    ->where('pet_id', $visit->pet_id)
    ->whereDate('prescription_date', $visit->visit_date)
    ->get();

$prescriptionTotal = 0;
foreach($prescriptions as $rx) {
    echo "  Prescription ID: {$rx->prescription_id}\n";
    $medications = json_decode($rx->medication, true);
    if (is_array($medications)) {
        foreach($medications as $med) {
            echo "    - Product ID: {$med['product_id']}\n";
            echo "      Product Name: {$med['product_name']}\n";
            if(isset($med['instructions'])) echo "      Instructions: {$med['instructions']}\n";
            
            $product = DB::table('tbl_prod')->where('prod_id', $med['product_id'])->first();
            if ($product) {
                $quantity = $med['quantity'] ?? 1;
                $itemTotal = $product->prod_price * $quantity;
                echo "      Price: ₱" . number_format($product->prod_price, 2) . " x {$quantity} = ₱" . number_format($itemTotal, 2) . "\n";
                $prescriptionTotal += $itemTotal;
            }
        }
    }
}

echo "\n\nCalculation using GroupedBillingService:\n";
echo str_repeat("-", 80) . "\n";
$billingService = new GroupedBillingService();
$reflection = new ReflectionClass($billingService);
$method = $reflection->getMethod('calculateVisitTotal');
$method->setAccessible(true);
$calculatedTotal = $method->invoke($billingService, $visit);

echo "Calculated Total: ₱" . number_format($calculatedTotal, 2) . "\n";

// Check current billing
$currentBilling = DB::table('tbl_bill')->where('visit_id', 205)->first();
if ($currentBilling) {
    echo "Current Billing Total: ₱" . number_format($currentBilling->total_amount, 2) . "\n";
    
    if ($calculatedTotal != $currentBilling->total_amount) {
        echo "\n⚠️ MISMATCH DETECTED!\n";
        echo "Expected: ₱" . number_format($calculatedTotal, 2) . "\n";
        echo "Current:  ₱" . number_format($currentBilling->total_amount, 2) . "\n";
        echo "Difference: ₱" . number_format($calculatedTotal - $currentBilling->total_amount, 2) . "\n";
    } else {
        echo "\n✅ Billing matches calculated total\n";
    }
}

echo "\n=== END ===\n";

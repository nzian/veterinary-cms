<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Visit;
use App\Models\Product;

echo "Checking visit details for billing calculation...\n\n";

$visit = Visit::with(['services', 'pet.owner', 'user'])->find(146);

if (!$visit) {
    echo "Visit 146 not found!\n";
    exit;
}

echo "=== VISIT 146 DETAILS ===\n";
echo "Pet: " . $visit->pet->pet_name . "\n";
echo "Owner: " . $visit->pet->owner->own_name . "\n";
echo "Workflow Status: " . $visit->workflow_status . "\n";
echo "Services Count: " . $visit->services->count() . "\n\n";

echo "=== SERVICES ===\n";
$servicesTotal = 0;
foreach ($visit->services as $service) {
    $quantity = $service->pivot->quantity ?? 1;
    $unitPrice = $service->pivot->unit_price ?? $service->serv_price ?? 0;
    $totalPrice = $service->pivot->total_price ?? ($unitPrice * $quantity);
    
    echo "Service: " . $service->serv_name . "\n";
    echo "  - Base Price (serv_price): ₱" . number_format($service->serv_price ?? 0, 2) . "\n";
    echo "  - Pivot Unit Price: " . ($service->pivot->unit_price ? '₱' . number_format($service->pivot->unit_price, 2) : 'NULL') . "\n";
    echo "  - Pivot Quantity: " . $quantity . "\n";
    echo "  - Pivot Total Price: " . ($service->pivot->total_price ? '₱' . number_format($service->pivot->total_price, 2) : 'NULL') . "\n";
    echo "  - Calculated Total: ₱" . number_format($totalPrice, 2) . "\n\n";
    
    $servicesTotal += $totalPrice;
}

echo "SERVICES TOTAL: ₱" . number_format($servicesTotal, 2) . "\n\n";

// Check for products/medications linked to this visit
echo "=== CHECKING FOR MEDICATIONS/PRODUCTS ===\n";
$products = \DB::table('tbl_visit_service')
    ->join('tbl_service_product', 'tbl_visit_service.visit_service_id', '=', 'tbl_service_product.visit_service_id')
    ->join('tbl_product', 'tbl_service_product.prod_id', '=', 'tbl_product.prod_id')
    ->where('tbl_visit_service.visit_id', 146)
    ->select('tbl_product.prod_name', 'tbl_service_product.quantity', 'tbl_service_product.unit_price', 'tbl_service_product.total_price')
    ->get();

if ($products->count() > 0) {
    $productsTotal = 0;
    foreach ($products as $product) {
        echo "Product: " . $product->prod_name . "\n";
        echo "  - Quantity: " . $product->quantity . "\n";
        echo "  - Unit Price: ₱" . number_format($product->unit_price ?? 0, 2) . "\n";
        echo "  - Total: ₱" . number_format($product->total_price ?? 0, 2) . "\n\n";
        $productsTotal += ($product->total_price ?? 0);
    }
    echo "PRODUCTS TOTAL: ₱" . number_format($productsTotal, 2) . "\n\n";
    echo "GRAND TOTAL: ₱" . number_format($servicesTotal + $productsTotal, 2) . "\n";
} else {
    echo "No products/medications linked to this visit.\n";
    echo "GRAND TOTAL: ₱" . number_format($servicesTotal, 2) . "\n";
}

<?php

use App\Models\Product;
use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Stock Deduction Verification...\n";

DB::beginTransaction();

try {
    // 1. Create a test Product
    $product = Product::create([
        'prod_name' => 'Test Consumable ' . uniqid(),
        'prod_description' => 'Test Description',
        'prod_price' => 100,
        'prod_category' => 'Medicine',
        'prod_type' => 'Consumable',
        'prod_stocks' => 30, // Initial aggregate stock
        'prod_reorderlevel' => 5,
        'branch_id' => 1
    ]);

    echo "Created Product: {$product->prod_name} (ID: {$product->prod_id})\n";

    $user = \App\Models\User::first();
    $userId = $user ? $user->user_id : null;

    // 2. Create ProductStock batches
    // Batch A: Expiring soon (valid), Qty 10
    ProductStock::create([
        'stock_prod_id' => $product->prod_id,
        'batch' => 'BATCH-A',
        'quantity' => 10,
        'expire_date' => Carbon::now()->addDays(10),
        'created_by' => $userId
    ]);

    // Batch B: Expiring later (valid), Qty 10
    ProductStock::create([
        'stock_prod_id' => $product->prod_id,
        'batch' => 'BATCH-B',
        'quantity' => 10,
        'expire_date' => Carbon::now()->addDays(20),
        'created_by' => $userId
    ]);

    // Batch C: Expired, Qty 10
    ProductStock::create([
        'stock_prod_id' => $product->prod_id,
        'batch' => 'BATCH-C',
        'quantity' => 10,
        'expire_date' => Carbon::now()->subDays(1),
        'created_by' => $userId
    ]);

    echo "Created 3 Batches (10 each). Total Stock in Batches: 30. Expired: 10. Valid: 20.\n";

    // 3. Deduct 15 units
    $inventoryService = new InventoryService();
    echo "Deducting 15 units...\n";
    
    $inventoryService->deductFromInventory(
        $product->prod_id, 
        15, 
        'Test Transaction', 
        'adjustment'
    );

    // 4. Verify
    $batchA = ProductStock::where('stock_prod_id', $product->prod_id)->where('batch', 'BATCH-A')->first();
    $batchB = ProductStock::where('stock_prod_id', $product->prod_id)->where('batch', 'BATCH-B')->first();
    $batchC = ProductStock::where('stock_prod_id', $product->prod_id)->where('batch', 'BATCH-C')->first();
    $product->refresh();

    echo "Verification Results:\n";
    echo "Batch A (Expiring Soon) Quantity: {$batchA->quantity} (Expected: 0)\n";
    echo "Batch B (Expiring Later) Quantity: {$batchB->quantity} (Expected: 5)\n";
    echo "Batch C (Expired) Quantity: {$batchC->quantity} (Expected: 10)\n";
    echo "Product Aggregate Stock: {$product->prod_stocks} (Expected: 15)\n";

    if ($batchA->quantity == 0 && $batchB->quantity == 5 && $batchC->quantity == 10 && $product->prod_stocks == 15) {
        echo "✅ SUCCESS: Stock deduction logic verified!\n";
    } else {
        echo "❌ FAILURE: Stock deduction logic incorrect.\n";
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    DB::rollBack(); // Clean up
    echo "\nTest data rolled back.\n";
}

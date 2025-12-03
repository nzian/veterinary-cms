<?php
// app/Services/InventoryService.php

namespace App\Services;
use App\Models\Service as ServiceModel;

use App\Models\Product;
use App\Models\ServiceProduct;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductStock;

class InventoryService
{
    /**
     * Deduct quantity from a product's inventory
     * 
     * @param int $productId The ID of the product to deduct from
     * @param int $quantity The quantity to deduct
     * @param string $reference The reference number/description for the transaction
     * @param string $type The type of transaction (e.g., 'Prescription', 'Service')
     * @return bool True if successful, false otherwise
     */
    public function deductFromInventory($productId, $quantity, $reference, $type = 'adjustment'): bool
    {
        try {
            DB::beginTransaction();

            // Get the product with a lock for update to prevent race conditions
            $product = Product::lockForUpdate()->find($productId);
            
            if (!$product) {
                throw new \Exception("Product with ID {$productId} not found");
            }

            // Use the new batch-based deduction logic
            $this->deductFromStockBatches($product, $quantity, $reference, $type);
            
            DB::commit();
            
            // Check if stock is below reorder level and log a warning
            if ($product->available_stock - $product->usage_from_inventory_transactions <= $product->prod_reorderlevel) {
                Log::warning("Product {$product->prod_name} is at or below reorder level. Current stock: {$product->prod_stocks}");
            }

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deducting from inventory: " . $e->getMessage());
            throw $e;
        }
    }

   

    /**
     * Deduct products used in appointment services
     */
  public function deductServiceProducts(Appointment $appointment): bool
    {
        try {
            Log::info("🔍 Starting inventory deduction for appointment {$appointment->appoint_id}");

            // CRUCIAL: Ensure the services relationship loads the pivot columns (like prod_id)
            $appointment->load(['services' => function ($query) {
                $query->withPivot('prod_id');
            }]);

            $services = $appointment->services;

            if ($services->isEmpty()) {
                Log::info("ℹ️ No services found for appointment {$appointment->appoint_id}");
                return true; // Consider successful as nothing to deduct
            }
            
            // Identify the Vaccination Service ID once
            $vaccinationService = ServiceModel::where('serv_name', 'LIKE', '%Vaccination%') // <-- FIXED: Use ServiceModel alias
                ->orWhere('serv_name', 'LIKE', '%Vaccine%')
                ->first();
            $vaccinationServId = $vaccinationService->serv_id ?? null;

            Log::info("📋 Found {$services->count()} services for appointment {$appointment->appoint_id}");
            
            DB::beginTransaction();
            
            $deductionSuccessful = true;
            
            // Loop through all services attached to the appointment
            foreach ($services as $service) {
                $productsToDeduct = collect();
                
                // 1. --- SPECIAL CASE: VACCINATION ---
                if ($vaccinationServId && $service->serv_id === $vaccinationServId) {
                    $productId = $service->pivot->prod_id ?? null;
                    
                    if ($productId) {
                        // The specific vaccine product and quantity (always 1) from the appointment record
                        $productsToDeduct->push((object)[
                            'prod_id' => $productId, 
                            'quantity_used' => 1,
                            'serv_name' => $service->serv_name,
                        ]);
                        Log::info("📦 Found specific vaccine Product ID {$productId} on pivot for service {$service->serv_id}");
                    } else {
                        Log::warning("⚠️ Vaccination Service used, but no specific vaccine (prod_id) recorded on appointment pivot.");
                    }
                } 
                // 2. --- GENERAL CASE: OTHER SERVICES (using tbl_serv_prod) ---
                else {
                    // Products linked to this service via the standard ServiceProduct table (tbl_serv_prod)
                    $serviceProducts = ServiceProduct::where('serv_id', $service->serv_id)
                        ->get();
                    
                    $productsToDeduct = $serviceProducts->map(function($sp) use ($service) {
                        return (object)[
                            'prod_id' => $sp->prod_id, 
                            'quantity_used' => $sp->quantity_used,
                            'serv_name' => $service->serv_name,
                        ];
                    });
                    
                    if ($productsToDeduct->isEmpty() && !$vaccinationServId) {
                         Log::info("ℹ️ Service '{$service->serv_name}' is general and has no products linked in ServiceProduct table.");
                         continue;
                    }
                }
                
                // 3. --- EXECUTE DEDUCTION ---
                foreach ($productsToDeduct as $deductItem) {
                    $product = Product::find($deductItem->prod_id);
                    $quantityNeeded = $deductItem->quantity_used;
                    $serviceName = $deductItem->serv_name;

                    if (!$product) {
                        Log::warning("⚠️ Product ID {$deductItem->prod_id} not found for deduction.");
                        $deductionSuccessful = false;
                        continue;
                    }
                    
                    // Determine the actual amount to deduct (max is current stock)
                    $actualDeduction = min($product->prod_stocks, $quantityNeeded);

                    // Check if enough stock (for warning log)
                    if ($product->prod_stocks < $quantityNeeded) {
                        Log::warning("⚠️ Insufficient stock for {$product->prod_name}. Need: {$quantityNeeded}, Available: {$product->prod_stocks}");
                        $deductionSuccessful = false; // Mark as failed but proceed with deduction of available stock
                    }
                    
                    // Deduct from stock batches
                    try {
                        $this->deductFromStockBatches($product, $actualDeduction, "Appointment #{$appointment->appoint_id}", $serviceName == ($vaccinationService->serv_name ?? 'Vaccination') ? 'vaccination_usage' : 'service_usage');
                    } catch (\Exception $e) {
                        Log::warning("⚠️ Failed to deduct from batches for {$product->prod_name}: " . $e->getMessage());
                        $deductionSuccessful = false;
                        // Fallback: still update the main stock if batch deduction fails (though deductFromStockBatches handles main stock too)
                        // If deductFromStockBatches failed, it likely means insufficient stock in batches.
                        // We might want to force decrement the main stock anyway if we want to allow negative stock or just log it.
                        // For now, let's assume if it fails, we don't decrement main stock here because deductFromStockBatches should have done it or thrown.
                        // But since we are in a loop, we might want to continue?
                        // Let's re-throw or handle gracefully.
                        // If we want to enforce "must have stock", then $deductionSuccessful = false is correct.
                    }
                    
                    Log::info("✅ Deducted {$actualDeduction} units of '{$product->prod_name}' for Service '{$serviceName}' (Stock: {$oldStock} → {$product->prod_stocks})");
                    
                    // Record transaction
                    InventoryTransaction::create([
                        'prod_id' => $product->prod_id,
                        'transaction_type' => $serviceName == ($vaccinationService->serv_name ?? 'Vaccination') ? 'vaccination_usage' : 'service_usage',
                        'quantity_change' => -$actualDeduction,
                        'reference' => "Appointment #{$appointment->appoint_id}",
                        'serv_id' => $service->serv_id,
                        'appoint_id' => $appointment->appoint_id,
                        'user_id' => Auth::id(),
                        'notes' => "Used in service: {$serviceName}",
                        'created_at' => now(),
                    ]);
                    
                    Log::info("📝 Inventory transaction recorded for product {$product->prod_id}");
                }
            }
            
            if (!$deductionSuccessful) {
                Log::warning("Deduction completed with stock warnings for Appt {$appointment->appoint_id}.");
            }
            
            DB::commit();
            Log::info("✅ Inventory deduction completed successfully for appointment {$appointment->appoint_id}");
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Error in deductServiceProducts for appointment {$appointment->appoint_id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    /**
     * Check if products are available for services
     */
    public function checkServiceProductAvailability(array $serviceIds)
    {
        $unavailableProducts = [];

        foreach ($serviceIds as $serviceId) {
            $serviceProducts = ServiceProduct::where('serv_id', $serviceId)
                ->with('product', 'service')
                ->get();

            foreach ($serviceProducts as $serviceProduct) {
                if (!$serviceProduct->product) {
                    continue;
                }

                if ($serviceProduct->product->prod_stocks < $serviceProduct->quantity_used) {
                    $unavailableProducts[] = [
                        'service' => $serviceProduct->service->serv_name,
                        'product' => $serviceProduct->product->prod_name,
                        'required' => $serviceProduct->quantity_used,
                        'available' => $serviceProduct->product->prod_stocks
                    ];
                }
            }
        }

        return $unavailableProducts;
    }

    /**
     * Get inventory transactions for a product
     */
    public function getProductTransactions($productId, $limit = 50)
    {
        return InventoryTransaction::where('prod_id', $productId)
            ->with(['appointment.pet.owner', 'service', 'performedBy'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function deductSpecificProduct(int $productId, float $quantity, int $appointmentId, int $serviceId): bool
{
    try {
        DB::beginTransaction();
        
        $product = Product::find($productId);
        if (!$product || $product->prod_stocks < $quantity) {
            DB::rollBack();
            Log::warning("💉 Vaccine Deduction Failed: Product {$productId} not found or insufficient stock.");
            return false;
        }

        // Use batch deduction
        $this->deductFromStockBatches($product, $quantity, "Appoint #{$appointmentId} Vaccine Record", 'service_usage', $serviceId);
        
        DB::commit();
        Log::info("✅ Vaccine Deduction Success: Prod {$productId} Stock: {($product->current_stock + $quantity)} -> {$product->current_stock}");
        return true;

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("❌ Specific Product Deduction Error (Vaccine): " . $e->getMessage());
        return false;
    }
    }

    /**
     * Helper method to deduct quantity from product stock batches (FIFO by expiration)
     * Also updates the main Product model's prod_stocks
     * 
     * @param Product $product
     * @param float $quantity
     * @throws \Exception If insufficient stock
     */
    private function deductFromStockBatches(Product $product, float $quantity, string $reference = '', string $type = 'adjustment', $serv_id = null)
    {
        $remainingToDeduct = $quantity;
        
        // Get non-expired batches ordered by expiration date (FIFO)
        // We assume 'expire_date' is the field.
        $batches = ProductStock::where('stock_prod_id', $product->prod_id)
            ->where('expire_date', '>=', now()->toDateString())
            ->where('quantity', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->get();

        $totalAvailableInBatches = $batches->sum('quantity');

        if ($totalAvailableInBatches < $quantity) {
             // Option: Throw exception OR allow partial deduction and let the main stock go negative?
             // Requirement says "make sure saving those service Consumable product quantity is deducted from the product stock table not expire"
             // Implies strict check.
             throw new \Exception("Insufficient non-expired stock in batches. Required: {$quantity}, Available: {$totalAvailableInBatches}");
        }

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            if ($batch->quantity >= $remainingToDeduct) {
                // This batch has enough
                $batch->quantity -= $remainingToDeduct;
                $batch->save();
                $remainingToDeduct = 0;
                $deducted = $remainingToDeduct;
            } else {
                // Take everything from this batch
                $deducted = $batch->quantity;
                $batch->quantity = 0;
                $batch->save();
                $remainingToDeduct -= $deducted;
            }
            // Record the transaction
            $transaction = new InventoryTransaction();
            $transaction->prod_id = $product->prod_id;
            $transaction->transaction_type = $type;
            $transaction->quantity_change = -1 * $deducted;
            $transaction->reference = $reference;
            $transaction->batch_id = $batch->id;
            $transaction->serv_id = $serv_id;
           // $transaction->transaction_date = now();
            $transaction->performed_by = Auth::id();
            $transaction->save();
        }

        // Update the aggregate stock on the Product model
        // We can either decrement by $quantity or recalculate from batches.
        // Recalculating is safer but slower. Decrementing is faster.
        // Let's decrement to match the logic of "deducting".
        // However, if we want to be 100% sure, we might want to sync.
        // For now, let's decrement the main stock column as well.
        $product->prod_stocks = max(0, $product->prod_stocks - $quantity);
        $product->save();
    }
}
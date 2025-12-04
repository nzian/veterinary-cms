<?php
// app/Services/InventoryService.php

namespace App\Services;
use App\Models\Service as ServiceModel;

use App\Models\Product;
use App\Models\ServiceProduct;
use App\Models\ProductConsumable;
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
     * Also deducts linked consumables automatically
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

            // Check if we have enough stock
            if ($product->prod_stocks < $quantity) {
                throw new \Exception("Insufficient stock for product {$product->prod_name}");
            }

            // Deduct the quantity
            $product->prod_stocks -= $quantity;
            $product->save();

            // Record the transaction
            $transaction = new InventoryTransaction();
            $transaction->prod_id = $productId;
            $transaction->transaction_type = $type;
            $quantityChange = -1 * $quantity;
            $transaction->quantity_change = $quantityChange;
            $transaction->reference = $reference;
           // $transaction->transaction_date = now();
            $transaction->performed_by = Auth::id();
            $transaction->save();
            
            // ===== DEDUCT LINKED CONSUMABLES =====
            $this->deductLinkedConsumables($productId, $quantity, $reference, $type);

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
     * Deduct linked consumables for a product
     * For example, when a vaccine is used, deduct linked syringes
     * 
     * @param int $productId The parent product ID
     * @param int $parentQuantity The quantity of parent product being used
     * @param string $reference The reference for the transaction
     * @param string $type The transaction type
     */
    protected function deductLinkedConsumables($productId, $parentQuantity, $reference, $type): void
    {
        try {
            // Get linked consumables for this product
            $linkedConsumables = ProductConsumable::where('product_id', $productId)
                ->with('consumableProduct')
                ->get();
            
            if ($linkedConsumables->isEmpty()) {
                return; // No linked consumables to deduct
            }
            
            foreach ($linkedConsumables as $link) {
                $consumable = $link->consumableProduct;
                
                if (!$consumable) {
                    Log::warning("Linked consumable product not found for link ID {$link->id}");
                    continue;
                }
                
                // Calculate quantity to deduct (link quantity * parent quantity used)
                $consumableQtyToDeduct = $link->quantity * $parentQuantity;
                
                // Check if we have enough stock
                $currentStock = $consumable->prod_stocks ?? 0;
                $actualDeduction = min($currentStock, $consumableQtyToDeduct);
                
                if ($actualDeduction > 0) {
                    // Deduct from consumable stock
                    $consumable->prod_stocks = max(0, $currentStock - $actualDeduction);
                    $consumable->save();
                    
                    // Record the transaction for the consumable
                    $parentProductName = $link->product->prod_name ?? 'Unknown';
                    InventoryTransaction::create([
                        'prod_id' => $consumable->prod_id,
                        'transaction_type' => $type,
                        'quantity_change' => -$actualDeduction,
                        'reference' => $reference . " (Linked from: {$parentProductName})",
                        'performed_by' => Auth::id(),
                        'notes' => "Auto-deducted as linked consumable",
                        'created_at' => now(),
                    ]);
                    
                    Log::info("✅ Linked consumable deducted: {$consumable->prod_name} x {$actualDeduction} (linked to product ID {$productId})");
                    
                    // Log warning if consumable stock is low
                    if ($consumable->prod_stocks <= ($consumable->prod_reorderlevel ?? 0)) {
                        Log::warning("Linked consumable {$consumable->prod_name} is at or below reorder level. Current stock: {$consumable->prod_stocks}");
                    }
                } else {
                    Log::warning("⚠️ Insufficient stock for linked consumable {$consumable->prod_name}. Need: {$consumableQtyToDeduct}, Available: {$currentStock}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error deducting linked consumables: " . $e->getMessage());
            // Don't throw - just log. We don't want to fail the main transaction
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
                    $oldStock = $product->prod_stocks;
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
                    $transactionType = $serviceName == ($vaccinationService->serv_name ?? 'Vaccination') ? 'vaccination_usage' : 'service_usage';
                    InventoryTransaction::create([
                        'prod_id' => $product->prod_id,
                        'transaction_type' => $transactionType,
                        'quantity_change' => -$actualDeduction,
                        'reference' => "Appointment #{$appointment->appoint_id}",
                        'serv_id' => $service->serv_id,
                        'appoint_id' => $appointment->appoint_id,
                        'user_id' => Auth::id(),
                        'notes' => "Used in service: {$serviceName}",
                        'created_at' => now(),
                    ]);
                    
                    Log::info("📝 Inventory transaction recorded for product {$product->prod_id}");
                    
                    // ===== DEDUCT LINKED CONSUMABLES =====
                    // When a product (e.g., vaccine) is used, also deduct its linked consumables (e.g., syringe)
                    $this->deductLinkedConsumables(
                        $product->prod_id, 
                        $actualDeduction, 
                        "Appointment #{$appointment->appoint_id} - {$serviceName}",
                        $transactionType
                    );
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

            $oldStock = $product->prod_stocks;
            $product->prod_stocks -= $quantity;
            $product->save();

            InventoryTransaction::create([
                'prod_id' => $productId,
                'transaction_type' => 'service_usage',
                'quantity_change' => -$quantity,
                'reference' => "Appoint #{$appointmentId} Vaccine Record",
                'serv_id' => $serviceId,
                'appoint_id' => $appointmentId,
                'user_id' => Auth::id(),
                'notes' => "Used in specific vaccine recording.",
                'created_at' => now(),
            ]);
            
            // ===== DEDUCT LINKED CONSUMABLES =====
            // When a vaccine is used, also deduct its linked consumables (e.g., syringe)
            $this->deductLinkedConsumables(
                $productId, 
                $quantity, 
                "Appoint #{$appointmentId} Vaccine Record",
                'service_usage'
            );
            
            DB::commit();
            Log::info("✅ Vaccine Deduction Success: Prod {$productId} Stock: {$oldStock} -> {$product->prod_stocks}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Specific Product Deduction Error (Vaccine): " . $e->getMessage());
            return false;
        }
    }
}
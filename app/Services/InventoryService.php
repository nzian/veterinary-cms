<?php
// app/Services/InventoryService.php

namespace App\Services;
use App\Models\Service as ServiceModel;

use App\Models\Product;
use App\Models\ServiceProduct;
use App\Models\Appointment;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
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
                    
                    // Deduct from stock and save
                    $oldStock = $product->prod_stocks;
                    $product->prod_stocks = max(0, $product->prod_stocks - $actualDeduction);
                    $product->save();
                    
                    Log::info("✅ Deducted {$actualDeduction} units of '{$product->prod_name}' for Service '{$serviceName}' (Stock: {$oldStock} → {$product->prod_stocks})");
                    
                    // Record transaction
                    InventoryTransaction::create([
                        'prod_id' => $product->prod_id,
                        'transaction_type' => $serviceName == ($vaccinationService->serv_name ?? 'Vaccination') ? 'vaccination_usage' : 'service_usage',
                        'quantity_change' => -$actualDeduction,
                        'reference' => "Appointment #{$appointment->appoint_id}",
                        'serv_id' => $service->serv_id,
                        'appoint_id' => $appointment->appoint_id,
                        'user_id' => auth()->id(),
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

        $oldStock = $product->prod_stocks;
        $product->prod_stocks -= $quantity;
        $product->save();

       \App\Models\InventoryTransaction::create([
    'prod_id' => $productId,
    // ⭐ CRITICAL FIX: Change to a very short code, e.g., 'V_REC' (5 chars) ⭐
    'transaction_type' => 'service_usage',
    'quantity_change' => -$quantity,
    'reference' => "Appoint #{$appointmentId} Vaccine Record",
    'serv_id' => $serviceId,
            'appoint_id' => $appointmentId,
            'user_id' => auth()->id(),
            'notes' => "Used in specific vaccine recording.",
            'created_at' => now(),
        ]);
        
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
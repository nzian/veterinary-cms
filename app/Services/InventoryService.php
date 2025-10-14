<?php
// app/Services/InventoryService.php

namespace App\Services;

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
    public function deductServiceProducts($appointment)
{
    try {
        Log::info("🔍 Starting inventory deduction for appointment {$appointment->appoint_id}");
        
        // Get all services from the appointment
        $services = $appointment->services;
        
        if ($services->isEmpty()) {
            Log::info("ℹ️ No services found for appointment {$appointment->appoint_id}");
            return false;
        }
        
        Log::info("📋 Found {$services->count()} services for appointment {$appointment->appoint_id}");
        
        DB::beginTransaction();
        
        foreach ($services as $service) {
            // Get products linked to this service
            $serviceProducts = ServiceProduct::where('serv_id', $service->serv_id)
                ->with('product')
                ->get();
            
            if ($serviceProducts->isEmpty()) {
                Log::info("ℹ️ No products linked to service {$service->serv_id} ({$service->serv_name})");
                continue;
            }
            
            Log::info("📦 Service '{$service->serv_name}' has {$serviceProducts->count()} products");
            
            foreach ($serviceProducts as $serviceProduct) {
                $product = $serviceProduct->product;
                $quantityNeeded = $serviceProduct->quantity_used;
                
                if (!$product) {
                    Log::warning("⚠️ Product not found for service_product ID {$serviceProduct->id}");
                    continue;
                }
                
                // Check if enough stock
                if ($product->prod_stocks < $quantityNeeded) {
                    Log::warning("⚠️ Insufficient stock for {$product->prod_name}. Need: {$quantityNeeded}, Available: {$product->prod_stocks}");
                    // Continue anyway to deduct what we can
                }
                
                // Deduct from stock
                $oldStock = $product->prod_stocks;
                $product->prod_stocks = max(0, $product->prod_stocks - $quantityNeeded);
                $product->save();
                
                Log::info("✅ Deducted {$quantityNeeded} units of '{$product->prod_name}' (Stock: {$oldStock} → {$product->prod_stocks})");
                
                // Record transaction
                InventoryTransaction::create([
                    'prod_id' => $product->prod_id,
                    'transaction_type' => 'service_usage',
                    'quantity_change' => -$quantityNeeded,
                    'reference' => "Appointment #{$appointment->appoint_id}",
                    'serv_id' => $service->serv_id,
                    'appoint_id' => $appointment->appoint_id,
                    'user_id' => auth()->id(),
                    'notes' => "Used in service: {$service->serv_name}",
                    'created_at' => now(),
                ]);
                
                Log::info("📝 Inventory transaction recorded for product {$product->prod_id}");
            }
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
}
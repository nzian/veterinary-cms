<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Visit;
use App\Models\Service;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Added for robust error logging

class VisitBillingService
{
    /**
     * Create a billing record for the given visit if not already created.
     */
    public function createFromVisit(Visit $visit): ?Billing
{
    // Check if billing already exists by querying directly (not using relationship cache)
    $existingBilling = DB::table('tbl_bill')
        ->where('visit_id', $visit->visit_id)
        ->first();
    
    if ($existingBilling) {
        return Billing::find($existingBilling->bill_id);
    }

    try {
        return DB::transaction(function () use ($visit) {
            
            // Build billing data
            $billingData = [
                'bill_date' => $visit->visit_date ? Carbon::parse($visit->visit_date)->toDateString() : Carbon::now()->toDateString(),
                'visit_id' => $visit->visit_id,
                'bill_status' => 'Pending',
            ];
            
            // Only add branch_id if column exists
            $branchId = optional($visit->user)->branch_id ?? optional(Auth::user())->branch_id;
            if (Schema::hasColumn('tbl_bill', 'branch_id') && $branchId) {
                $billingData['branch_id'] = $branchId;
            }
            
            $bill = Billing::create($billingData);

            Log::info("Billing record created: Bill ID {$bill->bill_id} for Visit ID {$visit->visit_id}");

        $serviceLines = $this->buildServiceLines($visit);
        Log::info("Service lines built for visit {$visit->visit_id}: " . count($serviceLines) . " lines");
        $createdOrderIds = [];
        
        // 2) Add service line(s)
        foreach ($serviceLines as $line) {
            // If no prod_id, create or find a product for this service
            $prodId = $line['prod_id'];
            if (empty($prodId)) {
                $prodId = $this->createOrFindServiceProduct($line['service_name'], $line['price'], $visit);
            }
            
            // Skip if we still can't get a product ID (shouldn't happen, but safety check)
            if (empty($prodId)) {
                Log::warning("Billing skipped service line - could not create/find product for service: " . ($line['service_name'] ?? 'N/A'));
                continue;
            }
            
            $lineTotal = $line['price'] * $line['quantity']; 

            // Build order data - only include ord_price if column exists
            $orderData = [
                'ord_quantity' => $line['quantity'],
                'ord_date' => Carbon::now(),
                'user_id' => $visit->user_id,
                'prod_id' => $prodId,
                'ord_total' => $lineTotal,
                'own_id' => $this->resolveOwnerId($visit),
                'bill_id' => $bill->bill_id,
            ];
            
            // Only add ord_price if the column exists
            if (Schema::hasColumn('tbl_ord', 'ord_price')) {
                $orderData['ord_price'] = $line['price'];
            }

            try {
                $ordId = DB::table('tbl_ord')->insertGetId($orderData, 'ord_id');
                if ($ordId) { 
                    $createdOrderIds[] = $ordId;
                    Log::info("Order created: Order ID {$ordId} for service {$line['service_name']}, Total: {$lineTotal}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to create order for service {$line['service_name']}: " . $e->getMessage());
                throw $e; // Re-throw to rollback transaction
            }
        }
            // 3) Add prescription lines
            $prescriptionLines = $this->buildPrescriptionLines($visit);
            foreach ($prescriptionLines as $pline) {
                if (empty($pline['prod_id'])) { continue; }
                
                $plineTotal = $pline['price'] * $pline['quantity'];

                // Build order data - only include ord_price if column exists
                $orderData = [
                    'ord_quantity' => $pline['quantity'],
                    'ord_date' => Carbon::now(),
                    'user_id' => $visit->user_id,
                    'prod_id' => $pline['prod_id'],
                    'ord_total' => $plineTotal,
                    'own_id' => $this->resolveOwnerId($visit),
                    'bill_id' => $bill->bill_id,
                ];
                
                // Only add ord_price if the column exists
                if (Schema::hasColumn('tbl_ord', 'ord_price')) {
                    $orderData['ord_price'] = $pline['price'];
                }

                try {
                    $ordId = DB::table('tbl_ord')->insertGetId($orderData, 'ord_id');
                    if ($ordId) { $createdOrderIds[] = $ordId; }
                } catch (\Throwable $e) {
                    Log::error("Failed to create order for prescription: " . $e->getMessage());
                    // Continue with other orders
                }
            }

            // 4) Update bill header
            if (!empty($createdOrderIds)) {
                // Only update ord_id if column exists
                if (Schema::hasColumn('tbl_bill', 'ord_id')) {
                    $bill->ord_id = $createdOrderIds[0];
                    $bill->save();
                }
            } else {
                // Log warning if no orders were created
                Log::warning("Billing created for visit {$visit->visit_id} but no orders were created. Service lines: " . count($serviceLines));
                Log::warning("This might indicate that services don't have products or product creation failed.");
            }

            Log::info("Billing created successfully for visit {$visit->visit_id}. Bill ID: {$bill->bill_id}, Orders created: " . count($createdOrderIds));

            // Always return the bill, even if no orders were created
            return $bill->fresh(['orders']);
        });
    } catch (\Throwable $e) {
        Log::error("CRITICAL: Billing creation transaction failed for visit {$visit->visit_id}: " . $e->getMessage());
        Log::error("Stack trace: " . $e->getTraceAsString());
        throw $e; // Re-throw to let caller handle
    }
    }

    /**
     * Builds line items for services using pivot data (for boarding duration).
     */
    private function buildServiceLines(Visit $visit): array
{
    $lines = [];
    
    try {
        // Refresh the visit to ensure we have latest data
        $visit->refresh();
        
        // Load services with pivot data
        $services = $visit->services()->get();
        
        Log::info("Found " . $services->count() . " services for visit {$visit->visit_id}");
    } catch (\Throwable $e) {
        Log::error("Error loading services for visit {$visit->visit_id}: " . $e->getMessage());
        $services = collect();
    }

    if ($services->isNotEmpty()) {
        foreach ($services as $s) {
            // FIX: Prioritize reading the pre-calculated total from the pivot table if available
            $quantity = $s->pivot->quantity ?? 1; 
            $unitPrice = $s->pivot->unit_price ?? $s->serv_price ?? 0;
            
            // Ensure we have a valid price
            if ($unitPrice <= 0) {
                Log::warning("Service {$s->serv_name} has invalid price: {$unitPrice}. Using service price from table.");
                $unitPrice = $s->serv_price ?? 0;
            }
            
            // Fallback price logic is robust. For boarding, this uses (Days * Daily Rate) as saved in the controller.
            
            $lines[] = [
                'service_name' => $s->serv_name ?? 'Service N/A',
                'prod_id' => $this->resolveServiceProductId($s->serv_id),
                'price' => $unitPrice, // Unit Price (Daily Rate)
                'quantity' => $quantity, // Quantity (Total Days)
            ];
            
            Log::info("Added service line: {$s->serv_name}, Price: {$unitPrice}, Quantity: {$quantity}, Prod ID: " . ($lines[count($lines)-1]['prod_id'] ?? 'null'));
        }
        return $lines; 
    }
    
    Log::warning("No services found for visit {$visit->visit_id} when building service lines");
        
        // 2. Fallback: map visit.service_type to a Service by type or name
        $stype = trim((string)($visit->service_type ?? ''));
        if ($stype !== '') {
            $service = Service::where('serv_type', $stype)
                ->orWhere('serv_name', 'like', "%$stype%")
                ->first();
            if ($service) {
                $lines[] = [
                    'service_name' => $service->serv_name,
                    'prod_id' => $this->resolveServiceProductId($service->serv_id),
                    'price' => $service->serv_price ?? 0,
                    'quantity' => 1,
                ];
            }
        }
        return $lines;
    }

    /**
     * Finds the corresponding Product ID for a Service, safely handles missing product.
     */
    private function resolveServiceProductId(int $servId): ?int
    {
        $service = Service::find($servId);
        if (!$service) return null;
        
        $prod = Product::where('prod_name', 'like', "%{$service->serv_name}%")->first();
        
        // 🛑 FIX: Safely check if a product was found
        return $prod->prod_id ?? null;
    }

    /**
     * Builds line items for prescriptions.
     */
    private function buildPrescriptionLines(Visit $visit): array
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_prescription')) {
            return [];
        }
        $date = $visit->visit_date ? Carbon::parse($visit->visit_date)->toDateString() : null;
        $prescriptions = DB::table('tbl_prescription')
            ->where('pet_id', $visit->pet_id)
            ->when($date, function ($q) use ($date) {
                $q->whereDate('prescription_date', $date);
            })
            ->orderBy('prescription_date', 'desc')
            ->limit(5)
            ->get();

        $lines = [];
        foreach ($prescriptions as $pr) {
            $meds = json_decode($pr->medication ?? '[]', true);
            if (!is_array($meds)) continue;
            
            foreach ($meds as $m) {
                $name = $m['name'] ?? null;
                $qty = isset($m['quantity']) ? (float)$m['quantity'] : 1;
                if (!$name) continue;
                
                $prod = Product::where('prod_name', 'like', "%$name%")->first();
                
                if ($prod) {
                    $price = $prod->prod_price ?? 0;
                    $lines[] = [
                        'prod_id' => $prod->prod_id,
                        'price' => $price,
                        'quantity' => max(1, $qty),
                    ];
                }
            }
        }
        return $lines;
    }

    /**
     * Resolves the owner ID for the order record.
     */
    private function resolveOwnerId(Visit $visit): ?int
    {
        try {
            // Assuming $visit->pet is related to the owner
            return $visit->pet->own_id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Create or find a product for a service if it doesn't exist
     */
    private function createOrFindServiceProduct(string $serviceName, float $price, Visit $visit): ?int
    {
        try {
            // First, try to find existing product with matching name (case-insensitive)
            $product = Product::where(DB::raw('LOWER(prod_name)'), strtolower($serviceName))
                ->where('prod_category', 'Service')
                ->first();
            
            if ($product) {
                return $product->prod_id;
            }
            
            // If not found, create a new product for this service
            $branchId = optional($visit->user)->branch_id ?? optional(Auth::user())->branch_id;
            
            $productData = [
                'prod_name' => $serviceName,
                'prod_category' => 'Service',
                'prod_price' => $price,
                'prod_stocks' => 0, // Services don't have stock
                'branch_id' => $branchId,
                'prod_description' => "Service: {$serviceName}",
            ];
            
            // Only add prod_status if the column exists
            if (Schema::hasColumn('tbl_prod', 'prod_status')) {
                $productData['prod_status'] = 'active';
            }
            
            $product = Product::create($productData);
            
            Log::info("Created product for service: {$serviceName} (Product ID: {$product->prod_id})");
            
            return $product->prod_id;
        } catch (\Throwable $e) {
            Log::error("Failed to create/find product for service {$serviceName}: " . $e->getMessage());
            return null;
        }
    }
}
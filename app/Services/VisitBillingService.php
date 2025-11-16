<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Visit;
use App\Models\Service;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
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
    // If already has billing, skip
    if ($visit->billing) {
        return $visit->billing;
    }

    return DB::transaction(function () use ($visit) {
        
        $bill = Billing::create([
            'bill_date' => $visit->visit_date ? Carbon::parse($visit->visit_date)->toDateString() : Carbon::now()->toDateString(),
            'visit_id' => $visit->visit_id,
            'bill_status' => 'Pending',
            'branch_id' => optional($visit->user)->branch_id ?? optional(Auth::user())->branch_id,
        ]);

        $serviceLines = $this->buildServiceLines($visit);
        $createdOrderIds = [];
        
        // 2) Add service line(s)
        foreach ($serviceLines as $line) {
            if (empty($line['prod_id'])) { 
                // Use Log::warning for clarity if prod_id is missing
                Log::warning("Billing skipped service line due to missing prod_id for service: " . ($line['service_name'] ?? 'N/A'));
                continue; 
            }
            
            $lineTotal = $line['price'] * $line['quantity']; 

            $ordId = DB::table('tbl_ord')->insertGetId([
                'ord_quantity' => $line['quantity'], // Correct quantity (days)
                'ord_date' => Carbon::now(),
                'user_id' => $visit->user_id,
                'prod_id' => $line['prod_id'],
                'ord_price' => $line['price'],     // Correct unit price (daily rate)
                'ord_total' => $lineTotal,           // Correct total (Days * Rate)
                'own_id' => $this->resolveOwnerId($visit),
                'bill_id' => $bill->bill_id,
            ], 'ord_id');
            if ($ordId) { $createdOrderIds[] = $ordId; }
        }
            // 3) Add prescription lines
            $prescriptionLines = $this->buildPrescriptionLines($visit);
            foreach ($prescriptionLines as $pline) {
                if (empty($pline['prod_id'])) { continue; }
                
                $plineTotal = $pline['price'] * $pline['quantity'];

                $ordId = DB::table('tbl_ord')->insertGetId([
                    'ord_quantity' => $pline['quantity'],
                    'ord_date' => Carbon::now(),
                    'user_id' => $visit->user_id,
                    'prod_id' => $pline['prod_id'],
                    'ord_price' => $pline['price'],
                    'ord_total' => $plineTotal,
                    'own_id' => $this->resolveOwnerId($visit),
                    'bill_id' => $bill->bill_id,
                ], 'ord_id');
                if ($ordId) { $createdOrderIds[] = $ordId; }
            }

            // 4) Update bill header
            if (!empty($createdOrderIds)) {
                $bill->ord_id = $createdOrderIds[0];
                $bill->save();
            }

            return $bill->fresh(['orders']);
        });
    }

    /**
     * Builds line items for services using pivot data (for boarding duration).
     */
    private function buildServiceLines(Visit $visit): array
{
    $lines = [];
    
    try {
        $services = $visit->services()->get(); 
    } catch (\Throwable $e) {
        $services = collect();
    }

    if ($services->isNotEmpty()) {
        foreach ($services as $s) {
            // FIX: Prioritize reading the pre-calculated total from the pivot table if available
            $quantity = $s->pivot->quantity ?? 1; 
            $unitPrice = $s->pivot->unit_price ?? $s->serv_price ?? 0;
            
            // Fallback price logic is robust. For boarding, this uses (Days * Daily Rate) as saved in the controller.
            
            $lines[] = [
                'service_name' => $s->serv_name ?? 'Service N/A',
                'prod_id' => $this->resolveServiceProductId($s->serv_id),
                'price' => $unitPrice, // Unit Price (Daily Rate)
                'quantity' => $quantity, // Quantity (Total Days)
            ];
        }
        return $lines; 
    }
        
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
}
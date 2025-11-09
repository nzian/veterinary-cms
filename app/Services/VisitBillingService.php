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

class VisitBillingService
{
    /**
     * Create a billing record for the given visit if not already created.
     * Includes service lines (from visit->services or service_type mapping)
     * and prescription lines (if any for same pet and date).
     */
    public function createFromVisit(Visit $visit): ?Billing
    {
        // If already has billing, skip
        if ($visit->billing) {
            return $visit->billing;
        }

        return DB::transaction(function () use ($visit) {
            // 1) Create bill header
            $bill = Billing::create([
                'bill_date' => $visit->visit_date ? Carbon::parse($visit->visit_date)->toDateString() : Carbon::now()->toDateString(),
                'visit_id' => $visit->visit_id,
                'bill_status' => 'Pending',
                'branch_id' => optional($visit->user)->branch_id ?? optional(Auth::user())->branch_id,
            ]);

            // 2) Add service line(s)
            $serviceLines = $this->buildServiceLines($visit);
            $createdOrderIds = [];
            foreach ($serviceLines as $line) {
                // Skip lines without a mapped product id
                if (empty($line['prod_id'])) { continue; }
                $ordId = DB::table('tbl_ord')->insertGetId([
                    'ord_quantity' => $line['quantity'],
                    'ord_date' => Carbon::now(),
                    'user_id' => $visit->user_id,
                    'prod_id' => $line['prod_id'],
                    'ord_price' => $line['price'],
                    'ord_total' => $line['price'] * $line['quantity'],
                    'own_id' => $this->resolveOwnerId($visit),
                    'bill_id' => $bill->bill_id,
                ], 'ord_id');
                if ($ordId) { $createdOrderIds[] = $ordId; }
            }

            // 3) Add prescription lines
            $prescriptionLines = $this->buildPrescriptionLines($visit);
            foreach ($prescriptionLines as $pline) {
                if (empty($pline['prod_id'])) { continue; }
                $ordId = DB::table('tbl_ord')->insertGetId([
                    'ord_quantity' => $pline['quantity'],
                    'ord_date' => Carbon::now(),
                    'user_id' => $visit->user_id,
                    'prod_id' => $pline['prod_id'],
                    'ord_price' => $pline['price'],
                    'ord_total' => $pline['price'] * $pline['quantity'],
                    'own_id' => $this->resolveOwnerId($visit),
                    'bill_id' => $bill->bill_id,
                ], 'ord_id');
                if ($ordId) { $createdOrderIds[] = $ordId; }
            }

            // 4) If ord_id is required in bill header, set it to the first created order id
            if (!empty($createdOrderIds)) {
                $bill->ord_id = $createdOrderIds[0];
                $bill->save();
            }

            return $bill->fresh(['orders']);
        });
    }

    private function buildServiceLines(Visit $visit): array
    {
        $lines = [];
        // Primary: linked services via pivot table
        try {
            $services = $visit->services()->with('branch')->get();
        } catch (\Throwable $e) {
            $services = collect();
        }

        if ($services->isNotEmpty()) {
            foreach ($services as $s) {
                $lines[] = [
                    'prod_id' => $this->resolveServiceProductId($s->serv_id),
                    'price' => $s->serv_price ?? 0,
                    'quantity' => 1,
                ];
            }
            return $lines;
        }

        // Fallback: map visit.service_type to a Service by type or name
        $stype = trim((string)($visit->service_type ?? ''));
        if ($stype !== '') {
            $service = Service::where('serv_type', $stype)
                ->orWhere('serv_name', 'like', "%$stype%")
                ->first();
            if ($service) {
                $lines[] = [
                    'prod_id' => $this->resolveServiceProductId($service->serv_id),
                    'price' => $service->serv_price ?? 0,
                    'quantity' => 1,
                ];
            }
        }
        return $lines;
    }

    private function resolveServiceProductId(int $servId): ?int
    {
        // If services are billed as their own service records, there may not be a product.
        // If you map services to a product row for billing, look it up here.
        // For now, try to find a product with a matching service name in description/name.
        $service = Service::find($servId);
        if (!$service) return null;
        $prod = Product::where('prod_name', 'like', "%{$service->serv_name}%")->first();
        return $prod->prod_id ?? null;
    }

    private function buildPrescriptionLines(Visit $visit): array
    {
        // Find prescription(s) for same pet and date
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

    private function resolveOwnerId(Visit $visit): ?int
    {
        // Resolve owner via pet
        try {
            $pet = $visit->pet;
            return $pet->own_id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

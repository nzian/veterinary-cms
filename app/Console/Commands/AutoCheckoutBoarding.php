<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Visit;
use App\Models\Service;
use App\Models\ServiceProduct;
use App\Models\InventoryTransaction;
use Carbon\Carbon;

class AutoCheckoutBoarding extends Command
{
    protected $signature = 'boarding:auto-checkout';
    protected $description = 'Automatically update boarding status to Check Out, deduct consumables, and generate billing if check-out time has passed.';

    public function handle()
    {
        $now = Carbon::now();
        $boardings = DB::table('tbl_boarding_record')
            ->where('status', 'Checked In')
            ->whereNotNull('check_out_date')
            ->where('check_out_date', '<=', $now)
            ->get();

        $count = 0;
        foreach ($boardings as $boarding) {
            // Update status to Checked Out
            DB::table('tbl_boarding_record')
                ->where('visit_id', $boarding->visit_id)
                ->where('pet_id', $boarding->pet_id)
                ->update(['status' => 'Checked Out', 'updated_at' => $now]);

            // NOTE: Consumable products are now deducted on CHECK-IN, not checkout
            // The deduction happens when the pet is first checked in, based on total_days
            // This prevents double deduction and ensures inventory is updated immediately

            // Generate billing if not already generated
            $visit = Visit::find($boarding->visit_id);
            if ($visit) {
                $existingBilling = DB::table('tbl_bill')->where('visit_id', $visit->visit_id)->first();
                if (!$existingBilling) {
                    $billing = (new \App\Services\VisitBillingService())->createFromVisit($visit);
                    if ($billing && $billing->bill_id) {
                        $this->info("Billing generated for Visit ID {$visit->visit_id}, Bill ID {$billing->bill_id}");
                    } else {
                        $this->warn("Failed to generate billing for Visit ID {$visit->visit_id}");
                    }
                }
            }
            $count++;
        }
        $this->info("Auto-checked out $count boarding records.");
        return 0;
    }

    /**
     * Deduct consumable products linked to a boarding service when auto-checkout.
     */
    private function deductBoardingConsumables($boarding): void
    {
        try {
            // Get service ID from boarding record
            $serviceId = $boarding->serv_id ?? null;
            if (!$serviceId) {
                Log::info('[Auto Checkout] No service ID found for boarding record', [
                    'visit_id' => $boarding->visit_id,
                    'pet_id' => $boarding->pet_id
                ]);
                return;
            }

            $service = Service::find($serviceId);
            if (!$service) {
                return;
            }

            $totalDays = $boarding->total_days ?? 1;
            $visit = Visit::with('pet')->find($boarding->visit_id);

            // Get consumable products linked to this boarding service
            $serviceProducts = ServiceProduct::where('serv_id', $serviceId)
                ->with('product')
                ->get();

            if ($serviceProducts->isEmpty()) {
                return;
            }

            Log::info('[Auto Checkout] Deducting consumables', [
                'service_id' => $serviceId,
                'total_days' => $totalDays,
                'products_count' => $serviceProducts->count()
            ]);

            foreach ($serviceProducts as $serviceProduct) {
                $product = $serviceProduct->product;
                
                if (!$product || $product->prod_type !== 'Consumable') {
                    continue;
                }

                // Calculate total quantity: quantity_per_day × total_days
                $quantityPerDay = (float) $serviceProduct->quantity_used;
                $totalQuantity = $quantityPerDay * $totalDays;

                // Check available stock
                $currentStock = (float) $product->prod_stocks;
                $actualDeduction = min($currentStock, $totalQuantity);

                if ($actualDeduction <= 0) {
                    Log::warning('[Auto Checkout] No stock available', [
                        'product_id' => $product->prod_id,
                        'required' => $totalQuantity
                    ]);
                    continue;
                }

                // Deduct from inventory
                $product->decrement('prod_stocks', $actualDeduction);

                // Record inventory transaction
                InventoryTransaction::create([
                    'prod_id' => $product->prod_id,
                    'transaction_type' => 'service_usage',
                    'quantity_change' => -$actualDeduction,
                    'reference' => "Auto Boarding Checkout - Visit #{$boarding->visit_id}",
                    'serv_id' => $serviceId,
                    'notes' => "Auto checkout for " . ($visit->pet->pet_name ?? 'Pet') . " ({$totalDays} days × {$quantityPerDay})",
                    'performed_by' => null,
                    'created_at' => now(),
                ]);

                $this->info("Deducted {$actualDeduction} x {$product->prod_name} for boarding");
            }

        } catch (\Exception $e) {
            Log::error('[Auto Checkout] Error deducting consumables: ' . $e->getMessage());
        }
    }
}

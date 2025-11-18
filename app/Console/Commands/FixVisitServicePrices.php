<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Service;

class FixVisitServicePrices extends Command
{
    protected $signature = 'fix:visit-service-prices';
    protected $description = 'Fix visit_service records that have 0 or null prices';

    public function handle()
    {
        $this->info('Fixing visit_service records with missing prices...');
        
        // Get all visit_service records where unit_price or total_price is 0 or null
        $records = DB::table('tbl_visit_service')
            ->where(function($query) {
                $query->where('unit_price', 0)
                      ->orWhereNull('unit_price')
                      ->orWhere('total_price', 0)
                      ->orWhereNull('total_price');
            })
            ->get();
        
        $this->info("Found {$records->count()} records to fix");
        
        $fixed = 0;
        foreach ($records as $record) {
            // Get the service details
            $service = Service::find($record->serv_id);
            if (!$service) {
                $this->warn("Service not found for serv_id: {$record->serv_id}");
                continue;
            }
            
            $quantity = $record->quantity ?? 1;
            $unitPrice = $record->unit_price;
            
            // If unit_price is 0 or null, use service price
            if (empty($unitPrice) || $unitPrice <= 0) {
                $unitPrice = $service->serv_price ?? 0;
            }
            
            $totalPrice = $unitPrice * $quantity;
            
            // Update the record
            DB::table('tbl_visit_service')
                ->where('id', $record->id)
                ->update([
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            
            $this->info("Fixed record ID {$record->id}: visit_id={$record->visit_id}, serv_id={$record->serv_id}, unit_price={$unitPrice}, quantity={$quantity}, total_price={$totalPrice}");
            $fixed++;
        }
        
        $this->info("Fixed {$fixed} records successfully!");
        
        // Now regenerate billing for visits that have bills
        $this->info("\nRegenerating billing records...");
        
        $bills = DB::table('tbl_bill')->get();
        $regenerated = 0;
        
        foreach ($bills as $bill) {
            // Delete existing orders for this bill
            DB::table('tbl_ord')->where('bill_id', $bill->bill_id)->delete();
            
            // Get visit
            $visit = \App\Models\Visit::find($bill->visit_id);
            if (!$visit) continue;
            
            // Recreate billing
            try {
                $billingService = new \App\Services\VisitBillingService();
                
                // Delete the bill and recreate
                DB::table('tbl_bill')->where('bill_id', $bill->bill_id)->delete();
                $billingService->createFromVisit($visit);
                
                $this->info("Regenerated billing for visit {$visit->visit_id}");
                $regenerated++;
            } catch (\Exception $e) {
                $this->error("Failed to regenerate billing for visit {$bill->visit_id}: " . $e->getMessage());
            }
        }
        
        $this->info("\nRegenerated {$regenerated} billing records!");
        $this->info('Done!');
        
        return 0;
    }
}

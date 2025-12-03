<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Visit;
use Carbon\Carbon;

class AutoCheckoutBoarding extends Command
{
    protected $signature = 'boarding:auto-checkout';
    protected $description = 'Automatically update boarding status to Check Out and generate billing if check-out time has passed.';

    public function handle()
    {
        $now = Carbon::now();
        $boardings = DB::table('tbl_boarding_record')
            ->where('status', 'Check In')
            ->whereNotNull('check_out_date')
            ->where('check_out_date', '<=', $now)
            ->get();

        $count = 0;
        foreach ($boardings as $boarding) {
            // Update status to Check Out
            DB::table('tbl_boarding_record')
                ->where('visit_id', $boarding->visit_id)
                ->where('pet_id', $boarding->pet_id)
                ->update(['status' => 'Check Out', 'updated_at' => $now]);

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
}

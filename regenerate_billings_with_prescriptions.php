<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Visit;
use App\Services\GroupedBillingService;
use Illuminate\Support\Facades\DB;

echo "=== REGENERATE BILLINGS WITH PRESCRIPTIONS ===\n\n";

$billingService = new GroupedBillingService();

// Find all billings from the last 2 days that might need prescription costs added
$recentBillings = DB::table('tbl_bill as b')
    ->join('tbl_visit_record as v', 'b.visit_id', '=', 'v.visit_id')
    ->whereDate('b.bill_date', '>=', now()->subDays(2))
    ->select('b.bill_id', 'b.visit_id', 'b.total_amount', 'v.pet_id', 'v.visit_date')
    ->get();

echo "Found " . $recentBillings->count() . " recent billings to check\n\n";

$updated = 0;
foreach($recentBillings as $billing) {
    // Check if this visit has prescriptions
    $hasPrescription = DB::table('tbl_prescription')
        ->where('pet_id', $billing->pet_id)
        ->whereDate('prescription_date', $billing->visit_date)
        ->exists();
    
    if ($hasPrescription) {
        $visit = Visit::with('services')->find($billing->visit_id);
        if ($visit) {
            // Use reflection to call protected method
            $reflection = new ReflectionClass($billingService);
            $method = $reflection->getMethod('calculateVisitTotal');
            $method->setAccessible(true);
            $newTotal = $method->invoke($billingService, $visit);
            
            if ($newTotal != $billing->total_amount) {
                DB::table('tbl_bill')
                    ->where('bill_id', $billing->bill_id)
                    ->update(['total_amount' => $newTotal]);
                
                echo "✓ Updated Bill #{$billing->bill_id} (Visit {$billing->visit_id})\n";
                echo "  OLD: ₱" . number_format($billing->total_amount, 2) . "\n";
                echo "  NEW: ₱" . number_format($newTotal, 2) . "\n";
                echo "  Difference: ₱" . number_format($newTotal - $billing->total_amount, 2) . "\n\n";
                $updated++;
            }
        }
    }
}

if ($updated == 0) {
    echo "No billings needed updating\n";
} else {
    echo "\n✅ Updated {$updated} billing(s) to include prescription costs\n";
}

echo "\n=== END ===\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== AUTO-FIX VISIT 205: CHANGE KENNEL COUGH TO ANTI-RABIES ===\n\n";

try {
    DB::beginTransaction();
    
    // Get the Anti-Rabies service (ID 133)
    $antiRabiesService = DB::table('tbl_serv')
        ->where('serv_id', 133)
        ->first();
    
    if (!$antiRabiesService) {
        echo "ERROR: Service ID 133 (Vaccination - Anti Rabies) not found!\n";
        exit;
    }
    
    echo "Selected Service: {$antiRabiesService->serv_name} (ID: {$antiRabiesService->serv_id})\n";
    echo "Price: ₱" . number_format($antiRabiesService->serv_price, 2) . "\n\n";
    
    // 1. Delete old service link (Kennel Cough)
    $deleted = DB::table('tbl_visit_service')
        ->where('visit_id', 205)
        ->where('serv_id', 131)
        ->delete();
    
    echo "✓ Removed Service ID 131 (Kennel Cough) from Visit 205\n";
    
    // 2. Add new service link (Anti-Rabies)
    DB::table('tbl_visit_service')->insert([
        'visit_id' => 205,
        'serv_id' => $antiRabiesService->serv_id,
        'status' => 'completed',
        'completed_at' => now(),
        'quantity' => 1,
        'unit_price' => $antiRabiesService->serv_price,
        'total_price' => $antiRabiesService->serv_price,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✓ Added Service ID {$antiRabiesService->serv_id} ({$antiRabiesService->serv_name}) to Visit 205\n";
    
    // 3. Delete old billing
    $oldBillingId = DB::table('tbl_bill')
        ->where('visit_id', 205)
        ->value('bill_id');
    
    if ($oldBillingId) {
        DB::table('tbl_bill')->where('bill_id', $oldBillingId)->delete();
        echo "✓ Deleted old billing (Bill ID: {$oldBillingId})\n";
    }
    
    // 4. Regenerate billing
    $visit = DB::table('tbl_visit_record as v')
        ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
        ->where('v.visit_id', 205)
        ->select('v.*', 'p.own_id')
        ->first();
    
    if ($visit) {
        $totalAmount = $antiRabiesService->serv_price;
        
        // Create new billing
        $newBillingId = DB::table('tbl_bill')->insertGetId([
            'visit_id' => 205,
            'owner_id' => $visit->own_id,
            'bill_date' => now()->toDateString(),
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'bill_status' => 'unpaid',
            'branch_id' => $visit->branch_id ?? null,
        ]);
        
        echo "✓ Created new billing (Bill ID: {$newBillingId}) with total: ₱" . number_format($totalAmount, 2) . "\n";
    }
    
    DB::commit();
    
    echo "\n";
    echo "SUCCESS! Visit 205 has been corrected:\n";
    echo "- OLD: Vaccination - Kennel Cough (₱950.00)\n";
    echo "- NEW: {$antiRabiesService->serv_name} (₱" . number_format($antiRabiesService->serv_price, 2) . ")\n";
    echo "- Billing Status: unpaid\n";
    
    // Verify the change
    echo "\nVerification:\n";
    echo str_repeat("-", 80) . "\n";
    
    $verifyService = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', 205)
        ->select('s.serv_id', 's.serv_name', 'vs.total_price')
        ->first();
    
    if ($verifyService) {
        echo "Visit 205 now has: Service ID {$verifyService->serv_id} - {$verifyService->serv_name} - ₱" . number_format($verifyService->total_price, 2) . "\n";
    }
    
    $verifyBilling = DB::table('tbl_bill')
        ->where('visit_id', 205)
        ->first();
    
    if ($verifyBilling) {
        echo "Billing: Bill ID {$verifyBilling->bill_id} - Total: ₱" . number_format($verifyBilling->total_amount, 2) . " - Status: {$verifyBilling->bill_status}\n";
    }
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIX COMPLETED ===\n";

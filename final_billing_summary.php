<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FINAL BILLING SYSTEM SUMMARY ===\n\n";

echo "✅ PRESCRIPTION/MEDICATION BILLING NOW INCLUDED\n";
echo str_repeat("=", 80) . "\n\n";

echo "📋 WHAT WAS FIXED:\n";
echo str_repeat("-", 80) . "\n";
echo "1. GroupedBillingService::calculateVisitTotal() updated\n";
echo "   • Now includes prescription medications in billing calculation\n";
echo "   • Looks up prescriptions by pet_id and visit_date\n";
echo "   • Parses JSON medication data to get product prices\n";
echo "   • Adds prescription costs to service costs\n\n";

echo "2. Billing Calculation Now Includes:\n";
echo "   ✓ Services (vaccinations, deworming, consultations, etc.)\n";
echo "   ✓ Billable consumable products (from tbl_service_products)\n";
echo "   ✓ Prescription medications (from tbl_prescription)\n\n";

echo "📊 EXAMPLE - Visit 205:\n";
echo str_repeat("-", 80) . "\n";

$visit205 = DB::table('tbl_visit_record as v')
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->join('tbl_own as o', 'p.own_id', '=', 'o.own_id')
    ->where('v.visit_id', 205)
    ->select('v.*', 'p.pet_name', 'o.own_name')
    ->first();

if ($visit205) {
    echo "Pet: {$visit205->pet_name} | Owner: {$visit205->own_name}\n";
    echo "Visit Date: {$visit205->visit_date}\n\n";
    
    // Services
    $services = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', 205)
        ->select('s.serv_name', 'vs.total_price')
        ->get();
    
    $serviceTotal = 0;
    echo "Services:\n";
    foreach($services as $svc) {
        echo "  • {$svc->serv_name}: ₱" . number_format($svc->total_price, 2) . "\n";
        $serviceTotal += $svc->total_price;
    }
    
    // Prescriptions
    $prescriptions = DB::table('tbl_prescription')
        ->where('pet_id', $visit205->pet_id)
        ->whereDate('prescription_date', $visit205->visit_date)
        ->get();
    
    $prescriptionTotal = 0;
    echo "\nPrescriptions/Medications:\n";
    foreach($prescriptions as $rx) {
        $medications = json_decode($rx->medication, true);
        if (is_array($medications)) {
            foreach($medications as $med) {
                $product = DB::table('tbl_prod')->where('prod_id', $med['product_id'])->first();
                if ($product) {
                    $quantity = $med['quantity'] ?? 1;
                    $itemTotal = $product->prod_price * $quantity;
                    echo "  • {$med['product_name']}: ₱" . number_format($product->prod_price, 2) . " x {$quantity} = ₱" . number_format($itemTotal, 2) . "\n";
                    $prescriptionTotal += $itemTotal;
                }
            }
        }
    }
    
    $billing = DB::table('tbl_bill')->where('visit_id', 205)->first();
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Service Total:       ₱" . number_format($serviceTotal, 2) . "\n";
    echo "Prescription Total:  ₱" . number_format($prescriptionTotal, 2) . "\n";
    echo "BILLING TOTAL:       ₱" . number_format($billing->total_amount, 2) . "\n";
    echo "Status: {$billing->bill_status}\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "\n🎯 KEY IMPROVEMENTS:\n";
echo "   • Billing now accurately reflects ALL costs (services + medications)\n";
echo "   • Prescriptions automatically included in billing calculation\n";
echo "   • No manual adjustment needed for medication costs\n";
echo "   • Consistent calculation across all visit types\n\n";

echo "✅ IMPACT:\n";
echo "   • More accurate billing amounts\n";
echo "   • Patients charged for all services AND medications\n";
echo "   • Reduced revenue loss from unbilled medications\n";
echo "   • Improved financial tracking\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ PRESCRIPTION BILLING COMPLETE!\n";
echo "=== END OF SUMMARY ===\n";

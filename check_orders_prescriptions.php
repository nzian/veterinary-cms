<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ORDERS AND PRESCRIPTIONS RELATIONSHIP ===\n\n";

// Check orders table
echo "tbl_ord columns:\n";
echo str_repeat("-", 80) . "\n";
$columns = DB::select('DESCRIBE tbl_ord');
foreach($columns as $col) {
    echo "{$col->Field} | {$col->Type}\n";
}

echo "\n\nRecent orders:\n";
echo str_repeat("-", 80) . "\n";
$orders = DB::table('tbl_ord as o')
    ->leftJoin('tbl_prod as p', 'o.prod_id', '=', 'p.prod_id')
    ->orderBy('o.ord_id', 'desc')
    ->limit(5)
    ->select('o.*', 'p.prod_name')
    ->get();

foreach($orders as $order) {
    echo "Order ID: {$order->ord_id}\n";
    if(isset($order->visit_id)) echo "Visit ID: {$order->visit_id}\n";
    if(isset($order->own_id)) echo "Owner ID: {$order->own_id}\n";
    if(isset($order->bill_id)) echo "Bill ID: {$order->bill_id}\n";
    echo "Product: {$order->prod_name}\n";
    echo "Quantity: {$order->ord_quantity} | Total: ₱{$order->ord_total}\n";
    echo "Date: {$order->ord_date}\n";
    echo str_repeat("-", 80) . "\n";
}

// Check for visits with medications/prescriptions
echo "\n\nChecking recent visits with prescriptions:\n";
echo str_repeat("-", 80) . "\n";

$visitsWithPrescriptions = DB::table('tbl_visit_record as v')
    ->join('tbl_prescription as rx', function($join) {
        $join->on('v.pet_id', '=', 'rx.pet_id')
             ->whereRaw('DATE(rx.prescription_date) = DATE(v.visit_date)');
    })
    ->join('tbl_pet as p', 'v.pet_id', '=', 'p.pet_id')
    ->whereDate('v.visit_date', '>=', now()->subDays(2))
    ->select('v.visit_id', 'v.visit_date', 'p.pet_name', 'rx.prescription_id', 'rx.medication')
    ->orderBy('v.visit_id', 'desc')
    ->limit(5)
    ->get();

if($visitsWithPrescriptions->isEmpty()) {
    echo "No recent visits with prescriptions found\n";
} else {
    foreach($visitsWithPrescriptions as $visit) {
        echo "Visit ID: {$visit->visit_id} | Pet: {$visit->pet_name}\n";
        echo "Prescription ID: {$visit->prescription_id}\n";
        echo "Medication: {$visit->medication}\n";
        echo str_repeat("-", 80) . "\n";
    }
}

echo "\n=== END ===\n";

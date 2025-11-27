<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRESCRIPTION STRUCTURE ===\n\n";

// Check prescription table columns
echo "tbl_prescription columns:\n";
echo str_repeat("-", 80) . "\n";
$columns = DB::select('DESCRIBE tbl_prescription');
foreach($columns as $col) {
    echo "{$col->Field} | {$col->Type} | Null: {$col->Null} | Key: {$col->Key}\n";
}

echo "\n\nSample prescription data:\n";
echo str_repeat("-", 80) . "\n";
$prescriptions = DB::table('tbl_prescription')
    ->limit(3)
    ->get();

foreach($prescriptions as $rx) {
    echo "Prescription ID: {$rx->prescription_id}\n";
    echo "Pet ID: {$rx->pet_id}\n";
    echo "Date: {$rx->prescription_date}\n";
    echo "Medication: {$rx->medication}\n";
    echo str_repeat("-", 80) . "\n";
}

// Check if there's a related items table
echo "\n\nChecking for prescription-related tables:\n";
echo str_repeat("-", 80) . "\n";
$tables = DB::select("SHOW TABLES LIKE '%presc%'");
foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    echo "Found: {$tableName}\n";
}

echo "\n=== END ===\n";

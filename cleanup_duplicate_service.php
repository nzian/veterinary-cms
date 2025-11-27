<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLEANUP DUPLICATE SERVICE ===\n\n";

// Get all service entries for Visit 205
$services = DB::table('tbl_visit_service')
    ->where('visit_id', 205)
    ->orderBy('completed_at')
    ->get();

echo "Found " . $services->count() . " service entries for Visit 205\n";

if ($services->count() > 1) {
    // Keep the most recent one, delete the rest
    $keepId = $services->last()->id ?? null;
    
    if ($keepId) {
        $deleted = DB::table('tbl_visit_service')
            ->where('visit_id', 205)
            ->where('id', '!=', $keepId)
            ->delete();
        
        echo "✓ Deleted {$deleted} duplicate service(s)\n";
        echo "✓ Kept service entry ID: {$keepId}\n";
    }
}

// Verify
$remaining = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('vs.visit_id', 205)
    ->select('vs.id', 's.serv_id', 's.serv_name', 'vs.total_price')
    ->get();

echo "\nRemaining services for Visit 205:\n";
foreach ($remaining as $svc) {
    echo "- ID: {$svc->id} | Service ID: {$svc->serv_id} | {$svc->serv_name} | ₱" . number_format($svc->total_price, 2) . "\n";
}

echo "\n=== CLEANUP COMPLETED ===\n";

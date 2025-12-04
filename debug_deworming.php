<?php
/**
 * Debug script for deworming visit service issue
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get the most recent deworming visits
echo "=== Recent Deworming Visits ===\n\n";

$recentVisits = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where(DB::raw('LOWER(s.serv_type)'), 'like', '%deworming%')
    ->orderBy('v.visit_id', 'desc')
    ->limit(10)
    ->select('v.visit_id', 'v.pet_id', 'v.visit_date', 's.serv_id', 's.serv_name', 's.serv_type', 's.branch_id as service_branch', 'vs.status', 'vs.completed_at')
    ->get();

foreach ($recentVisits as $visit) {
    echo "Visit ID: {$visit->visit_id}\n";
    echo "  Pet ID: {$visit->pet_id}\n";
    echo "  Visit Date: {$visit->visit_date}\n";
    echo "  Service ID: {$visit->serv_id}\n";
    echo "  Service Name: {$visit->serv_name}\n";
    echo "  Service Type: {$visit->serv_type}\n";
    echo "  Service Branch: {$visit->service_branch}\n";
    echo "  Status: {$visit->status}\n";
    echo "  Completed At: " . ($visit->completed_at ?? 'NULL') . "\n";
    echo "\n";
}

// Look for visits with multiple services where at least one is deworming
echo "\n=== Visits with Multiple Services (Including Deworming) ===\n\n";

$visitIds = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where(DB::raw('LOWER(s.serv_type)'), 'like', '%deworming%')
    ->distinct()
    ->pluck('vs.visit_id');

foreach ($visitIds->take(5) as $visitId) {
    $services = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', $visitId)
        ->select('vs.visit_id', 's.serv_id', 's.serv_name', 's.serv_type', 's.branch_id', 'vs.status', 'vs.completed_at')
        ->get();
    
    echo "Visit ID: {$visitId} - Total services: " . count($services) . "\n";
    foreach ($services as $svc) {
        $statusBadge = $svc->status == 'completed' ? '✓ COMPLETED' : '⏳ PENDING';
        echo "  [{$statusBadge}] Service {$svc->serv_id}: {$svc->serv_name} (Type: {$svc->serv_type}, Branch: {$svc->branch_id})\n";
    }
    echo "\n";
}

// Check deworming services by branch
echo "\n=== Deworming Services by Branch ===\n\n";

$dewormingServices = DB::table('tbl_serv')
    ->where(DB::raw('LOWER(serv_type)'), 'like', '%deworming%')
    ->select('serv_id', 'serv_name', 'serv_type', 'branch_id')
    ->orderBy('branch_id')
    ->get();

foreach ($dewormingServices as $svc) {
    echo "Service ID: {$svc->serv_id} | Branch: {$svc->branch_id} | Name: {$svc->serv_name} | Type: {$svc->serv_type}\n";
}

echo "\n=== Done ===\n";

<?php
/**
 * Cleanup script to remove services from wrong branches
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Cleaning up corrupted visit services ===\n\n";

// Visit 6 - Remove service 134 from branch 1 (user was on branch 19)
$deleted1 = DB::table('tbl_visit_service')
    ->where('visit_id', 6)
    ->where('serv_id', 134)
    ->delete();
echo "Visit 6: Removed service 134 (branch 1) - " . ($deleted1 ? "SUCCESS" : "Not found") . "\n";

// Visit 10 - Remove service 131 from branch 1 (user was on branch 19)
$deleted2 = DB::table('tbl_visit_service')
    ->where('visit_id', 10)
    ->where('serv_id', 131)
    ->delete();
echo "Visit 10: Removed service 131 (branch 1) - " . ($deleted2 ? "SUCCESS" : "Not found") . "\n";

echo "\n=== Verification ===\n\n";

// Verify visit 6
$visit6Services = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('vs.visit_id', 6)
    ->select('s.serv_id', 's.serv_name', 's.branch_id', 'vs.status')
    ->get();
echo "Visit 6 services:\n";
foreach ($visit6Services as $svc) {
    echo "  - Service {$svc->serv_id}: {$svc->serv_name} (Branch: {$svc->branch_id}, Status: {$svc->status})\n";
}

// Verify visit 10
$visit10Services = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('vs.visit_id', 10)
    ->select('s.serv_id', 's.serv_name', 's.branch_id', 'vs.status')
    ->get();
echo "\nVisit 10 services:\n";
foreach ($visit10Services as $svc) {
    echo "  - Service {$svc->serv_id}: {$svc->serv_name} (Branch: {$svc->branch_id}, Status: {$svc->status})\n";
}

echo "\n=== Done ===\n";

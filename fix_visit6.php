<?php
/**
 * Fix visit 6 - remove wrong branch service
 */
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Remove service 131 (branch 1) from visit 10
$deleted = DB::table('tbl_visit_service')
    ->where('visit_id', 10)
    ->where('serv_id', 131)
    ->delete();

echo "Deleted $deleted row(s) - removed service 131 (branch 1) from visit 10\n";

// Verify
$remaining = DB::table('tbl_visit_service as vs')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('vs.visit_id', 10)
    ->select('vs.visit_id', 's.serv_id', 's.serv_name', 's.branch_id', 'vs.status')
    ->get();

echo "\nRemaining services for visit 10:\n";
foreach ($remaining as $svc) {
    echo "  Service {$svc->serv_id}: {$svc->serv_name} (Branch: {$svc->branch_id}, Status: {$svc->status})\n";
}

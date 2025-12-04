<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$visits = DB::table('tbl_visit_record')
    ->select('visit_id', 'visit_service_type')
    ->orderBy('visit_id', 'desc')
    ->limit(15)
    ->get();

echo "=== Visit Service Types ===\n\n";
foreach ($visits as $v) {
    echo "Visit {$v->visit_id}: " . ($v->visit_service_type ?? 'NULL');
    
    // Check attached services
    $services = DB::table('tbl_visit_service as vs')
        ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
        ->where('vs.visit_id', $v->visit_id)
        ->select('s.serv_name', 's.serv_type', 'vs.status')
        ->get();
    
    if ($services->count() > 0) {
        echo " | Services: ";
        foreach ($services as $svc) {
            echo "[{$svc->serv_name} (type: {$svc->serv_type}, status: {$svc->status})] ";
        }
    } else {
        echo " | NO SERVICES ATTACHED";
    }
    echo "\n";
}

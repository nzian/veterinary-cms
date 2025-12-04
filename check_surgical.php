<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Surgical Records ===\n\n";
$surgicalRecords = DB::table('tbl_surgical_record')->get();
if ($surgicalRecords->isEmpty()) {
    echo "No surgical records found.\n";
} else {
    foreach ($surgicalRecords as $rec) {
        echo "Record ID: " . ($rec->record_id ?? $rec->id ?? 'N/A') . "\n";
        echo "  Visit ID: {$rec->visit_id}\n";
        echo "  Pet ID: {$rec->pet_id}\n";
        echo "  Procedure: " . ($rec->procedure_name ?? 'NULL') . "\n";
        echo "  Service ID: " . ($rec->service_id ?? 'NULL') . "\n";
        echo "  Status: " . ($rec->status ?? 'NULL') . "\n";
        echo "  Created: " . ($rec->created_at ?? 'NULL') . "\n";
        echo "\n";
    }
}

echo "\n=== Visits with Surgical Service Type ===\n\n";
$surgicalVisits = DB::table('tbl_visit_record')
    ->where(function($q) {
        $q->where(DB::raw('LOWER(visit_service_type)'), 'LIKE', '%surgical%')
          ->orWhere(DB::raw('LOWER(visit_service_type)'), 'LIKE', '%surgery%');
    })
    ->get();

if ($surgicalVisits->isEmpty()) {
    echo "No visits with surgical service type found.\n";
} else {
    foreach ($surgicalVisits as $v) {
        echo "Visit {$v->visit_id}: {$v->visit_service_type} (Status: {$v->visit_status})\n";
        
        // Check attached services
        $services = DB::table('tbl_visit_service as vs')
            ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
            ->where('vs.visit_id', $v->visit_id)
            ->select('s.serv_id', 's.serv_name', 's.serv_type', 'vs.status')
            ->get();
        
        if ($services->isNotEmpty()) {
            foreach ($services as $svc) {
                echo "  - Service {$svc->serv_id}: {$svc->serv_name} (type: {$svc->serv_type}, status: {$svc->status})\n";
            }
        } else {
            echo "  - No services attached\n";
        }
    }
}

echo "\n=== tbl_surgical_record Table Structure ===\n";
$columns = DB::select("SHOW COLUMNS FROM tbl_surgical_record");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})" . ($col->Null === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
}

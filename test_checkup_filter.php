<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Test the filter logic
$checkupTypes = ['check-up', 'consultation', 'checkup'];

echo "=== Testing Check-up Filter ===\n\n";

// Get visits that should match check-up
$matchingVisits = DB::table('tbl_visit_record')
    ->where(function($query) use ($checkupTypes) {
        foreach ($checkupTypes as $type) {
            $query->orWhere(DB::raw('LOWER(visit_service_type)'), 'LIKE', '%' . strtolower($type) . '%');
        }
    })
    ->select('visit_id', 'visit_service_type')
    ->get();

echo "Visits matching check-up filter by visit_service_type:\n";
if ($matchingVisits->isEmpty()) {
    echo "  (none found)\n";
} else {
    foreach ($matchingVisits as $v) {
        echo "  Visit {$v->visit_id}: {$v->visit_service_type}\n";
    }
}

// Also check via services
echo "\nVisits matching check-up filter by attached services:\n";
$matchingByService = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->whereIn(DB::raw('LOWER(s.serv_type)'), $checkupTypes)
    ->select('v.visit_id', 'v.visit_service_type', 's.serv_type')
    ->distinct()
    ->get();

if ($matchingByService->isEmpty()) {
    echo "  (none found)\n";
} else {
    foreach ($matchingByService as $v) {
        echo "  Visit {$v->visit_id}: {$v->visit_service_type} (service type: {$v->serv_type})\n";
    }
}

// Show all unique visit_service_type values
echo "\n=== All Unique visit_service_type Values ===\n";
$allTypes = DB::table('tbl_visit_record')
    ->whereNotNull('visit_service_type')
    ->where('visit_service_type', '!=', '')
    ->distinct()
    ->pluck('visit_service_type');

foreach ($allTypes as $type) {
    echo "  - '$type'\n";
}

// Show all unique serv_type values
echo "\n=== All Unique serv_type Values ===\n";
$allServTypes = DB::table('tbl_serv')
    ->whereNotNull('serv_type')
    ->distinct()
    ->pluck('serv_type');

foreach ($allServTypes as $type) {
    echo "  - '$type'\n";
}

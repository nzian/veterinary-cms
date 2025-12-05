<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Create a Laravel application instance
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking appointment types in database...\n";

// Check current appointment type distribution
$appointmentTypes = DB::table('tbl_appoint')
    ->select('appoint_type', DB::raw('count(*) as count'))
    ->groupBy('appoint_type')
    ->get();

echo "\nCurrent appointment type distribution:\n";
foreach ($appointmentTypes as $type) {
    echo "- {$type->appoint_type}: {$type->count} records\n";
}

// Update "Follow-up" to "General Follow-up"
$updated = DB::table('tbl_appoint')
    ->where('appoint_type', 'Follow-up')
    ->update(['appoint_type' => 'General Follow-up']);

echo "\nUpdated {$updated} appointment records from 'Follow-up' to 'General Follow-up'\n";

// Show final distribution
echo "\nFinal appointment type distribution:\n";
$finalTypes = DB::table('tbl_appoint')
    ->select('appoint_type', DB::raw('count(*) as count'))
    ->groupBy('appoint_type')
    ->get();

foreach ($finalTypes as $type) {
    echo "- {$type->appoint_type}: {$type->count} records\n";
}

echo "\nAppointment type fix completed!\n";

?>
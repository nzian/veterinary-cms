<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== All Appointment Records ===\n";

$appointments = DB::table('tbl_appoint')->get();

echo "Total records: " . count($appointments) . "\n\n";

foreach ($appointments as $appointment) {
    echo "ID: {$appointment->appoint_id}\n";
    echo "Type: [{$appointment->appoint_type}]\n";
    echo "Date: {$appointment->appoint_date}\n";
    echo "Status: {$appointment->appoint_status}\n";
    echo "---\n";
}

// Also check if there are any with just "Follow-up"
$followupOnly = DB::table('tbl_appoint')
    ->where('appoint_type', 'Follow-up')
    ->get();

echo "\nRecords with exactly 'Follow-up' type: " . count($followupOnly) . "\n";

foreach ($followupOnly as $record) {
    echo "ID: {$record->appoint_id} - [{$record->appoint_type}]\n";
}

?>
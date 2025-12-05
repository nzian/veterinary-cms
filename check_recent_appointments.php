<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Recent Appointments ===\n";

$recent = DB::table('tbl_appoint')->orderBy('created_at', 'desc')->limit(10)->get();

foreach ($recent as $appointment) {
    echo "ID: {$appointment->appoint_id}\n";
    echo "Type: [{$appointment->appoint_type}]\n";
    echo "Created: {$appointment->created_at}\n";
    echo "Date: {$appointment->appoint_date}\n";
    echo "---\n";
}

?>
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Surgical Services ===\n\n";
$services = DB::table('tbl_serv')
    ->where('serv_type', 'like', '%surgical%')
    ->orWhere('serv_type', 'like', '%Surgical%')
    ->select('serv_id', 'serv_name', 'serv_type', 'branch_id', 'serv_price')
    ->get();

if ($services->isEmpty()) {
    echo "No surgical services found!\n";
} else {
    foreach ($services as $s) {
        echo "ID: {$s->serv_id} | Branch: {$s->branch_id} | Price: {$s->serv_price} | Name: {$s->serv_name} | Type: {$s->serv_type}\n";
    }
}

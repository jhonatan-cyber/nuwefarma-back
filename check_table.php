<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("SHOW COLUMNS FROM sucursals;");
foreach ($columns as $col) {
    echo $col->Field . ': ' . $col->Type . "\n";
}

$sample = DB::select("SELECT id FROM sucursals LIMIT 1;");
if ($sample) {
    echo "\nSample ID: " . $sample[0]->id . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\Usuario::with('rol')->get();
foreach ($users as $u) {
    echo "User: {$u->nombre} {$u->apellidos}\n";
    echo "Rol: " . ($u->rol ? $u->rol->nombre : 'Sin Rol') . "\n";
    echo "Sucursal ID: {$u->sucursal_id}\n";
    echo "-------------------\n";
}

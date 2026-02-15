<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\Usuario::with('rol')->get();
foreach ($users as $u) {
    if ($u->rol) {
        echo "User: {$u->nombre}, Rol: '{$u->rol->nombre}' (len: ".strlen($u->rol->nombre).")\n";
    }
}

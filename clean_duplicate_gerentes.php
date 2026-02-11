<?php

require_once 'vendor/autoload.php';

// Simular una aplicación Laravel básica para testing
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 Cleaning Duplicate Gerente Assignments\n";
echo "========================================\n\n";

try {
    // Encontrar duplicados
    $sucursales = \App\Models\Sucursal::whereNotNull('gerente_id')->get();
    $gerenteAssignments = [];
    
    foreach ($sucursales as $sucursal) {
        if (!isset($gerenteAssignments[$sucursal->gerente_id])) {
            $gerenteAssignments[$sucursal->gerente_id] = [];
        }
        $gerenteAssignments[$sucursal->gerente_id][] = $sucursal;
    }
    
    echo "📊 Current assignments:\n";
    foreach ($gerenteAssignments as $gerenteId => $assignments) {
        $gerente = \App\Models\Usuario::find($gerenteId);
        $gerenteName = $gerente ? $gerente->nombre . ' ' . $gerente->apellidos : 'Unknown';
        
        echo "   {$gerenteName}: " . count($assignments) . " sucursales\n";
        foreach ($assignments as $sucursal) {
            echo "     - {$sucursal->nombre}\n";
        }
        
        // Si hay duplicados, mantener solo el primero
        if (count($assignments) > 1) {
            echo "   🔧 Removing duplicates...\n";
            for ($i = 1; $i < count($assignments); $i++) {
                $assignments[$i]->gerente_id = null;
                $assignments[$i]->save();
                echo "     ✅ Removed from: {$assignments[$i]->nombre}\n";
            }
        }
    }
    
    echo "\n✅ Cleanup completed!\n\n";
    
    // Verificar resultado
    echo "📊 Final state:\n";
    $finalSucursales = \App\Models\Sucursal::with('gerente')->whereNotNull('gerente_id')->get();
    
    foreach ($finalSucursales as $sucursal) {
        echo "   {$sucursal->nombre}: {$sucursal->gerente->nombre} {$sucursal->gerente->apellidos}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Cleanup failed with error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Cleanup complete!\n";
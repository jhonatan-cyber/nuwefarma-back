<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Analgésicos',
                'descripcion' => 'Medicamentos para aliviar el dolor',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Antibióticos',
                'descripcion' => 'Medicamentos para tratar infecciones bacterianas',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Antiinflamatorios',
                'descripcion' => 'Medicamentos para reducir la inflamación',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Vitaminas y Suplementos',
                'descripcion' => 'Complementos nutricionales y vitamínicos',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Antihistamínicos',
                'descripcion' => 'Medicamentos para tratar alergias',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Digestivos',
                'descripcion' => 'Medicamentos para problemas gastrointestinales',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Respiratorios',
                'descripcion' => 'Medicamentos para afecciones del sistema respiratorio',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Dermatológicos',
                'descripcion' => 'Productos para el cuidado y tratamiento de la piel',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Cardiovasculares',
                'descripcion' => 'Medicamentos para el corazón y sistema circulatorio',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Material de Curación',
                'descripcion' => 'Vendas, gasas y material médico para curaciones',
                'estado' => 'activo',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['nombre' => $categoria['nombre']],
                [
                    'id' => Str::uuid(),
                    'descripcion' => $categoria['descripcion'],
                    'estado' => $categoria['estado'],
                ]
            );
        }
    }
}

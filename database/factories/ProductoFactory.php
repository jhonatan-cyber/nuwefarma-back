<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'nombre' => fake()->words(3, true),
            'categoria_id' => Categoria::factory(),
            'proveedor_id' => Proveedor::factory(),
            'codigo_barras' => fake()->numerify('##############'),
            'sku' => fake()->unique()->numerify('##########'),
            'codigo_interno' => fake()->unique()->numerify('INT-##########'),
            'laboratorio' => fake()->company(),
            'forma_farmaceutica' => fake()->randomElement(['Tableta', 'Cápsula', 'Jarabe', 'Inyectable']),
            'concentracion' => fake()->randomElement(['10mg', '20mg', '50mg', '100mg']),
            'presentacion' => fake()->randomElement(['Caja', 'Blíster', 'Frasco']),
            'via_administracion' => fake()->randomElement(['Oral', 'Tópica', 'Intravenosa']),
            'unidad_medida' => fake()->randomElement(['mg', 'ml', 'unidades']),
            'fracciones_por_unidad' => fake()->numberBetween(1, 100),
            'permite_fraccionar' => fake()->boolean(),
            'lote' => fake()->numerify('LOT-##########'),
            'fecha_vencimiento' => fake()->dateTimeBetween('+1 year', '+5 years'),
            'registro_sanitario' => fake()->numerify('REG-##########'),
            'refrigeracion_requerida' => fake()->boolean(),
            'dias_para_alertar_vencimiento' => fake()->numberBetween(30, 90),
            'stock_actual' => fake()->numberBetween(0, 1000),
            'stock_minimo' => fake()->numberBetween(1, 50),
            'stock_maximo' => fake()->numberBetween(50, 2000),
            'precio_compra' => fake()->randomFloat(5, 50, 100),
            'precio_venta' => fake()->randomFloat(10, 100, 200),
            'margen_sugerido' => fake()->randomFloat(10, 50, 100),
            'impuesto' => fake()->randomFloat(0, 20, 100),
            'etiquetas' => fake()->words(5, false),
            'fotos' => fake()->randomElements(['foto1.jpg', 'foto2.jpg']),
            'descripcion' => fake()->sentence(),
            'estado' => 'activo',
        ];
    }
}

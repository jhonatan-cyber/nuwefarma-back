<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rol>
 */
class RolFactory extends Factory
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
            'nombre' => fake()->randomElement(['Administrador', 'Gerente', 'Vendedor', 'Cajero']),
            'descripcion' => fake()->sentence(),
            'estado' => 'activo',
        ];
    }
}

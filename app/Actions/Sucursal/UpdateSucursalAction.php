<?php

declare(strict_types=1);

namespace App\Actions\Sucursal;

use App\Models\Sucursal;
use Illuminate\Validation\Rule;

class UpdateSucursalAction
{
    /**
     * Update an existing branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Sucursal $sucursal, array $data): Sucursal
    {
        $validatedData = $this->validate($data, $sucursal);

        $sucursal->update($validatedData);

        return $sucursal->fresh()->load(['gerente']);
    }

    /**
     * Validate the branch data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Sucursal $sucursal): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('sucursals', 'nombre')->ignore($sucursal->id)],
            'direccion' => ['sometimes', 'string', 'max:500'],
            'telefono' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('sucursals', 'email')->ignore($sucursal->id)],
            'ciudad' => ['sometimes', 'string', 'max:100'],
            'departamento' => ['sometimes', 'string', 'max:100'],
            'pais' => ['sometimes', 'string', 'max:100'],
            'gerente_id' => ['sometimes', 'exists:usuarios,id'],
            'capacidad_maxima' => ['sometimes', 'integer', 'min:1'],
            'horario_apertura' => ['sometimes', 'string', 'max:50'],
            'horario_cierre' => ['sometimes', 'string', 'max:50'],
            'dias_laborales' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['sometimes', 'string', 'max:1000'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

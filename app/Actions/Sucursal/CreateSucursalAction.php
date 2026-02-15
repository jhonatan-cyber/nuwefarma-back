<?php

declare(strict_types=1);

namespace App\Actions\Sucursal;

use App\Models\Sucursal;
use Illuminate\Validation\Rule;

class CreateSucursalAction
{
    /**
     * Create a new branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Sucursal
    {
        $validatedData = $this->validate($data);

        return Sucursal::create($validatedData);
    }

    /**
     * Validate the branch data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255', 'unique:sucursals,nombre'],
            'direccion' => ['required', 'string', 'max:500'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:sucursals,email'],
            'ciudad' => ['required', 'string', 'max:100'],
            'departamento' => ['required', 'string', 'max:100'],
            'pais' => ['required', 'string', 'max:100'],
            'gerente_id' => ['nullable', 'exists:usuarios,id'],
            'capacidad_maxima' => ['nullable', 'integer', 'min:1'],
            'horario_apertura' => ['nullable', 'string', 'max:50'],
            'horario_cierre' => ['nullable', 'string', 'max:50'],
            'dias_laborales' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

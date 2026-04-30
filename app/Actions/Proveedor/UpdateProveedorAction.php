<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Models\Proveedor;
use Illuminate\Validation\Rule;

class UpdateProveedorAction
{
    /**
     * Update an existing provider.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Proveedor $proveedor, array $data): Proveedor
    {
        $validatedData = $this->validate($data, $proveedor);

        $proveedor->update($validatedData);

        return $proveedor->fresh();
    }

    /**
     * Validate the provider data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Proveedor $proveedor): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nit' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('proveedores', 'nit')->ignore($proveedor->id)],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('proveedores', 'email')->ignore($proveedor->id)],
            'ciudad' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pais' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contacto' => ['sometimes', 'nullable', 'string', 'max:150'],
            'telefono_contacto' => ['sometimes', 'nullable', 'string', 'max:20'],
            'categoria' => ['sometimes', 'nullable', 'string', 'max:100'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'estado' => ['sometimes', 'nullable', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

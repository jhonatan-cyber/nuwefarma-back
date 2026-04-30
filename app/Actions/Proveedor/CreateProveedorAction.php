<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Models\Proveedor;
use Illuminate\Validation\Rule;

class CreateProveedorAction
{
    /**
     * Create a new provider.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Proveedor
    {
        $validatedData = $this->validate($data);

        return Proveedor::create($validatedData);
    }

    /**
     * Validate the provider data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:20', 'unique:proveedores,nit'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:proveedores,email'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:100'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

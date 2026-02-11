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
     * @param array<string, mixed> $data
     * @return Proveedor
     */
    public function execute(array $data): Proveedor
    {
        $validatedData = $this->validate($data);

        return Proveedor::create($validatedData);
    }

    /**
     * Validate the provider data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'ruc' => ['required', 'string', 'max:20', 'unique:proveedors,ruc'],
            'direccion' => ['required', 'string', 'max:500'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:proveedors,email'],
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_telefono' => ['nullable', 'string', 'max:20'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'condiciones_pago' => ['nullable', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

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
     * @param Proveedor $proveedor
     * @param array<string, mixed> $data
     * @return Proveedor
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
     * @param array<string, mixed> $data
     * @param Proveedor $proveedor
     * @return array<string, mixed>
     */
    private function validate(array $data, Proveedor $proveedor): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'ruc' => ['sometimes', 'string', 'max:20', Rule::unique('proveedors', 'ruc')->ignore($proveedor->id)],
            'direccion' => ['sometimes', 'string', 'max:500'],
            'telefono' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('proveedors', 'email')->ignore($proveedor->id)],
            'contacto_nombre' => ['sometimes', 'string', 'max:255'],
            'contacto_telefono' => ['sometimes', 'string', 'max:20'],
            'contacto_email' => ['sometimes', 'email', 'max:255'],
            'dias_credito' => ['sometimes', 'integer', 'min:0'],
            'limite_credito' => ['sometimes', 'numeric', 'min:0'],
            'condiciones_pago' => ['sometimes', 'string', 'max:1000'],
            'observaciones' => ['sometimes', 'string', 'max:2000'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

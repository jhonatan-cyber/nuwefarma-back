<?php

declare(strict_types=1);

namespace App\Actions\Cliente;

use App\Models\Cliente;
use Illuminate\Validation\Rule;

class UpdateClienteAction
{
    /**
     * Update an existing client.
     *
     * @param Cliente $cliente
     * @param array<string, mixed> $data
     * @return Cliente
     */
    public function execute(Cliente $cliente, array $data): Cliente
    {
        $validatedData = $this->validate($data, $cliente);

        $cliente->update($validatedData);

        return $cliente->fresh();
    }

    /**
     * Validate the client data for update.
     *
     * @param array<string, mixed> $data
     * @param Cliente $cliente
     * @return array<string, mixed>
     */
    private function validate(array $data, Cliente $cliente): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'string', 'max:255'],
            'ci' => ['sometimes', 'string', 'max:20', Rule::unique('clientes', 'ci')->ignore($cliente->id)],
            'nit' => ['sometimes', 'string', 'max:20', Rule::unique('clientes', 'nit')->ignore($cliente->id)],
            'direccion' => ['sometimes', 'string', 'max:500'],
            'telefono' => ['sometimes', 'string', 'max:20'],
            'celular' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('clientes', 'email')->ignore($cliente->id)],
            'fecha_nacimiento' => ['sometimes', 'date'],
            'sexo' => ['sometimes', Rule::in(['M', 'F', 'O'])],
            'estado_civil' => ['sometimes', 'string', 'max:50'],
            'ocupacion' => ['sometimes', 'string', 'max:255'],
            'referencia_nombre' => ['sometimes', 'string', 'max:255'],
            'referencia_telefono' => ['sometimes', 'string', 'max:20'],
            'limite_credito' => ['sometimes', 'numeric', 'min:0'],
            'dias_credito' => ['sometimes', 'integer', 'min:0'],
            'observaciones' => ['sometimes', 'string', 'max:2000'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

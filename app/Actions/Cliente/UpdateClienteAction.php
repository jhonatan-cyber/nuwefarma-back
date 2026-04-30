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
     * @param  array<string, mixed>  $data
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Cliente $cliente): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'string', 'max:255'],
            'ci' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('clientes', 'ci')->ignore($cliente->id)],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

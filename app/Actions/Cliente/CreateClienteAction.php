<?php

declare(strict_types=1);

namespace App\Actions\Cliente;

use App\Models\Cliente;
use Illuminate\Validation\Rule;

class CreateClienteAction
{
    /**
     * Create a new client.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Cliente
    {
        $validatedData = $this->validate($data);

        return Cliente::create($validatedData);
    }

    /**
     * Validate the client data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'ci' => ['nullable', 'string', 'max:20', 'unique:clientes,ci'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

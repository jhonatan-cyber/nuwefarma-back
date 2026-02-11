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
     * @param array<string, mixed> $data
     * @return Cliente
     */
    public function execute(array $data): Cliente
    {
        $validatedData = $this->validate($data);

        return Cliente::create($validatedData);
    }

    /**
     * Validate the client data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'string', 'max:20', 'unique:clientes,ci'],
            'nit' => ['nullable', 'string', 'max:20', 'unique:clientes,nit'],
            'direccion' => ['required', 'string', 'max:500'],
            'telefono' => ['required', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clientes,email'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'estado_civil' => ['nullable', 'string', 'max:50'],
            'ocupacion' => ['nullable', 'string', 'max:255'],
            'referencia_nombre' => ['nullable', 'string', 'max:255'],
            'referencia_telefono' => ['nullable', 'string', 'max:20'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

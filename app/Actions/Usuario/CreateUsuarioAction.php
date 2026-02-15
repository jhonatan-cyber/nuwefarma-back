<?php

declare(strict_types=1);

namespace App\Actions\Usuario;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CreateUsuarioAction
{
    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Usuario
    {
        $validatedData = $this->validate($data);

        // Auto-hash CI as password if not provided
        if (! isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['ci']);
        }

        return Usuario::create($validatedData);
    }

    /**
     * Validate the user data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'string', 'max:20', 'unique:usuarios,ci'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'telefono' => ['required', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'rol_id' => ['required', 'exists:roles,id'],
            'sucursal_id' => ['nullable', 'exists:sucursals,id'],
            'sueldo' => ['nullable', 'numeric', 'min:0'],
            'foto' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

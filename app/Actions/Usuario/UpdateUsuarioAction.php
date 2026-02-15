<?php

declare(strict_types=1);

namespace App\Actions\Usuario;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateUsuarioAction
{
    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Usuario $usuario, array $data): Usuario
    {
        $validatedData = $this->validate($data, $usuario);

        // Auto-hash CI as password if CI is updated and no password provided
        if (isset($validatedData['ci']) && ! isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['ci']);
        }

        $usuario->update($validatedData);

        return $usuario->fresh()->load(['rol', 'sucursal']);
    }

    /**
     * Validate the user data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Usuario $usuario): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'apellidos' => ['sometimes', 'string', 'max:255'],
            'ci' => ['sometimes', 'string', 'max:20', Rule::unique('usuarios', 'ci')->ignore($usuario->id)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'telefono' => ['sometimes', 'string', 'max:20'],
            'direccion' => ['sometimes', 'string', 'max:500'],
            'rol_id' => ['sometimes', 'exists:roles,id'],
            'sucursal_id' => ['sometimes', 'exists:sucursals,id'],
            'sueldo' => ['sometimes', 'numeric', 'min:0'],
            'foto' => ['sometimes', 'string', 'max:255'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Rol;

use App\Models\Rol;
use Illuminate\Validation\Rule;

class CreateRolAction
{
    /**
     * Create a new role.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Rol
    {
        $validatedData = $this->validate($data);
        
        // Establecer valores por defecto
        $validatedData['estado'] = $validatedData['estado'] ?? 'activo';

        return Rol::create($validatedData);
    }

    /**
     * Validate the role data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255', 'unique:roles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'permiso_id' => ['nullable', 'array'],
            'permiso_id.*' => ['string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Rol;

use App\Models\Rol;
use Illuminate\Validation\Rule;

class UpdateRolAction
{
    /**
     * Update an existing role.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Rol $rol, array $data): Rol
    {
        $validatedData = $this->validate($data, $rol);

        $rol->update($validatedData);

        return $rol->fresh();
    }

    /**
     * Validate the role data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Rol $rol): array
    {
        $rules = [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($rol->id)],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'permiso_id' => ['sometimes', 'nullable', 'array'],
            'permiso_id.*' => ['string', 'max:255'],
            'estado' => ['sometimes', 'nullable', Rule::in(['activo', 'inactivo'])],
        ];

        return validator($data, $rules)->validate();
    }
}

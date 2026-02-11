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
     * @param Rol $rol
     * @param array<string, mixed> $data
     * @return Rol
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
     * @param array<string, mixed> $data
     * @param Rol $rol
     * @return array<string, mixed>
     */
    private function validate(array $data, Rol $rol): array
    {
        return validator($data, [
            'nombre' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'nombre')->ignore($rol->id)],
            'descripcion' => ['sometimes', 'string', 'max:1000'],
            'permiso_id' => ['sometimes', 'array'],
            'permiso_id.*' => ['string', 'max:255'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

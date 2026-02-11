<?php

declare(strict_types=1);

namespace App\Actions\Rol;

use App\Models\Rol;
use Illuminate\Validation\Rule;

class BulkUpdateRolesAction
{
    /**
     * Bulk update roles status.
     *
     * @param array<string> $ids
     * @param string $estado
     * @return int
     */
    public function execute(array $ids, string $estado): int
    {
        $this->validate(['ids' => $ids, 'estado' => $estado]);

        return Rol::whereIn('id', $ids)
            ->update(['estado' => $estado]);
    }

    /**
     * Validate bulk update data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:roles,id'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

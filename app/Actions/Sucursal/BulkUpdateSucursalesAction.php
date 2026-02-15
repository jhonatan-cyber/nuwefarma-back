<?php

declare(strict_types=1);

namespace App\Actions\Sucursal;

use App\Models\Sucursal;
use Illuminate\Validation\Rule;

class BulkUpdateSucursalesAction
{
    /**
     * Bulk update branches status.
     *
     * @param  array<string>  $ids
     */
    public function execute(array $ids, string $estado): int
    {
        $this->validate(['ids' => $ids, 'estado' => $estado]);

        return Sucursal::whereIn('id', $ids)
            ->update(['estado' => $estado]);
    }

    /**
     * Validate bulk update data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'string', 'exists:sucursals,id'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

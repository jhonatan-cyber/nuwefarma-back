<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Models\Proveedor;
use Illuminate\Validation\Rule;

class BulkUpdateProveedoresAction
{
    /**
     * Bulk update providers status.
     *
     * @param  array<string>  $ids
     */
    public function execute(array $ids, string $estado): int
    {
        $this->validate(['ids' => $ids, 'estado' => $estado]);

        return Proveedor::whereIn('id', $ids)
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
            'ids.*' => ['required', 'string', 'exists:proveedores,id'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

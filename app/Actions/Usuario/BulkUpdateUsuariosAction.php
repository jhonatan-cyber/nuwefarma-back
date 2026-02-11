<?php

declare(strict_types=1);

namespace App\Actions\Usuario;

use App\Models\Usuario;
use Illuminate\Validation\Rule;

class BulkUpdateUsuariosAction
{
    /**
     * Bulk update users status.
     *
     * @param array<string> $ids
     * @param string $estado
     * @return int
     */
    public function execute(array $ids, string $estado): int
    {
        $this->validate(['ids' => $ids, 'estado' => $estado]);

        return Usuario::whereIn('id', $ids)
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
            'ids.*' => ['required', 'string', 'exists:usuarios,id'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ])->validate();
    }
}

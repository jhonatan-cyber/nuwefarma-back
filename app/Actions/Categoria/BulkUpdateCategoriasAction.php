<?php

declare(strict_types=1);

namespace App\Actions\Categoria;

use App\Models\Categoria;

class BulkUpdateCategoriasAction
{
    /**
     * Bulk update categories status.
     *
     * @param  array<string>  $ids
     */
    public function execute(array $ids, string $estado): int
    {
        return Categoria::whereIn('id', $ids)
            ->update(['estado' => $estado]);
    }
}

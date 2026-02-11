<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Producto;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class BulkUpdateProductosAction
{
    /**
     * Bulk update products status or stock.
     *
     * @param array<string> $ids
     * @param array<string, mixed> $data
     * @return int
     */
    public function execute(array $ids, array $data): int
    {
        $this->validate(['ids' => $ids] + $data);

        return DB::transaction(function () use ($ids, $data) {
            $updateData = [];

            if (isset($data['estado'])) {
                $updateData['estado'] = $data['estado'];
            }

            if (isset($data['stock_actual'])) {
                $updateData['stock_actual'] = $data['stock_actual'];
            }

            if (isset($data['precio_venta'])) {
                $updateData['precio_venta'] = $data['precio_venta'];
            }

            return Producto::whereIn('id', $ids)
                ->update($updateData);
        });
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
            'ids.*' => ['required', 'string', 'exists:productos,id'],
            'estado' => ['sometimes', Rule::in(['activo', 'inactivo', 'descontinuado'])],
            'stock_actual' => ['sometimes', 'integer', 'min:0'],
            'precio_venta' => ['sometimes', 'numeric', 'min:0'],
        ])->validate();
    }
}

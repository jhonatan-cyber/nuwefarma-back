<?php

declare(strict_types=1);

namespace App\Actions\Compra;

use App\Exceptions\ApiException;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateCompraAction
{
    /**
     * Update an existing purchase.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Compra $compra, array $data): Compra
    {
        return DB::transaction(function () use ($compra, $data) {
            $this->validateUpdateability($compra);

            $validatedData = $this->validate($data, $compra);

            // Update purchase
            $compra->update($validatedData);

            return $compra->fresh()->load(['proveedor', 'usuario', 'caja', 'compraProductos.producto']);
        });
    }

    /**
     * Validate if purchase can be updated.
     *
     * @throws ApiException
     */
    private function validateUpdateability(Compra $compra): void
    {
        if ($compra->estado === 'cancelada') {
            throw ApiException::conflict(
                'No se puede modificar una compra cancelada',
                ['estado' => ['La compra ya está cancelada']]
            );
        }

        if ($compra->estado === 'completada') {
            throw ApiException::conflict(
                'No se puede modificar una compra completada',
                ['estado' => ['La compra ya está completada']]
            );
        }
    }

    /**
     * Validate the purchase data for update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, Compra $compra): array
    {
        return validator($data, [
            'proveedor_id' => ['sometimes', 'exists:proveedores,id'],
            'tipo_documento' => ['sometimes', Rule::in(['factura', 'boleta', 'nota_credito', 'guia_remision'])],
            'numero_documento' => ['sometimes', 'string', 'max:100'],
            'fecha_documento' => ['sometimes', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'impuesto' => ['sometimes', 'numeric', 'min:0'],
            'descuento' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'pagado' => ['sometimes', 'numeric', 'min:0'],
            'saldo_pendiente' => ['sometimes', 'numeric', 'min:0'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'completada', 'cancelada'])],
            'observaciones' => ['sometimes', 'string', 'max:1000'],
        ])->validate();
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Cotizacion;

use App\Actions\Venta\CreateVentaAction;
use App\Exceptions\ApiException;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConvertirCotizacionAction
{
    /**
     * Convert an accepted/pending quote into a completed sale.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Cotizacion $cotizacion, array $data): Venta
    {
        return DB::transaction(function () use ($cotizacion, $data) {
            $cotizacion = Cotizacion::query()->lockForUpdate()->findOrFail($cotizacion->id);

            $cotizacion->marcarVencidaSiCorresponde();

            if (! in_array($cotizacion->estado, ['en_espera', 'aceptada'], true)) {
                throw ApiException::conflict(
                    'La cotización no se puede convertir en venta',
                    ['estado' => ["La cotización está en estado: {$cotizacion->estado}"]]
                );
            }

            $validatedData = $this->validate($data);
            $cliente = $this->resolverCliente($cotizacion->cliente);

            $total = (float) $cotizacion->total;
            $tipoPago = $validatedData['tipo_pago'];
            $pagado = $tipoPago === 'contado' ? $total : 0;

            $venta = app(CreateVentaAction::class)->execute([
                'cliente_id' => $cliente->id,
                'usuario_id' => $validatedData['usuario_id'] ?? auth()->id(),
                'caja_id' => $validatedData['caja_id'],
                'sucursal_id' => $validatedData['sucursal_id'] ?? null,
                'tipo_pago' => $tipoPago,
                'metodo_pago' => $validatedData['metodo_pago'],
                'subtotal' => (float) $cotizacion->subtotal,
                'impuesto' => (float) $cotizacion->impuesto,
                'descuento' => (float) $cotizacion->descuento,
                'total' => $total,
                'pagado' => $pagado,
                'saldo_pendiente' => max(0, $total - $pagado),
                'estado' => 'completada',
                'observaciones' => "Generada a partir de la cotización {$cotizacion->numero_cotizacion}",
                'productos' => $cotizacion->productos()->get()->map(fn ($item) => [
                    'producto_id' => $item->producto_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento' => 0,
                ])->all(),
            ]);

            $cotizacion->update(['estado' => 'aceptada']);

            return $venta;
        }, 3);
    }

    /**
     * Validate conversion data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'caja_id' => ['required', 'exists:cajas,id'],
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'sucursal_id' => ['nullable', 'exists:sucursals,id'],
            'tipo_pago' => ['required', 'in:contado,credito'],
            'metodo_pago' => ['required', 'in:efectivo,tarjeta,transferencia,cheque'],
        ])->validate();
    }

    /**
     * Resolve or create a Cliente from the quote's free-text client name.
     */
    private function resolverCliente(string $nombre): Cliente
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw ApiException::conflict(
                'La cotización no tiene cliente asociado',
                ['cliente' => ['Agregue un cliente a la cotización']]
            );
        }

        $cliente = Cliente::query()
            ->where('nombre', $nombre)
            ->orWhere('apellidos', $nombre)
            ->orWhereRaw("REPLACE(CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellidos, '')), '  ', ' ') = ?", [$nombre])
            ->first();

        if ($cliente) {
            return $cliente;
        }

        $partes = explode(' ', $nombre);
        $nombreCampo = array_shift($partes);
        $apellidos = implode(' ', $partes);

        return Cliente::create([
            'ci' => 'CQ-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nombre), 0, 20)).'-'.strtoupper(Str::random(4)),
            'nombre' => $nombreCampo,
            'apellidos' => $apellidos,
            'estado' => 'activo',
        ]);
    }
}
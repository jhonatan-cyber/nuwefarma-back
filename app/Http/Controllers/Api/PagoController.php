<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pago\PagoCollection;
use App\Http\Resources\Pago\PagoResource;
use App\Models\Compra;
use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Display a paginated listing of payments.
     */
    public function index(Request $request)
    {
        $query = Pago::with(['caja', 'usuario']);

        if ($request->documento_tipo) {
            $query->where('documento_tipo', $request->documento_tipo);
        }

        if ($request->documento_numero) {
            $query->where('documento_numero', 'like', "%{$request->documento_numero}%");
        }

        if ($request->numero_pago) {
            $query->where('numero_pago', 'like', "%{$request->numero_pago}%");
        }

        if ($request->metodo_pago) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->tipo_pago) {
            $query->where('tipo_pago', $request->tipo_pago);
        }

        if ($request->caja_id) {
            $query->where('caja_id', $request->caja_id);
        }

        if ($request->fecha_inicio) {
            $query->whereDate('fecha_pago', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('fecha_pago', '<=', $request->fecha_fin);
        }

        $query->orderByDesc('fecha_pago');

        $perPage = min($request->per_page ?? 15, 100);

        return $this->success(new PagoCollection($query->paginate($perPage)));
    }

    /**
     * Display the specified payment.
     */
    public function show(Pago $pago)
    {
        return $this->success(new PagoResource($pago->load(['caja', 'usuario'])));
    }

    /**
     * Cuentas por cobrar: ventas con saldo pendiente.
     */
    public function cuentasPorCobrar(Request $request)
    {
        $ventas = Venta::with(['cliente', 'caja'])
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->where('saldo_pendiente', '>', 0)
            ->orderByDesc('fecha_venta')
            ->paginate(min($request->per_page ?? 15, 100));

        $cuentas = $ventas->getCollection()->map(function (Venta $venta) {
            $mora = $this->calcularMora($venta->fecha_vencimiento);

            return [
                'id' => $venta->id,
                'documento_tipo' => 'Venta',
                'documento_numero' => $venta->numero_venta,
                'fecha' => $venta->fecha_venta,
                'fecha_vencimiento' => $venta->fecha_vencimiento,
                'dias_vencido' => $mora['dias'],
                'estado_mora' => $mora['estado'],
                'total' => $venta->total,
                'pagado' => $venta->pagado,
                'saldo_pendiente' => $venta->saldo_pendiente,
                'estado' => $venta->estado,
                'cliente' => $venta->cliente ? [
                    'id' => $venta->cliente->id,
                    'nombre' => trim($venta->cliente->nombre.' '.($venta->cliente->apellidos ?? '')),
                    'ci' => $venta->cliente->ci,
                ] : null,
            ];
        });

        $coleccion = $ventas->getCollection();

        return $this->success([
            'data' => $cuentas->values(),
            'meta' => [
                'total' => $ventas->total(),
                'count' => $cuentas->count(),
                'per_page' => $ventas->perPage(),
                'current_page' => $ventas->currentPage(),
                'last_page' => $ventas->lastPage(),
            ],
            'resumen' => [
                'total_por_cobrar' => round((float) $coleccion->sum('saldo_pendiente'), 2),
                'cantidad_cuentas' => $cuentas->count(),
                'cuentas_vencidas' => $coleccion->filter(fn ($v) => $v->fecha_vencimiento && $v->fecha_vencimiento->lt(now()->startOfDay()))->count(),
                'monto_vencido' => round((float) $coleccion->filter(fn ($v) => $v->fecha_vencimiento && $v->fecha_vencimiento->lt(now()->startOfDay()))->sum('saldo_pendiente'), 2),
            ],
        ], 'Cuentas por cobrar');
    }

    /**
     * Cuentas por pagar: compras con saldo pendiente.
     */
    public function cuentasPorPagar(Request $request)
    {
        $compras = Compra::with(['proveedor', 'caja'])
            ->whereNotIn('estado', ['cancelada', 'devuelta'])
            ->where('saldo_pendiente', '>', 0)
            ->orderByDesc('fecha_compra')
            ->paginate(min($request->per_page ?? 15, 100));

        $cuentas = $compras->getCollection()->map(function (Compra $compra) {
            $mora = $this->calcularMora($compra->fecha_vencimiento);

            return [
                'id' => $compra->id,
                'documento_tipo' => 'Compra',
                'documento_numero' => $compra->numero_compra,
                'fecha' => $compra->fecha_compra,
                'fecha_vencimiento' => $compra->fecha_vencimiento,
                'dias_vencido' => $mora['dias'],
                'estado_mora' => $mora['estado'],
                'total' => $compra->total,
                'pagado' => $compra->pagado,
                'saldo_pendiente' => $compra->saldo_pendiente,
                'estado' => $compra->estado,
                'proveedor' => $compra->proveedor ? [
                    'id' => $compra->proveedor->id,
                    'nombre' => $compra->proveedor->nombre,
                    'nit' => $compra->proveedor->nit,
                ] : null,
            ];
        });

        $coleccion = $compras->getCollection();

        return $this->success([
            'data' => $cuentas->values(),
            'meta' => [
                'total' => $compras->total(),
                'count' => $cuentas->count(),
                'per_page' => $compras->perPage(),
                'current_page' => $compras->currentPage(),
                'last_page' => $compras->lastPage(),
            ],
            'resumen' => [
                'total_por_pagar' => round((float) $coleccion->sum('saldo_pendiente'), 2),
                'cantidad_cuentas' => $cuentas->count(),
                'cuentas_vencidas' => $coleccion->filter(fn ($c) => $c->fecha_vencimiento && $c->fecha_vencimiento->lt(now()->startOfDay()))->count(),
                'monto_vencido' => round((float) $coleccion->filter(fn ($c) => $c->fecha_vencimiento && $c->fecha_vencimiento->lt(now()->startOfDay()))->sum('saldo_pendiente'), 2),
            ],
        ], 'Cuentas por pagar');
    }

    /**
     * Calcular el estado de mora de una cuenta según su vencimiento.
     *
     * @return array{dias: int, estado: string}
     */
    private function calcularMora($fechaVencimiento): array
    {
        if (! $fechaVencimiento) {
            return ['dias' => 0, 'estado' => 'sin_vencimiento'];
        }

        $hoy = now()->startOfDay();
        $vencimiento = $fechaVencimiento instanceof \Carbon\CarbonInterface
            ? $fechaVencimiento->copy()->startOfDay()
            : \Illuminate\Support\Carbon::parse($fechaVencimiento)->startOfDay();

        if ($vencimiento->lt($hoy)) {
            return ['dias' => (int) $vencimiento->diffInDays($hoy), 'estado' => 'vencido'];
        }

        if ($vencimiento->eq($hoy)) {
            return ['dias' => 0, 'estado' => 'vence_hoy'];
        }

        return ['dias' => -(int) $hoy->diffInDays($vencimiento), 'estado' => 'al_dia'];
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibroControlado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LibroControladoController extends Controller
{
    /**
     * Listar movimientos del libro de controlados con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LibroControlado::with(['producto', 'lote', 'receta', 'paciente', 'medico', 'cajero']);

        if ($request->sucursal_id) {
            $query->where('sucursal_id', $request->sucursal_id);
        } elseif ($request->user()->sucursal_id) {
            $query->where('sucursal_id', $request->user()->sucursal_id);
        }

        if ($request->producto_id) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->fecha_inicio) {
            $query->where('created_at', '>=', $request->fecha_inicio.' 00:00:00');
        }

        if ($request->fecha_fin) {
            $query->where('created_at', '<=', $request->fecha_fin.' 23:59:59');
        }

        if ($request->tipo_operacion) {
            $query->where('tipo_operacion', $request->tipo_operacion);
        }

        $query->orderByDesc('created_at');
        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage)->through(fn ($r) => $this->formatear($r)),
        ], Response::HTTP_OK);
    }

    /**
     * Reporte regulatorio: resumen por producto y por periodo.
     */
    public function reporte(Request $request): JsonResponse
    {
        $sucursalId = $request->sucursal_id ?? $request->user()->sucursal_id;

        $query = LibroControlado::query()
            ->with(['producto']);

        if ($sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        $fechaInicio = $request->fecha_inicio ?? now()->startOfMonth()->toDateString();
        $fechaFin = $request->fecha_fin ?? now()->endOfMonth()->toDateString();

        $movimientos = $query->whereBetween('created_at', [$fechaInicio.' 00:00:00', $fechaFin.' 23:59:59'])
            ->get();

        $porProducto = $movimientos->groupBy('producto_id')->map(function ($items) {
            $producto = $items->first()->producto;

            return [
                'producto_id' => $items->first()->producto_id,
                'producto' => $producto ? $producto->nombre : 'Sin producto',
                'codigo' => $producto ? $producto->codigo_barras : null,
                'total_movimientos' => $items->count(),
                'total_unidades' => (float) $items->sum('cantidad'),
                'dispensaciones' => $items->where('tipo_operacion', LibroControlado::OPERACION_DISPENSACION)->count(),
            ];
        })->values();

        $totalUnidades = (float) $movimientos->sum('cantidad');

        return response()->json([
            'success' => true,
            'data' => [
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin,
                ],
                'resumen' => [
                    'total_movimientos' => $movimientos->count(),
                    'total_unidades' => $totalUnidades,
                    'productos' => $porProducto->count(),
                ],
                'por_producto' => $porProducto,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(LibroControlado $r): array
    {
        return [
            'id' => $r->id,
            'receta_numero' => $r->receta_numero,
            'autorizacion' => $r->autorizacion,
            'tipo_operacion' => $r->tipo_operacion,
            'cantidad' => (float) $r->cantidad,
            'observaciones' => $r->observaciones,
            'registrado_en' => $r->created_at,
            'producto' => $r->producto ? [
                'id' => $r->producto->id,
                'nombre' => $r->producto->nombre,
                'codigo_barras' => $r->producto->codigo_barras,
            ] : null,
            'lote' => $r->lote ? [
                'id' => $r->lote->id,
                'numero_lote' => $r->lote->numero_lote,
            ] : null,
            'paciente' => $r->paciente ? [
                'id' => $r->paciente->id,
                'nombre' => $r->paciente->nombre_completo,
                'ci' => $r->paciente->ci,
            ] : null,
            'medico' => $r->medico ? [
                'id' => $r->medico->id,
                'nombre' => $r->medico->nombre_completo,
                'registro_profesional' => $r->medico->registro_profesional,
            ] : null,
            'cajero' => $r->cajero ? [
                'id' => $r->cajero->id,
                'nombre' => trim($r->cajero->nombre.' '.($r->cajero->apellidos ?? '')),
            ] : null,
            'sucursal' => $r->sucursal ? [
                'id' => $r->sucursal->id,
                'nombre' => $r->sucursal->nombre,
            ] : null,
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActivityLog;
use App\Models\ArqueoCaja;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\DB;

class ArqueoCajaService
{
    public function __construct(private CajaLibroService $cajaLibro) {}

    /**
     * Realizar un arqueo de caja: se recibe el conteo físico por denominación
     * y se calcula la diferencia contra el saldo que registra el sistema.
     *
     * @param  array<string, mixed>  $data
     */
    public function realizar(Caja $caja, array $data): ArqueoCaja
    {
        return DB::transaction(function () use ($caja, $data) {
            $caja = Caja::query()->lockForUpdate()->findOrFail($caja->id);

            $validated = $this->validateRealizar($data);

            $detalles = [];
            $totalDeclarado = 0.0;

            foreach ($validated['detalles'] ?? [] as $detalle) {
                $monto = round((float) $detalle['denominacion'] * (int) $detalle['cantidad'], 2);
                $totalDeclarado += $monto;
                $detalles[] = [
                    'denominacion' => (float) $detalle['denominacion'],
                    'cantidad' => (int) $detalle['cantidad'],
                    'monto' => $monto,
                ];
            }

            $totalContado = (float) $validated['total_contado'];
            $saldoSistema = (float) $caja->saldo_actual;

            $arqueo = ArqueoCaja::create([
                'numero_arqueo' => ArqueoCaja::generateNumeroArqueo(),
                'caja_id' => $caja->id,
                'saldo_inicial' => (float) $caja->saldo_inicial,
                'total_ingresos' => (float) $caja->total_ingresos,
                'total_egresos' => (float) $caja->total_egresos,
                'saldo_sistema' => $saldoSistema,
                'total_declarado' => round($totalDeclarado, 2),
                'total_contado' => $totalContado,
                'diferencia' => round($totalContado - $saldoSistema, 2),
                'detalles' => $detalles,
                'estado' => ArqueoCaja::ESTADO_REALIZADO,
                'observaciones' => $validated['observaciones'] ?? null,
                'usuario_id' => auth('sanctum')->id(),
                'sucursal_id' => $caja->sucursal_id,
            ]);

            ActivityLog::registrar(
                'arqueo_caja',
                'Caja',
                $caja->id,
                "Arqueo {$arqueo->numero_arqueo} de la caja {$caja->numero_caja}: diferencia {$arqueo->diferencia}"
            );

            return $arqueo->fresh()->load(['caja', 'usuario']);
        }, 3);
    }

    /**
     * Conciliar un arqueo: si existe diferencia, se registra un ajuste de
     * caja para dejar el saldo contable igual al conteo físico.
     *
     * @param  array<string, mixed>  $data
     */
    public function conciliar(ArqueoCaja $arqueo, array $data): ArqueoCaja
    {
        return DB::transaction(function () use ($arqueo, $data) {
            $arqueo = ArqueoCaja::query()->lockForUpdate()->findOrFail($arqueo->id);

            if ($arqueo->estado === ArqueoCaja::ESTADO_CONCILIADO) {
                throw ApiException::conflict(
                    'El arqueo ya está conciliado',
                    ['estado' => ['El arqueo ya fue conciliado']]
                );
            }

            $validated = validator($data, [
                'observaciones' => ['nullable', 'string', 'max:1000'],
            ])->validate();

            $diferencia = (float) $arqueo->diferencia;

            if ($diferencia != 0) {
                $concepto = 'Ajuste de arqueo '.$arqueo->numero_arqueo;

                if ($diferencia > 0) {
                    $movimiento = $this->cajaLibro->ingreso(
                        $arqueo->caja_id,
                        abs($diferencia),
                        MovimientoCaja::ORIGEN_AJUSTE_ARQUEO,
                        'ArqueoCaja',
                        $arqueo->id,
                        $arqueo->numero_arqueo,
                        $concepto
                    );
                } else {
                    $movimiento = $this->cajaLibro->egreso(
                        $arqueo->caja_id,
                        abs($diferencia),
                        MovimientoCaja::ORIGEN_AJUSTE_ARQUEO,
                        'ArqueoCaja',
                        $arqueo->id,
                        $arqueo->numero_arqueo,
                        $concepto
                    );
                }

                $arqueo->saldo_sistema = $movimiento ? (float) $movimiento->saldo_despues : $arqueo->saldo_sistema;
            }

            $arqueo->update([
                'estado' => ArqueoCaja::ESTADO_CONCILIADO,
                'observaciones' => $validated['observaciones'] ?? $arqueo->observaciones,
                'fecha_cierre' => now(),
            ]);

            ActivityLog::registrar(
                'conciliar_arqueo',
                'Caja',
                $arqueo->caja_id,
                "Arqueo {$arqueo->numero_arqueo} conciliado"
            );

            return $arqueo->fresh()->load(['caja', 'usuario']);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateRealizar(array $data): array
    {
        return validator($data, [
            'detalles' => ['nullable', 'array'],
            'detalles.*.denominacion' => ['required_with:detalles', 'numeric', 'gt:0'],
            'detalles.*.cantidad' => ['required_with:detalles', 'integer', 'min:0'],
            'total_contado' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ])->validate();
    }
}
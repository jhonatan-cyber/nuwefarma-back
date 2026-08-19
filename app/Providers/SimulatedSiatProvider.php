<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\FiscalProvider;
use App\Models\PuntoVenta;
use Illuminate\Support\Str;

/**
 * Proveedor simulado del SIAT.
 *
 * Genera códigos deterministas (CUIS, CUFD, CUF, autorización y QR) para
 * operar con facturación simulada sin contacto con el SIN. Está listo para
 * ser sustituido por un adaptador real sin modificar el dominio.
 */
class SimulatedSiatProvider implements FiscalProvider
{
    public function solicitarCuis(PuntoVenta $puntoVenta): array
    {
        $codigo = $this->hash26('CUIS', $puntoVenta->codigo_poa, now()->format('Y'));

        return [
            'codigo' => $codigo,
            'codigo_control' => $this->codigoControl($codigo),
            'transaccion' => 0,
            'fecha_vigencia' => now()->toDateString(),
            'ambiente' => $puntoVenta->ambiente ?? config('siat.ambiente', 'pruebas'),
        ];
    }

    public function solicitarCufd(PuntoVenta $puntoVenta): array
    {
        $codigo = $this->hash26('CUFD', $puntoVenta->codigo_poa, now()->format('Ymd'));

        return [
            'codigo' => $codigo,
            'codigo_control' => $this->codigoControl($codigo),
            'transaccion' => 0,
            'fecha_vigencia' => now()->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function emitir(array $datos): array
    {
        $fechaEmision = $datos['fecha_emision'];
        $numeroFactura = str_pad((string) $datos['numero_factura'], 5, '0', STR_PAD_LEFT);
        $nit = str_pad((string) $datos['nit'], 13, '0', STR_PAD_LEFT);
        $cufd = $datos['cufd'];
        $cuis = $datos['cuis'];

        // El CUF del SIAT es el MD5 de la concatenación sin separadores.
        $cuf = md5(
            $nit
            .$fechaEmision // YYYYMMDDHHMMSSsss (17 dígitos con milésimas)
            .$numeroFactura
            .'00' // código sucursal
            .str_pad((string) $datos['codigo_poa'], 4, '0', STR_PAD_LEFT) // punto de venta
            .($datos['tipo_emision'] === 'contingencia' ? '2' : '1')
            .'01' // tipo documento sector: factura con derecho a crédito
            .'0' // número de autorización (regla general facturas)
            .$datos['tipo_factura']
            .'0' // nulo
            .'0' // número duplicado
            .$cufd
        );

        $numeroAutorizacion = $this->hashNombre('AUT', $cuf);

        return [
            'cuf' => $cuf,
            'codigo_control' => $this->codigoControl($cuf),
            'numero_autorizacion' => $numeroAutorizacion,
            'tipo_emision' => $datos['tipo_emision'],
            'transaccion' => 0,
            'cufd' => $cufd,
            'cuis' => $cuis,
            'leyenda' => config('siat.leyenda'),
            'fecha_emision' => $fechaEmision,
            'qr' => $this->qr($datos, $cuf),
            'respuesta' => [
                'estado' => 'emitida',
                'codigo_descripcion' => 'EMITIDA',
            ],
        ];
    }

    public function anular(PuntoVenta $puntoVenta, string $cuf, string $codigoMotivo, string $descripcion): array
    {
        return [
            'estado' => 'anulada',
            'codigo_respuesta' => $codigoMotivo,
            'descripcion' => $descripcion,
            'transaccion' => 0,
        ];
    }

    public function consultar(string $cuf): array
    {
        return [
            'estado' => 'emitida',
            'codigo_respuesta' => '0',
            'descripcion' => 'Documento vigente ante SIAT (simulado)',
        ];
    }

    public function reversionAnulacion(PuntoVenta $puntoVenta, string $cuf): array
    {
        return [
            'estado' => 'emitida',
            'codigo_respuesta' => '0',
            'descripcion' => 'Anulación revertida (simulado)',
        ];
    }

    public function esSimulado(): bool
    {
        return true;
    }

    /**
     * Código alfanumérico determinista de 26 caracteres (CUIS/CUFD).
     */
    private function hash26(string $prefijo, string $semilla, string $fecha): string
    {
        return strtoupper(Str::substr(md5($prefijo.'|'.$semilla.'|'.$fecha), 0, 26));
    }

    /**
     * Código de control de 8 caracteres derivado de un código.
     */
    private function codigoControl(string $codigo): string
    {
        return strtoupper(Str::substr(md5($codigo.'NuweFarma'), 0, 8));
    }

    /**
     * Número de autorización simulado (30 caracteres) como lo emite el SIN.
     */
    private function hashNombre(string $prefijo, string $base): string
    {
        return $prefijo.Str::upper(Str::substr(md5($base, true), 0, 10));
    }

    /**
     * Construir la cadena QR con los datos resumidos de la factura.
     *
     * @param  array<string, mixed>  $datos
     */
    private function qr(array $datos, string $cuf): string
    {
        $cliente = $datos['nit_cliente'] ?? '0';

        return implode('|', [
            $datos['nit'],
            $datos['razon_social'],
            $datos['numero_factura'],
            $cuf,
            $datos['fecha_emision'],
            $datos['monto_total'],
            $cliente,
            $datos['cuis'],
            $datos['cufd'],
            '1.0',
        ]);
    }
}
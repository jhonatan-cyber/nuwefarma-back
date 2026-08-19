<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\PuntoVenta;

/**
 * Contrato desacoplado del domino fiscal boliviano (SIAT).
 *
 * La implementación concreta puede ser local (simulada), un SDK oficial
 * del SIN o un servicio externo. Los métodos reciben y devuelven estructuras
 * planas para que el dominio no dependa de un proveedor específico.
 */
interface FiscalProvider
{
    /**
     * Obtener/renovar el CUIS (Código Único de Identificación del Sistema)
     * para un punto de venta.
     *
     * @return array{codigo: string, codigo_control: string, transaccion: int, fecha_vigencia: string, ambiente: string}|array<string, mixed>
     */
    public function solicitarCuis(PuntoVenta $puntoVenta): array;

    /**
     * Obtener/renovar el CUFD (Código Único de Facturación Diaria).
     *
     * @return array{codigo: string, codigo_control: string, transaccion: int, fecha_vigencia: string}|array<string, mixed>
     */
    public function solicitarCufd(PuntoVenta $puntoVenta): array;

    /**
     * Emitir una factura anexando el CUF generado y la leyenda del SIAT.
     *
     * @param  array<string, mixed>  $datos  Estructura normalizada de la factura.
     * @return array{cuf: string, numero_factura: string, cufd: string, cuis: string, leyenda: string, qr: string, transaccion: int}|array<string, mixed>
     */
    public function emitir(array $datos): array;

    /**
     * Anular un documento ya emitido.
     *
     * @return array{codigo_respuesta: string, descripcion: string, transaccion: int}|array<string, mixed>
     */
    public function anular(PuntoVenta $puntoVenta, string $cuf, string $codigoMotivo, string $descripcion): array;

    /**
     * Consultar el estado de un documento fiscal ante el SIAT.
     *
     * @return array{estado: string, codigo_respuesta: string, descripcion: string}|array<string, mixed>
     */
    public function consultar(string $cuf): array;

    /**
     * Reversión de anulación (devolver un documento anulado a vigencia).
     *
     * @return array{estado: string, codigo_respuesta: string, descripcion: string}|array<string, mixed>
     */
    public function reversionAnulacion(PuntoVenta $puntoVenta, string $cuf): array;

    /**
     * Indicar si la operación se está simulando (sin contacto con SIAT real).
     */
    public function esSimulado(): bool;
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reportes PDF', description: 'Generación de reportes en PDF')]
class PdfController extends Controller
{
    private PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    #[OA\Get(
        path: '/api/reportes/ventas/pdf',
        summary: 'Generar PDF de ventas',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'fecha_inicio', in: 'query'),
            new OA\Parameter(name: 'fecha_fin', in: 'query'),
            new OA\Parameter(name: 'estado', in: 'query'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de ventas'),
        ]
    )]
    public function reporteVentas(Request $request): Response
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'estado']);

        $pdf = $this->pdfService->generarReporteVentas($filtros);

        return $pdf->download('reporte_ventas_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/compras/pdf',
        summary: 'Generar PDF de compras',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'fecha_inicio', in: 'query'),
            new OA\Parameter(name: 'fecha_fin', in: 'query'),
            new OA\Parameter(name: 'estado', in: 'query'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de compras'),
        ]
    )]
    public function reporteCompras(Request $request): Response
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'estado']);

        $pdf = $this->pdfService->generarReporteCompras($filtros);

        return $pdf->download('reporte_compras_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/inventario/pdf',
        summary: 'Generar PDF de inventario',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query'),
            new OA\Parameter(name: 'producto_id', in: 'query'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de inventario'),
        ]
    )]
    public function reporteInventario(Request $request): Response
    {
        $filtros = $request->only(['estado', 'producto_id']);

        $pdf = $this->pdfService->generarReporteInventario($filtros);

        return $pdf->download('reporte_inventario_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/kardex/pdf/{loteId}',
        summary: 'Generar PDF de kardex',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'loteId', in: 'path', required: true),
            new OA\Parameter(name: 'fecha_inicio', in: 'query'),
            new OA\Parameter(name: 'fecha_fin', in: 'query'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de kardex'),
        ]
    )]
    public function reporteKardex(string $loteId, Request $request): Response
    {
        $pdf = $this->pdfService->generarReporteKardex(
            $loteId,
            $request->fecha_inicio,
            $request->fecha_fin
        );

        return $pdf->download('kardex_lote_'.$loteId.'_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/stock-bajo/pdf',
        summary: 'Generar PDF de stock bajo',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        responses: [
            new OA\Response(response: 200, description: 'PDF de stock bajo'),
        ]
    )]
    public function reporteStockBajo(): Response
    {
        $pdf = $this->pdfService->generarReporteStockBajo();

        return $pdf->download('reporte_stock_bajo_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/proximos-vencer/pdf',
        summary: 'Generar PDF de productos próximos a vencer',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'dias', in: 'query', description: 'Días de alerta'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de próximos a vencer'),
        ]
    )]
    public function reporteProximosVencer(Request $request): Response
    {
        $dias = $request->get('dias', 60);

        $pdf = $this->pdfService->generarReporteProximosVencer($dias);

        return $pdf->download('reporte_proximos_vencer_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/movimientos/pdf',
        summary: 'Generar PDF de movimientos',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'fecha_inicio', in: 'query'),
            new OA\Parameter(name: 'fecha_fin', in: 'query'),
            new OA\Parameter(name: 'tipo_movimiento', in: 'query'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de movimientos'),
        ]
    )]
    public function reporteMovimientos(Request $request): Response
    {
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'tipo_movimiento']);

        $pdf = $this->pdfService->generarReporteMovimientos($filtros);

        return $pdf->download('reporte_movimientos_'.date('Ymd_His').'.pdf');
    }

    #[OA\Get(
        path: '/api/ventas/{id}/comprobante/pdf',
        summary: 'Generar comprobante PDF de venta',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comprobante PDF'),
        ]
    )]
    public function comprobanteVenta(string $id): Response
    {
        $pdf = $this->pdfService->generarComprobanteVenta($id);

        return $pdf->download('comprobante_venta_'.$id.'.pdf');
    }

    #[OA\Get(
        path: '/api/compras/{id}/comprobante/pdf',
        summary: 'Generar comprobante PDF de compra',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comprobante PDF'),
        ]
    )]
    public function comprobanteCompra(string $id): Response
    {
        $pdf = $this->pdfService->generarComprobanteCompra($id);

        return $pdf->download('comprobante_compra_'.$id.'.pdf');
    }

    #[OA\Get(
        path: '/api/reportes/cotizacion/pdf',
        summary: 'Generar PDF de cotización',
        security: [['bearerAuth' => []]],
        tags: ['Reportes PDF'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', required: true, description: 'ID de la cotización'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PDF de cotización'),
        ]
    )]
    public function reporteCotizacion(Request $request): Response
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json(['error' => 'ID de cotización requerido'], 400);
        }

        $pdf = $this->pdfService->generarReporteCotizacion($id);

        return $pdf->download('cotizacion_'.$id.'.pdf');
    }
}

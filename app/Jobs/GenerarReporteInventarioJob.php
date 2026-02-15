<?php

namespace App\Jobs;

use App\Services\ProductoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerarReporteInventarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $usuarioId,
        private array $filtros = [],
        private string $formato = 'pdf'
    ) {
        $this->onQueue('reportes');
    }

    /**
     * Execute the job.
     */
    public function handle(ProductoService $productoService): void
    {
        try {
            Log::info('Iniciando generación de reporte de inventario', [
                'usuario_id' => $this->usuarioId,
                'filtros' => $this->filtros,
                'formato' => $this->formato,
            ]);

            // Obtener datos del reporte
            $datos = $this->obtenerDatosReporte($productoService);

            // Generar archivo del reporte
            $archivo = $this->generarArchivoReporte($datos);

            // Guardar archivo y notificar
            $ruta = $this->guardarReporte($archivo);

            // Enviar notificación al usuario
            $this->notificarUsuario($ruta);

            Log::info('Reporte de inventario generado exitosamente', [
                'usuario_id' => $this->usuarioId,
                'ruta' => $ruta,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando reporte de inventario', [
                'usuario_id' => $this->usuarioId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Fallo en generación de reporte de inventario', [
            'usuario_id' => $this->usuarioId,
            'error' => $exception->getMessage(),
        ]);

        // Notificar al usuario sobre el fallo
        $this->notificarFallo($exception);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['reportes', 'inventario', "usuario_{$this->usuarioId}"];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\RateLimitReportes];
    }

    /**
     * Obtener datos para el reporte
     */
    private function obtenerDatosReporte(ProductoService $productoService): array
    {
        $productos = $productoService->getProductos($this->filtros, ['campo' => 'nombre', 'direccion' => 'asc'], 1000);

        return [
            'productos' => $productos->items(),
            'totales' => [
                'cantidad' => $productos->total(),
                'valor_total' => $productos->items()->sum(function ($producto) {
                    return $producto->stock_actual * $producto->precio_venta;
                }),
                'bajo_stock' => $productos->items()->filter(fn ($p) => $p->bajo_stock)->count(),
                'proximos_vencer' => $productos->items()->filter(fn ($p) => $p->proximo_vencer)->count(),
            ],
            'fecha_generacion' => now()->toDateTimeString(),
            'filtros_aplicados' => $this->filtros,
        ];
    }

    /**
     * Generar archivo del reporte según formato
     */
    private function generarArchivoReporte(array $datos): string
    {
        return match ($this->formato) {
            'pdf' => $this->generarPDF($datos),
            'excel' => $this->generarExcel($datos),
            'csv' => $this->generarCSV($datos),
            default => throw new \InvalidArgumentException("Formato no soportado: {$this->formato}"),
        };
    }

    /**
     * Generar PDF del reporte
     */
    private function generarPDF(array $datos): string
    {
        $pdf = \PDF::loadView('reportes.inventario', compact('datos'));

        return $pdf->output();
    }

    /**
     * Generar Excel del reporte
     */
    private function generarExcel(array $datos): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->fromArray([
            ['ID', 'Nombre', 'Categoría', 'Stock Actual', 'Stock Mínimo', 'Precio Venta', 'Valor Total', 'Estado'],
        ], null, 'A1');

        // Data
        $row = 2;
        foreach ($datos['productos'] as $producto) {
            $sheet->fromArray([
                [
                    $producto->id,
                    $producto->nombre,
                    $producto->categoria?->nombre ?? 'N/A',
                    $producto->stock_actual,
                    $producto->stock_minimo,
                    $producto->precio_venta,
                    $producto->stock_actual * $producto->precio_venta,
                    $producto->estado_label,
                ],
            ], null, "A{$row}");
            $row++;
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

        return $writer->save('php://output/temp.xlsx');
    }

    /**
     * Generar CSV del reporte
     */
    private function generarCSV(array $datos): string
    {
        $csv = \League\Csv\Writer::createFromPath('php://output/temp.csv');

        $csv->insertOne([
            'ID', 'Nombre', 'Categoría', 'Stock Actual', 'Stock Mínimo', 'Precio Venta', 'Valor Total', 'Estado',
        ]);

        foreach ($datos['productos'] as $producto) {
            $csv->insertOne([
                $producto->id,
                $producto->nombre,
                $producto->categoria?->nombre ?? 'N/A',
                $producto->stock_actual,
                $producto->stock_minimo,
                $producto->precio_venta,
                $producto->stock_actual * $producto->precio_venta,
                $producto->estado_label,
            ]);
        }

        return file_get_contents('php://output/temp.csv');
    }

    /**
     * Guardar reporte en storage
     */
    private function guardarReporte(string $contenido): string
    {
        $filename = "reportes/inventario/inventario_{$this->usuarioId}_".now()->format('Y-m-d_H-i-s').".{$this->formato}";

        Storage::put($filename, $contenido);

        return $filename;
    }

    /**
     * Notificar al usuario que el reporte está listo
     */
    private function notificarUsuario(string $ruta): void
    {
        $usuario = \App\Models\Usuario::find($this->usuarioId);

        if ($usuario) {
            \App\Models\Notificacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Reporte de Inventario Generado',
                'mensaje' => "Tu reporte de inventario en formato {$this->formato} ha sido generado exitosamente.",
                'tipo' => 'reporte',
                'datos' => [
                    'ruta' => $ruta,
                    'formato' => $this->formato,
                    'fecha_generacion' => now()->toDateTimeString(),
                ],
                'leida' => false,
            ]);
        }
    }

    /**
     * Notificar al usuario sobre el fallo
     */
    private function notificarFallo(\Throwable $exception): void
    {
        $usuario = \App\Models\Usuario::find($this->usuarioId);

        if ($usuario) {
            \App\Models\Notificacion::create([
                'usuario_id' => $usuario->id,
                'titulo' => 'Error en Generación de Reporte',
                'mensaje' => 'Ocurrió un error al generar tu reporte de inventario. Por favor, intenta nuevamente.',
                'tipo' => 'error',
                'datos' => [
                    'error' => $exception->getMessage(),
                    'timestamp' => now()->toDateTimeString(),
                ],
                'leida' => false,
            ]);
        }
    }
}

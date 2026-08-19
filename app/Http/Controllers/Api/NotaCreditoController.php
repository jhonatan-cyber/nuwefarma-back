<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotaCredito\NotaCreditoCollection;
use App\Http\Resources\NotaCredito\NotaCreditoResource;
use App\Models\NotaCredito;
use App\Services\NotaCreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaCreditoController extends Controller
{
    public function __construct(private NotaCreditoService $notaCreditoService) {}

    /**
     * Display a paginated listing of credit notes.
     */
    public function index(Request $request)
    {
        $query = NotaCredito::with(['usuario']);

        if ($request->estado && in_array($request->estado, ['emitida', 'aplicada', 'anulada'], true)) {
            $query->where('estado', $request->estado);
        }

        if ($request->documento_tipo) {
            $query->where('documento_tipo', $request->documento_tipo);
        }

        if ($request->documento_numero) {
            $query->where('documento_numero', 'like', "%{$request->documento_numero}%");
        }

        if ($request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        $query->orderByDesc('created_at');

        $perPage = min($request->per_page ?? 15, 100);

        return $this->success(new NotaCreditoCollection($query->paginate($perPage)));
    }

    /**
     * Display the specified credit note.
     */
    public function show(NotaCredito $notaCredito)
    {
        return $this->success(new NotaCreditoResource($notaCredito->load(['usuario'])));
    }

    /**
     * Apply a credit note against a document's outstanding balance.
     */
    public function aplicar(Request $request, NotaCredito $notaCredito)
    {
        $validated = $request->validate([
            'documento_tipo' => ['required', 'in:Venta,Compra'],
            'documento_id' => ['required', 'string'],
        ]);

        $modelo = $validated['documento_tipo'] === 'Venta' ? \App\Models\Venta::class : \App\Models\Compra::class;
        $documento = $modelo::query()->find($validated['documento_id']);

        if (! $documento) {
            return $this->error(
                'Documento no encontrado',
                ['documento_id' => ['No existe el documento indicado']],
                Response::HTTP_NOT_FOUND
            );
        }

        $nota = DB::transaction(fn () => $this->notaCreditoService->aplicar($notaCredito, $documento));

        return $this->success(
            new NotaCreditoResource($nota->load(['usuario'])),
            'Nota de crédito aplicada exitosamente'
        );
    }

    /**
     * Void a credit note.
     */
    public function anular(Request $request, NotaCredito $notaCredito)
    {
        $validated = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $nota = $this->notaCreditoService->anular($notaCredito, $validated['motivo'] ?? null);

        return $this->success(
            new NotaCreditoResource($nota->load(['usuario'])),
            'Nota de crédito anulada exitosamente'
        );
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Cliente::query();

            // Filtros
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('apellido', 'like', "%{$search}%")
                      ->orWhere('ci', 'like', "%{$search}%")
                      ->orWhere('telefono', 'like', "%{$search}%");
                });
            }

            if ($request->has('estado') && !empty($request->estado)) {
                $query->where('estado', $request->estado);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $clientes = $query->paginate($perPage);

            return response()->json($clientes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ci' => 'nullable|string|max:20|unique:clientes,ci',
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'estado' => 'sometimes|in:activo,inactivo',
            ]);

            $cliente = Cliente::create($validated);

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => Auth::id(),
                'accion' => 'crear',
                'modulo' => 'clientes',
                'descripcion' => "Cliente creado: {$cliente->nombre_completo}",
                'datos_anteriores' => null,
                'datos_nuevos' => $cliente->toArray(),
            ]);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente): JsonResponse
    {
        try {
            return response()->json([
                'data' => $cliente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cliente $cliente): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ci' => [
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('clientes', 'ci')->ignore($cliente->id)
                ],
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'estado' => 'sometimes|in:activo,inactivo',
            ]);

            $datosAnteriores = $cliente->toArray();
            $cliente->update($validated);

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => Auth::id(),
                'accion' => 'actualizar',
                'modulo' => 'clientes',
                'descripcion' => "Cliente actualizado: {$cliente->nombre_completo}",
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $cliente->fresh()->toArray(),
            ]);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'data' => $cliente
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cliente $cliente): JsonResponse
    {
        try {
            $datosAnteriores = $cliente->toArray();
            $nombreCompleto = $cliente->nombre_completo;
            
            $cliente->delete();

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => Auth::id(),
                'accion' => 'eliminar',
                'modulo' => 'clientes',
                'descripcion' => "Cliente eliminado: {$nombreCompleto}",
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => null,
            ]);

            return response()->json([
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle client status
     */
    public function toggleEstado(Cliente $cliente): JsonResponse
    {
        try {
            $estadoAnterior = $cliente->estado;
            $nuevoEstado = $cliente->estado === 'activo' ? 'inactivo' : 'activo';
            
            $datosAnteriores = $cliente->toArray();
            $cliente->update(['estado' => $nuevoEstado]);

            // Registrar actividad
            ActivityLog::create([
                'usuario_id' => Auth::id(),
                'accion' => 'cambiar_estado',
                'modulo' => 'clientes',
                'descripcion' => "Estado del cliente {$cliente->nombre_completo} cambiado de {$estadoAnterior} a {$nuevoEstado}",
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $cliente->fresh()->toArray(),
            ]);

            return response()->json([
                'message' => "Cliente {$nuevoEstado} exitosamente",
                'data' => $cliente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get client statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Cliente::count(),
                'activos' => Cliente::where('estado', 'activo')->count(),
                'inactivos' => Cliente::where('estado', 'inactivo')->count(),
                'con_ci' => Cliente::whereNotNull('ci')->count(),
                'con_telefono' => Cliente::whereNotNull('telefono')->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
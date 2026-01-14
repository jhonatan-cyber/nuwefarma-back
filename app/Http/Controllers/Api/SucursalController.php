<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class SucursalController extends Controller
{
    #[OA\Get(
        path: '/api/sucursales',
        summary: 'Listar todas las sucursales',
        tags: ['Sucursales'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de sucursales',
            ),
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Sucursal::with('gerente');

            // Filtro por búsqueda
            if ($request->has('q')) {
                $search = $request->input('q');
                $query->where('nombre', 'like', "%$search%")
                    ->orWhere('direccion', 'like', "%$search%")
                    ->orWhere('ciudad', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            }

            // Filtro por estado
            if ($request->has('estado') && $request->input('estado') !== 'todos') {
                $query->where('estado', $request->input('estado'));
            }

            $sucursales = $query->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $sucursales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursales: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/sucursales',
        summary: 'Crear una nueva sucursal',
        tags: ['Sucursales'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'direccion', type: 'string'),
                    new OA\Property(property: 'ciudad', type: 'string'),
                    new OA\Property(property: 'pais', type: 'string'),
                    new OA\Property(property: 'telefono', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'gerente_id', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string'),
                ],
                required: ['nombre', 'direccion']
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Sucursal creada'),
            new OA\Response(response: 422, description: 'Validación fallida'),
        ]
    )]
    public function store(Request $request)
    {
        try {
            // Normalizar strings vacíos a null para campos opcionales
            $data = collect($request->all())->map(function ($v) {
                return $v === '' ? null : $v;
            })->toArray();

            $validated = validator($data, [
                'nombre' => 'required|string|unique:sucursals',
                'direccion' => 'required|string',
                'ciudad' => 'nullable|string',
                'pais' => 'nullable|string',
                'telefono' => 'nullable|string',
                'email' => 'nullable|email',
                'gerente_id' => 'nullable|uuid|exists:usuarios,id',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|in:activo,inactivo',
            ])->validate();

            $validated['estado'] = $validated['estado'] ?? 'activo';
            $sucursal = Sucursal::create($validated);

            return response()->json([
                'success' => true,
                'data' => $sucursal,
                'message' => 'Sucursal creada correctamente',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la sucursal: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/sucursales/{id}',
        summary: 'Obtener una sucursal específica',
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sucursal encontrada'),
            new OA\Response(response: 404, description: 'Sucursal no encontrada'),
        ]
    )]
    public function show(Sucursal $sucursal)
    {
        try {
            $sucursal->load(['usuarios.rol', 'gerente']);

            $usuariosTotal = $sucursal->usuarios->count();
            $usuariosActivos = $sucursal->usuarios->where('estado', 'activo')->count();

            $sucursal->setAttribute('usuarios_count', $usuariosTotal);
            $sucursal->setAttribute('usuarios_activos', $usuariosActivos);
            $sucursal->setAttribute('usuarios_inactivos', $usuariosTotal - $usuariosActivos);

            return response()->json([
                'success' => true,
                'data' => $sucursal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la sucursal: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/sucursales/{id}',
        summary: 'Actualizar una sucursal',
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent()
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sucursal actualizada'),
            new OA\Response(response: 404, description: 'Sucursal no encontrada'),
        ]
    )]
    public function update(Request $request, Sucursal $sucursal)
    {
        try {
            $user = Auth::user();
            
            // Validar autenticación
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                ], 401);
            }

            // Cargar la relación de rol
            $user->load('rol');
            
            $rolNombre = $user->rol ? strtolower($user->rol->nombre) : '';
            Log::info("Intento de editar sucursal usuario: {$user->email}, rol: {$rolNombre}, sucursal_user: {$user->sucursal_id}, sucursal_target: {$sucursal->id}");

            // Validar permiso: Administrador puede editar cualquier sucursal, Gerente solo la suya
            if ($rolNombre === 'gerente') {
                // Es un Gerente - solo puede editar su propia sucursal
                // NOTA: Si el gerente no tiene sucursal asignada (null), no puede editar ninguna.
                if (!$user->sucursal_id || $user->sucursal_id !== $sucursal->id) {
                    Log::warning("Gerente sin permiso: sucursal mismatch");
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para editar esta sucursal (No asignada a tu usuario)',
                    ], 403);
                }
            } else if ($rolNombre !== 'administrador') {
                // No es ni Administrador ni Gerente
                Log::warning("Usuario sin rol permitido: {$rolNombre}");
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar sucursales',
                ], 403);
            }

            // Normalizar strings vacíos a null para campos opcionales
            $data = collect($request->all())->map(function ($v) {
                return $v === '' ? null : $v;
            })->toArray();

            $validated = validator($data, [
                'nombre' => [
                    'sometimes',
                    'required',
                    'string',
                    Rule::unique('sucursals', 'nombre')->ignore($sucursal->id),
                ],
                'direccion' => 'sometimes|required|string',
                'ciudad' => 'nullable|string',
                'pais' => 'nullable|string',
                'telefono' => 'nullable|string',
                'email' => 'nullable|email',
                'gerente_id' => 'nullable|uuid|exists:usuarios,id',
                'descripcion' => 'nullable|string',
                'estado' => 'nullable|in:activo,inactivo',
            ])->validate();

            $sucursal->update($validated);

            return response()->json([
                'success' => true,
                'data' => $sucursal,
                'message' => 'Sucursal actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error on sucursal update', [
                'sucursal_id' => $sucursal->id,
                'data' => $data ?? [],
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la sucursal: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/sucursales/{id}',
        summary: 'Eliminar una sucursal',
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sucursal eliminada'),
            new OA\Response(response: 404, description: 'Sucursal no encontrada'),
        ]
    )]
    public function destroy(Sucursal $sucursal)
    {
        try {
            $sucursal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sucursal eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la sucursal: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/sucursales/{id}/toggle-estado',
        summary: 'Cambiar estado de una sucursal',
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estado actualizado'),
            new OA\Response(response: 404, description: 'Sucursal no encontrada'),
        ]
    )]
    public function toggleEstado(Sucursal $sucursal)
    {
        try {
            $sucursal->estado = $sucursal->estado === 'activo' ? 'inactivo' : 'activo';
            $sucursal->save();

            return response()->json([
                'success' => true,
                'data' => $sucursal,
                'message' => 'Estado actualizado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar si una sucursal tiene un gerente asignado
     */
    public function hasGerente($id)
    {
        try {
            $sucursal = Sucursal::findOrFail($id);
            
            // Buscar si hay un usuario con rol de Gerente asignado a esta sucursal y activo
            $GerenteRol = \App\Models\Rol::where('nombre', 'Gerente')->first();
            
            if (!$GerenteRol) {
                return response()->json([
                    'success' => true,
                    'has_gerente' => false,
                ]);
            }

            $gerente = \App\Models\Usuario::where('sucursal_id', $id)
                ->where('rol_id', $GerenteRol->id)
                ->where('estado', 'activo')
                ->first();

            return response()->json([
                'success' => true,
                'has_gerente' => $gerente !== null,
                'gerente' => $gerente ? [
                    'id' => $gerente->id,
                    'nombre' => $gerente->nombre . ' ' . $gerente->apellidos,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar gerente: ' . $e->getMessage(),
            ], 500);
        }
    }
}

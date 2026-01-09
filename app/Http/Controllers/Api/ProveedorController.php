<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Proveedor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Proveedores', description: 'Gestión de proveedores')]
class ProveedorController extends Controller
{
    #[OA\Get(
        path: '/api/proveedores',
        summary: 'Listar proveedores',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Buscar por nombre, NIT, email o contacto', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo'])),
            new OA\Parameter(name: 'ciudad', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'pais', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista de proveedores')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Proveedor::query();

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($ciudad = $request->query('ciudad')) {
            $query->where('ciudad', $ciudad);
        }

        if ($pais = $request->query('pais')) {
            $query->where('pais', $pais);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('nit', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contacto', 'like', "%{$search}%");
            });
        }

        $proveedores = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $proveedores,
        ]);
    }

    #[OA\Post(
        path: '/api/proveedores',
        summary: 'Crear proveedor',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [new OA\Response(response: 201, description: 'Proveedor creado'), new OA\Response(response: 422, description: 'Error de validación')]
    )]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateData($request);

            $proveedor = Proveedor::create($validated);

            ActivityLog::registrar(
                accion: 'create',
                modulo: 'proveedores',
                registroId: $proveedor->id,
                descripcion: "Proveedor creado: {$proveedor->nombre}",
                datosNuevos: $proveedor->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Get(
        path: '/api/proveedores/{id}',
        summary: 'Ver proveedor',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Detalle de proveedor'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function show(string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $proveedor,
        ]);
    }

    #[OA\Put(
        path: '/api/proveedores/{id}',
        summary: 'Actualizar proveedor',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Proveedor actualizado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ], 404);
        }

        try {
            $validated = $this->validateData($request, $proveedor->id);

            $datosAnteriores = $proveedor->toArray();
            $proveedor->update($validated);

            ActivityLog::registrar(
                accion: 'update',
                modulo: 'proveedores',
                registroId: $proveedor->id,
                descripcion: "Proveedor actualizado: {$proveedor->nombre}",
                datosAnteriores: $datosAnteriores,
                datosNuevos: $proveedor->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    #[OA\Delete(
        path: '/api/proveedores/{id}',
        summary: 'Eliminar proveedor',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Proveedor eliminado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function destroy(string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ], 404);
        }

        $datosAnteriores = $proveedor->toArray();
        $proveedor->delete();

        ActivityLog::registrar(
            accion: 'delete',
            modulo: 'proveedores',
            registroId: $id,
            descripcion: "Proveedor eliminado: {$datosAnteriores['nombre']}",
            datosAnteriores: $datosAnteriores
        );

        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado exitosamente',
        ]);
    }

    #[OA\Patch(
        path: '/api/proveedores/{id}/toggle-estado',
        summary: 'Activar/Desactivar proveedor',
        security: [['bearerAuth' => []]],
        tags: ['Proveedores'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
        responses: [new OA\Response(response: 200, description: 'Estado actualizado'), new OA\Response(response: 404, description: 'No encontrado')]
    )]
    public function toggleEstado(string $id): JsonResponse
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ], 404);
        }

        $estadoAnterior = $proveedor->estado;
        $proveedor->estado = $proveedor->estado === 'activo' ? 'inactivo' : 'activo';
        $proveedor->save();

        ActivityLog::registrar(
            accion: 'toggle_estado',
            modulo: 'proveedores',
            registroId: $proveedor->id,
            descripcion: "Estado de proveedor cambiado: {$proveedor->nombre} de {$estadoAnterior} a {$proveedor->estado}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $proveedor,
        ]);
    }

    /**
     * Validación centralizada
     */
    private function validateData(Request $request, ?string $id = null): array
    {
        $unique = fn(string $column) => Rule::unique('proveedores', $column)->ignore($id);

        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'nit' => ['nullable', 'string', 'max:100', $unique('nit')],
            'telefono' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', $unique('email')],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:150'],
            'pais' => ['nullable', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'telefono_contacto' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);
    }
}

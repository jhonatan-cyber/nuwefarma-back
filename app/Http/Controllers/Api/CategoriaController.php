<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Categoria\CategoriaCollection;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\Categoria;
use App\Actions\Categoria\CreateCategoriaAction;
use App\Actions\Categoria\UpdateCategoriaAction;
use App\Actions\Categoria\DeleteCategoriaAction;
use App\Actions\Categoria\ListCategoriasAction;
use App\Actions\Categoria\BulkUpdateCategoriasAction;
use App\DTOs\Categoria\CreateCategoriaDTO;
use App\DTOs\Categoria\UpdateCategoriaDTO;
use App\DTOs\Categoria\ListCategoriasDTO;
use App\DTOs\Categoria\BulkUpdateCategoriasDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoriaController extends Controller
{
    // Simplificar sin DTOs y Actions complejas por ahora
    // public function __construct(
    //     private CreateCategoriaAction $createCategoriaAction,
    //     private UpdateCategoriaAction $updateCategoriaAction,
    //     private DeleteCategoriaAction $deleteCategoriaAction,
    //     private ListCategoriasAction $listCategoriasAction,
    //     private BulkUpdateCategoriasAction $bulkUpdateCategoriasAction
    // ) {}

    /**
     * Display a paginated listing of categories with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::query();

        // Aplicar filtros básicos
        if ($request->search) {
            $query->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('descripcion', 'like', "%{$request->search}%");
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        // Ordenamiento
        $sort = $request->sort ?? 'nombre';
        $direction = $request->direction ?? 'asc';
        $query->orderBy($sort, $direction);

        $perPage = min($request->per_page ?? 15, 100);
        $categorias = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => new CategoriaCollection($categorias)
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created category in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Agregar validación
            $validated = $request->validate([
                'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
                'descripcion' => ['nullable', 'string', 'max:1000'],
                'estado' => ['nullable', 'in:activo,inactivo,descontinuado'],
            ]);

            $categoria = Categoria::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'estado' => $validated['estado'] ?? 'activo',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => new CategoriaResource($categoria->loadCount('productos'))
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified category with relationships.
     * 
     * @param Request $request
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $categoria = Categoria::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => new CategoriaResource($categoria->loadCount('productos'))
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }
    }

    /**
     * Update the specified category in storage.
     * 
     * @param Request $request
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $categoria = Categoria::findOrFail($id);
            
            $validated = $request->validate([
                'nombre' => ['sometimes', 'string', 'max:255', 'unique:categorias,nombre,'.$id],
                'descripcion' => ['nullable', 'string', 'max:1000'],
                'estado' => ['nullable', 'in:activo,inactivo,descontinuado'],
            ]);

            $categoria->update([
                'nombre' => $validated['nombre'] ?? $categoria->nombre,
                'descripcion' => $validated['descripcion'] ?? $categoria->descripcion,
                'estado' => $validated['estado'] ?? $categoria->estado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => new CategoriaResource($categoria->loadCount('productos'))
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified category from storage.
     * 
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $categoria->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }
    }

    /**
     * Toggle category status
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function toggleEstado(Request $request, $id): JsonResponse
    {
        try {
            $categoria = Categoria::findOrFail($id);
            
            $nuevoEstado = $categoria->estado === 'activo' ? 'inactivo' : 'activo';
            $categoria->update(['estado' => $nuevoEstado]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de la categoría actualizado exitosamente',
                'data' => ['estado' => $nuevoEstado]
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }
    }
}

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
    public function __construct(
        private CreateCategoriaAction $createCategoriaAction,
        private UpdateCategoriaAction $updateCategoriaAction,
        private DeleteCategoriaAction $deleteCategoriaAction,
        private ListCategoriasAction $listCategoriasAction,
        private BulkUpdateCategoriasAction $bulkUpdateCategoriasAction
    ) {}

    /**
     * Display a paginated listing of categories with filtering.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new ListCategoriasDTO(
            search: $request->string('search')?->toString(),
            estado: $request->string('estado')?->toString(),
            sort: $request->string('sort', 'nombre')?->toString(),
            direction: $request->string('direction', 'asc')?->toString(),
            per_page: $request->integer('per_page', 15)
        );
        
        $categorias = $this->listCategoriasAction->execute($filters);

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
        $dto = new CreateCategoriaDTO(
            nombre: $request->string('nombre')?->toString(),
            descripcion: $request->string('descripcion')?->toString(),
            estado: $request->string('estado', 'activo')?->toString()
        );

        $categoria = $this->createCategoriaAction->execute($dto);

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada exitosamente',
            'data' => new CategoriaResource($categoria->loadCount('productos'))
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified category with relationships.
     * 
     * @param Request $request
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function show(Request $request, Categoria $categoria): JsonResponse
    {
        // Laravel 12+: Conditional eager loading
        $includes = $request->collect('include', []);
        
        if ($includes->contains('productos')) {
            $categoria->load(['productos' => fn($query) => 
                $query->select('id', 'nombre', 'precio', 'stock', 'categoria_id')
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoriaResource($categoria->loadCount('productos'))
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified category in storage.
     * 
     * @param Request $request
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $dto = new UpdateCategoriaDTO(
            nombre: $request->filled('nombre') ? $request->string('nombre')?->toString() : null,
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')?->toString() : null,
            estado: $request->filled('estado') ? $request->string('estado')?->toString() : null
        );

        $updatedCategoria = $this->updateCategoriaAction->execute($categoria, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'data' => new CategoriaResource($updatedCategoria->loadCount('productos'))
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified category from storage.
     * 
     * @param Categoria $categoria
     * @return JsonResponse
     */
    public function destroy(Categoria $categoria): JsonResponse
    {
        $this->deleteCategoriaAction->execute($categoria);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ], Response::HTTP_OK);
    }

    /**
     * Bulk update categories status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $dto = new BulkUpdateCategoriasDTO(
            ids: $request->array('ids'),
            estado: $request->string('estado')?->toString()
        );

        $updatedCount = $this->bulkUpdateCategoriasAction->execute($dto->ids, $dto->estado);

        return response()->json([
            'success' => true,
            'message' => 'Categorías actualizadas exitosamente',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ], Response::HTTP_OK);
    }
}

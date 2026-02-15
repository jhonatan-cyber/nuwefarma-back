<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Categoria\BulkUpdateCategoriasAction;
use App\Actions\Categoria\CreateCategoriaAction;
use App\Actions\Categoria\DeleteCategoriaAction;
use App\Actions\Categoria\ListCategoriasAction;
use App\Actions\Categoria\UpdateCategoriaAction;
use App\DTOs\Categoria\BulkUpdateCategoriasDTO;
use App\DTOs\Categoria\CreateCategoriaDTO;
use App\DTOs\Categoria\ListCategoriasDTO;
use App\DTOs\Categoria\UpdateCategoriaDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Http\Requests\Categoria\UpdateCategoriaRequest;
use App\Http\Resources\Categoria\CategoriaCollection;
use App\Http\Resources\Categoria\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;

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
     */
    public function index(Request $request)
    {
        $filters = new ListCategoriasDTO(
            search: $request->string('search')?->toString(),
            estado: $request->string('estado')?->toString(),
            sort: $request->string('sort', 'nombre')?->toString(),
            direction: $request->string('direction', 'asc')?->toString(),
            per_page: $request->integer('per_page', 15)
        );

        $categorias = $this->listCategoriasAction->execute($filters);

        return $this->success(new CategoriaCollection($categorias));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoriaRequest $request)
    {
        $dto = new CreateCategoriaDTO(
            nombre: $request->validated('nombre'),
            descripcion: $request->validated('descripcion'),
            estado: $request->validated('estado', 'activo')
        );

        $categoria = $this->createCategoriaAction->execute($dto);

        return $this->created(
            new CategoriaResource($categoria->loadCount('productos')),
            'Categoría creada exitosamente'
        );
    }

    /**
     * Display the specified category with relationships.
     */
    public function show(Request $request, Categoria $categoria)
    {
        $includes = $request->collect('include', []);

        if ($includes->contains('productos')) {
            $categoria->load(['productos' => fn ($query) => $query->select('id', 'nombre', 'precio', 'stock', 'categoria_id'),
            ]);
        }

        return $this->success(
            new CategoriaResource($categoria->loadCount('productos'))
        );
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        $dto = new UpdateCategoriaDTO(
            nombre: $request->validated('nombre'),
            descripcion: $request->validated('descripcion'),
            estado: $request->validated('estado')
        );

        $updatedCategoria = $this->updateCategoriaAction->execute($categoria, $dto);

        return $this->success(
            new CategoriaResource($updatedCategoria->loadCount('productos')),
            'Categoría actualizada exitosamente'
        );
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Categoria $categoria)
    {
        $this->deleteCategoriaAction->execute($categoria);

        return $this->noContent('Categoría eliminada exitosamente');
    }

    /**
     * Bulk update categories status.
     */
    public function bulkUpdate(Request $request)
    {
        $dto = new BulkUpdateCategoriasDTO(
            ids: $request->array('ids'),
            estado: $request->string('estado')?->toString()
        );

        $updatedCount = $this->bulkUpdateCategoriasAction->execute($dto->ids, $dto->estado);

        return $this->success([
            'updated_count' => $updatedCount,
        ], 'Categorías actualizadas exitosamente');
    }
}

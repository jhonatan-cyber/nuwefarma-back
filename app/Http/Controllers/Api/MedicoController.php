<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Medico\MedicoCollection;
use App\Http\Resources\Medico\MedicoResource;
use App\Models\Medico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class MedicoController extends Controller
{
    /**
     * Listar médicos con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Medico::withCount(['recetas']);

        if ($request->q || $request->search) {
            $term = $request->q ?? $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('ci', 'like', "%{$term}%")
                    ->orWhere('registro_profesional', 'like', "%{$term}%");
            });
        }

        if ($request->especialidad) {
            $query->where('especialidad', 'like', "%{$request->especialidad}%");
        }

        if ($request->estado) {
            $query->where('estado', $request->estado);
        }

        $sort = $request->sort ?? 'nombres';
        $direction = $request->direction ?? 'asc';
        $query->orderBy($sort === 'nombre_completo' ? 'nombres' : $sort, $direction);

        $perPage = min($request->per_page ?? 15, 100);

        return response()->json([
            'success' => true,
            'data' => new MedicoCollection($query->paginate($perPage)),
        ], Response::HTTP_OK);
    }

    /**
     * Crear un médico.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'ci' => ['nullable', 'string', 'max:20'],
            'registro_profesional' => ['required', 'string', 'max:50', Rule::unique('medicos', 'registro_profesional')],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'institucion' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $medico = Medico::create([
            ...$validated,
            'estado' => $validated['estado'] ?? 'activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Médico creado exitosamente',
            'data' => new MedicoResource($medico),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar un médico.
     */
    public function show(Medico $medico): JsonResponse
    {
        $medico->loadCount(['recetas']);

        return response()->json([
            'success' => true,
            'data' => new MedicoResource($medico),
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar un médico.
     */
    public function update(Request $request, Medico $medico): JsonResponse
    {
        $validated = $request->validate([
            'nombres' => ['sometimes', 'required', 'string', 'max:120'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:120'],
            'ci' => ['nullable', 'string', 'max:20'],
            'registro_profesional' => ['sometimes', 'string', 'max:50', Rule::unique('medicos', 'registro_profesional')->ignore($medico->id)],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'institucion' => ['nullable', 'string', 'max:200'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $medico->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Médico actualizado exitosamente',
            'data' => new MedicoResource($medico->loadCount(['recetas'])),
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar un médico.
     */
    public function destroy(Medico $medico): JsonResponse
    {
        if ($medico->recetas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un médico con recetas asociadas. Puede desactivarlo.',
                'errors' => ['recetas' => ['El médico tiene recetas registradas']],
            ], Response::HTTP_CONFLICT);
        }

        $medico->delete();

        return response()->json([
            'success' => true,
            'message' => 'Médico eliminado exitosamente',
        ], Response::HTTP_OK);
    }
}
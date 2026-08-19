<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Paciente\PacienteCollection;
use App\Http\Resources\Paciente\PacienteResource;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PacienteController extends Controller
{
    /**
     * Listar pacientes con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Paciente::withCount(['recetas']);

        if ($request->q || $request->search) {
            $term = $request->q ?? $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('ci', 'like', "%{$term}%");
            });
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
            'data' => new PacienteCollection($query->paginate($perPage)),
        ], Response::HTTP_OK);
    }

    /**
     * Crear un paciente.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ci' => ['nullable', 'string', 'max:20', 'unique:pacientes,ci'],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo' => ['nullable', 'string', 'in:M,F'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'contacto_emergencia' => ['nullable', 'string', 'max:200'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ]);

        $paciente = Paciente::create([
            ...$validated,
            'estado' => $validated['estado'] ?? 'activo',
            'sucursal_id' => $request->user()->sucursal_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paciente creado exitosamente',
            'data' => new PacienteResource($paciente),
        ], Response::HTTP_CREATED);
    }

    /**
     * Mostrar un paciente.
     */
    public function show(Paciente $paciente): JsonResponse
    {
        $paciente->loadCount(['recetas']);

        return response()->json([
            'success' => true,
            'data' => new PacienteResource($paciente),
        ], Response::HTTP_OK);
    }

    /**
     * Actualizar un paciente.
     */
    public function update(Request $request, Paciente $paciente): JsonResponse
    {
        $validated = $request->validate([
            'ci' => ['nullable', 'string', 'max:20', 'unique:pacientes,ci,'.$paciente->id],
            'nombres' => ['sometimes', 'required', 'string', 'max:120'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo' => ['nullable', 'string', 'in:M,F'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'contacto_emergencia' => ['nullable', 'string', 'max:200'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'estado' => ['nullable', 'in:activo,inactivo'],
        ]);

        $paciente->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paciente actualizado exitosamente',
            'data' => new PacienteResource($paciente->loadCount(['recetas'])),
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar un paciente.
     */
    public function destroy(Paciente $paciente): JsonResponse
    {
        if ($paciente->recetas()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un paciente con recetas asociadas',
                'errors' => ['recetas' => ['El paciente tiene recetas registradas']],
            ], Response::HTTP_CONFLICT);
        }

        $paciente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paciente eliminado exitosamente',
        ], Response::HTTP_OK);
    }
}
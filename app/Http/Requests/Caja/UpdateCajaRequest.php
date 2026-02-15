<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->route('caja'));
    }

    public function rules(): array
    {
        $caja = $this->route('caja');

        return [
            'numero_caja' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:999',
                Rule::unique('cajas', 'numero_caja')->ignore($caja),
            ],
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'sucursal_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:sucursales,id',
            ],
            'notas' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['abierta', 'cerrada', 'suspendida']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_caja.required' => 'El número de caja es obligatorio.',
            'numero_caja.integer' => 'El número de caja debe ser un entero.',
            'numero_caja.min' => 'El número de caja debe ser al menos 1.',
            'numero_caja.max' => 'El número de caja no puede exceder 999.',
            'numero_caja.unique' => 'Ya existe una caja con este número.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.uuid' => 'El ID de la sucursal debe ser un UUID válido.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'notas.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'estado.in' => 'El estado debe ser abierta, cerrada o suspendida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'numero_caja' => 'número de caja',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'sucursal_id' => 'sucursal',
            'notas' => 'notas',
            'estado' => 'estado',
        ];
    }

    public function getValidatedData(): array
    {
        return $this->validated();
    }
}

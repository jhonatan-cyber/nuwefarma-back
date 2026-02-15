<?php

namespace App\Http\Requests\Caja;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create', \App\Models\Caja::class);
    }

    public function rules(): array
    {
        return [
            'numero_caja' => [
                'required',
                'integer',
                'min:1',
                'max:999',
                'unique:cajas,numero_caja',
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'saldo_inicial' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'sucursal_id' => [
                'required',
                'uuid',
                'exists:sucursales,id',
            ],
            'notas' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
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
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'saldo_inicial.numeric' => 'El saldo inicial debe ser un número.',
            'saldo_inicial.min' => 'El saldo inicial no puede ser negativo.',
            'saldo_inicial.max' => 'El saldo inicial no puede exceder 999,999,999.99.',
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
            'saldo_inicial' => 'saldo inicial',
            'sucursal_id' => 'sucursal',
            'notas' => 'notas',
            'estado' => 'estado',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('estado')) {
            $this->merge(['estado' => 'cerrada']);
        }

        if (! $this->has('saldo_inicial')) {
            $this->merge(['saldo_inicial' => 0]);
        }
    }

    public function getValidatedData(): array
    {
        $data = $this->validated();

        $data['saldo_actual'] = $data['saldo_inicial'];
        $data['total_ingresos'] = 0;
        $data['total_egresos'] = 0;
        $data['usuario_id'] = auth()->id();

        return $data;
    }
}

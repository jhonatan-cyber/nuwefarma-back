<?php

namespace App\Http\Requests\Venta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->route('venta'));
    }

    public function rules(): array
    {
        $venta = $this->route('venta');

        return [
            'cliente_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:clientes,id',
            ],
            'caja_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:cajas,id',
            ],
            'sucursal_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:sucursales,id',
            ],
            'productos' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
            'productos.*.producto_id' => [
                'required',
                'uuid',
                'exists:productos,id',
            ],
            'productos.*.cantidad' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
            'productos.*.precio_unitario' => [
                'required',
                'numeric',
                'min:0',
            ],
            'productos.*.descuento' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'subtotal' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'descuento' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'impuestos' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'total' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
            ],
            'metodo_pago' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 'credito', 'mixto']),
            ],
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['pendiente', 'completada', 'cancelada', 'devuelta']),
            ],
            'notas' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'fecha_venta' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.uuid' => 'El ID del cliente debe ser un UUID válido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'caja_id.required' => 'La caja es obligatoria.',
            'caja_id.uuid' => 'El ID de la caja debe ser un UUID válido.',
            'caja_id.exists' => 'La caja seleccionada no existe.',
            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.uuid' => 'El ID de la sucursal debe ser un UUID válido.',
            'sucursal_id.exists' => 'La sucursal seleccionada no existe.',
            'productos.required' => 'Debe incluir al menos un producto.',
            'productos.array' => 'Los productos deben ser un array.',
            'productos.min' => 'Debe incluir al menos un producto.',
            'productos.*.producto_id.required' => 'El ID del producto es obligatorio.',
            'productos.*.producto_id.uuid' => 'El ID del producto debe ser un UUID válido.',
            'productos.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'productos.*.cantidad.required' => 'La cantidad es obligatoria.',
            'productos.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'productos.*.cantidad.max' => 'La cantidad no puede exceder 9,999 unidades.',
            'productos.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
            'productos.*.precio_unitario.numeric' => 'El precio unitario debe ser un número.',
            'productos.*.precio_unitario.min' => 'El precio unitario no puede ser negativo.',
            'productos.*.descuento.numeric' => 'El descuento debe ser un número.',
            'productos.*.descuento.min' => 'El descuento no puede ser negativo.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal no puede ser negativo.',
            'descuento.numeric' => 'El descuento debe ser un número.',
            'descuento.min' => 'El descuento no puede ser negativo.',
            'descuento.max' => 'El descuento no puede exceder 999,999.99.',
            'impuestos.numeric' => 'Los impuestos deben ser un número.',
            'impuestos.min' => 'Los impuestos no pueden ser negativos.',
            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor a 0.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago no es válido.',
            'estado.in' => 'El estado debe ser pendiente, completada, cancelada o devuelta.',
            'notas.max' => 'Las notas no pueden exceder 1,000 caracteres.',
            'fecha_venta.before_or_equal' => 'La fecha de venta no puede ser futura.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'caja_id' => 'caja',
            'sucursal_id' => 'sucursal',
            'productos' => 'productos',
            'productos.*.producto_id' => 'producto',
            'productos.*.cantidad' => 'cantidad',
            'productos.*.precio_unitario' => 'precio unitario',
            'productos.*.descuento' => 'descuento',
            'subtotal' => 'subtotal',
            'descuento' => 'descuento',
            'impuestos' => 'impuestos',
            'total' => 'total',
            'metodo_pago' => 'método de pago',
            'estado' => 'estado',
            'notas' => 'notas',
            'fecha_venta' => 'fecha de venta',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $venta = $this->route('venta');

            if ($venta && in_array($venta->estado, ['completada', 'cancelada', 'devuelta'])) {
                if ($this->has('estado') || $this->has('productos') || $this->has('total')) {
                    $validator->errors()->add(
                        'estado',
                        'No se puede modificar una venta completada, cancelada o devuelta.'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('descuento') && $this->descuento === '') {
            $this->merge(['descuento' => 0]);
        }

        if ($this->has('impuestos') && $this->impuestos === '') {
            $this->merge(['impuestos' => 0]);
        }
    }

    public function getValidatedData(): array
    {
        return $this->validated();
    }
}

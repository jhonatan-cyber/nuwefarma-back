<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productoId = $this->route('producto')?->id;

        return [
            'nombre' => 'required|string|max:255',
            'categoria_id' => 'nullable|uuid|exists:categorias,id',
            'proveedor_id' => 'nullable|uuid|exists:proveedores,id',
            'codigo_barras' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo_barras')->ignore($productoId),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'sku')->ignore($productoId),
            ],
            'codigo_interno' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo_interno')->ignore($productoId),
            ],
            'laboratorio' => 'nullable|string|max:255',
            'forma_farmaceutica' => 'nullable|string|max:100',
            'concentracion' => 'nullable|string|max:100',
            'presentacion' => 'nullable|string|max:100',
            'via_administracion' => 'nullable|string|max:100',
            'unidad_medida' => 'nullable|string|max:50',
            'fracciones_por_unidad' => 'nullable|integer|min:1',
            'permite_fraccionar' => 'boolean',
            'registro_sanitario' => 'nullable|string|max:100',
            'refrigeracion_requerida' => 'boolean',
            'dias_para_alertar_vencimiento' => 'nullable|integer|min:1|max:365',
            'stock_actual' => 'nullable|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
            'stock_maximo' => 'nullable|integer|min:0|gte:stock_minimo',
            'precio_compra' => 'nullable|numeric|min:0',
            'precio_venta' => 'nullable|numeric|min:0|gt:precio_compra',
            'margen_sugerido' => 'nullable|numeric|min:0|max:100',
            'impuesto' => 'nullable|numeric|min:0|max:100',
            'etiquetas' => 'nullable|array',
            'etiquetas.*' => 'string|max:50',
            'fotos' => 'nullable|array',
            'fotos.*' => 'string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'estado' => 'nullable|in:activo,inactivo',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.max' => 'El nombre del producto no puede exceder 255 caracteres.',
            'categoria_id.uuid' => 'El ID de categoría debe ser un UUID válido.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'proveedor_id.uuid' => 'El ID de proveedor debe ser un UUID válido.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'codigo_barras.unique' => 'El código de barras ya está registrado.',
            'sku.unique' => 'El SKU ya está registrado.',
            'codigo_interno.unique' => 'El código interno ya está registrado.',
            'fracciones_por_unidad.min' => 'Las fracciones por unidad deben ser mayor a 0.',
            'dias_para_alertar_vencimiento.min' => 'Los días para alertar deben ser mayor a 0.',
            'dias_para_alertar_vencimiento.max' => 'Los días para alertar no pueden exceder 365.',
            'stock_actual.min' => 'El stock actual debe ser mayor o igual a 0.',
            'stock_minimo.min' => 'El stock mínimo debe ser mayor o igual a 0.',
            'stock_maximo.min' => 'El stock máximo debe ser mayor o igual a 0.',
            'stock_maximo.gte' => 'El stock máximo debe ser mayor o igual al stock mínimo.',
            'precio_compra.min' => 'El precio de compra debe ser mayor o igual a 0.',
            'precio_venta.min' => 'El precio de venta debe ser mayor o igual a 0.',
            'precio_venta.gt' => 'El precio de venta debe ser mayor al precio de compra.',
            'margen_sugerido.min' => 'El margen sugerido debe ser mayor o igual a 0.',
            'margen_sugerido.max' => 'El margen sugerido no puede exceder 100%.',
            'impuesto.min' => 'El impuesto debe ser mayor o igual a 0.',
            'impuesto.max' => 'El impuesto no puede exceder 100%.',
            'etiquetas.*.max' => 'Cada etiqueta no puede exceder 50 caracteres.',
            'fotos.*.max' => 'Cada URL de foto no puede exceder 255 caracteres.',
            'descripcion.max' => 'La descripción no puede exceder 1000 caracteres.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del producto',
            'categoria_id' => 'categoría',
            'proveedor_id' => 'proveedor',
            'codigo_barras' => 'código de barras',
            'sku' => 'SKU',
            'codigo_interno' => 'código interno',
            'laboratorio' => 'laboratorio',
            'forma_farmaceutica' => 'forma farmacéutica',
            'concentracion' => 'concentración',
            'presentacion' => 'presentación',
            'via_administracion' => 'vía de administración',
            'unidad_medida' => 'unidad de medida',
            'fracciones_por_unidad' => 'fracciones por unidad',
            'permite_fraccionar' => 'permite fraccionar',
            'registro_sanitario' => 'registro sanitario',
            'refrigeracion_requerida' => 'refrigeración requerida',
            'dias_para_alertar_vencimiento' => 'días para alertar vencimiento',
            'stock_actual' => 'stock actual',
            'stock_minimo' => 'stock mínimo',
            'stock_maximo' => 'stock máximo',
            'precio_compra' => 'precio de compra',
            'precio_venta' => 'precio de venta',
            'margen_sugerido' => 'margen sugerido',
            'impuesto' => 'impuesto',
            'etiquetas' => 'etiquetas',
            'fotos' => 'fotos',
            'descripcion' => 'descripción',
            'estado' => 'estado',
        ];
    }
}

<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->route('producto'));
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'nombre')->ignore($producto),
            ],
            'categoria_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:categorias,id',
            ],
            'proveedor_id' => [
                'sometimes',
                'nullable',
                'uuid',
                'exists:proveedores,id',
            ],
            'codigo_barras' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo_barras')->ignore($producto),
            ],
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'sku')->ignore($producto),
            ],
            'codigo_interno' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('productos', 'codigo_interno')->ignore($producto),
            ],
            'laboratorio' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'forma_farmaceutica' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'concentracion' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'presentacion' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'via_administracion' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'unidad_medida' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'fracciones_por_unidad' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
            'permite_fraccionar' => [
                'sometimes',
                'boolean',
            ],
            'lote' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'fecha_vencimiento' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'registro_sanitario' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'refrigeracion_requerida' => [
                'sometimes',
                'boolean',
            ],
            'dias_para_alertar_vencimiento' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'stock_actual' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'stock_minimo' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'stock_maximo' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:stock_minimo',
            ],
            'precio_compra' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d{1,6}(\.\d{1,2})?$/',
            ],
            'precio_venta' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'regex:/^\d{1,6}(\.\d{1,2})?$/',
            ],
            'margen_sugerido' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,3}(\.\d{1,2})?$/',
            ],
            'impuesto' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,3}(\.\d{1,2})?$/',
            ],
            'etiquetas' => [
                'sometimes',
                'nullable',
                'array',
                'max:10',
            ],
            'etiquetas.*' => [
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
            'fotos' => [
                'sometimes',
                'nullable',
                'array',
                'max:5',
            ],
            'fotos.*' => [
                'string',
                'url',
                'max:500',
            ],
            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo', 'descontinuado']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.unique' => 'Ya existe un producto con este nombre.',
            'categoria_id.uuid' => 'El ID de categoría debe ser un UUID válido.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'proveedor_id.uuid' => 'El ID de proveedor debe ser un UUID válido.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'codigo_barras.unique' => 'El código de barras ya está registrado.',
            'sku.unique' => 'El SKU ya está registrado.',
            'codigo_interno.unique' => 'El código interno ya está registrado.',
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.max' => 'El stock actual no puede exceder 999,999 unidades.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_maximo.gte' => 'El stock máximo debe ser mayor o igual al stock mínimo.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.regex' => 'El precio de compra debe tener máximo 2 decimales.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.gt' => 'El precio de venta debe ser mayor al precio de compra.',
            'precio_venta.regex' => 'El precio de venta debe tener máximo 2 decimales.',
            'margen_sugerido.regex' => 'El margen sugerido debe tener máximo 2 decimales.',
            'impuesto.regex' => 'El impuesto debe tener máximo 2 decimales.',
            'etiquetas.max' => 'No puede agregar más de 10 etiquetas.',
            'etiquetas.*.regex' => 'Las etiquetas solo pueden contener letras, números, espacios, guiones y guiones bajos.',
            'fotos.max' => 'No puede agregar más de 5 fotos.',
            'fotos.*.url' => 'Cada foto debe ser una URL válida.',
            'estado.in' => 'El estado debe ser activo, inactivo o descontinuado.',
        ];
    }

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
            'lote' => 'lote',
            'fecha_vencimiento' => 'fecha de vencimiento',
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

    protected function prepareForValidation(): void
    {
        if ($this->has('permite_fraccionar')) {
            $this->merge([
                'permite_fraccionar' => $this->boolean('permite_fraccionar'),
            ]);
        }

        if ($this->has('refrigeracion_requerida')) {
            $this->merge([
                'refrigeracion_requerida' => $this->boolean('refrigeracion_requerida'),
            ]);
        }

        if ($this->has('fracciones_por_unidad')) {
            $this->merge([
                'fracciones_por_unidad' => $this->integer('fracciones_por_unidad') ?? 1,
            ]);
        }

        if ($this->has('dias_para_alertar_vencimiento')) {
            $this->merge([
                'dias_para_alertar_vencimiento' => $this->integer('dias_para_alertar_vencimiento') ?? 60,
            ]);
        }

        if ($this->has('etiquetas')) {
            $this->merge([
                'etiquetas' => $this->collect($this->get('etiquetas', []))
                    ->map(fn ($etiqueta) => trim($etiqueta))
                    ->filter(fn ($etiqueta) => ! empty($etiqueta))
                    ->unique()
                    ->values()
                    ->all(),
            ]);
        }

        if ($this->has('fotos')) {
            $this->merge([
                'fotos' => $this->collect($this->get('fotos', []))
                    ->filter(fn ($foto) => ! empty($foto) && filter_var($foto, FILTER_VALIDATE_URL))
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function getValidatedData(): array
    {
        $data = $this->validated();

        $data['actualizar_usuario_id'] = auth()->id();

        return $data;
    }
}

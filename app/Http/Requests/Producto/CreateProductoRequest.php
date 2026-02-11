<?php

namespace App\Http\Requests\Producto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create', \App\Models\Producto::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:productos,nombre',
            ],
            'categoria_id' => [
                'nullable',
                'uuid',
                'exists:categorias,id',
            ],
            'proveedor_id' => [
                'nullable',
                'uuid',
                'exists:proveedores,id',
            ],
            'codigo_barras' => [
                'nullable',
                'string',
                'max:100',
                'unique:productos,codigo_barras',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'unique:productos,sku',
            ],
            'codigo_interno' => [
                'nullable',
                'string',
                'max:100',
                'unique:productos,codigo_interno',
            ],
            'laboratorio' => [
                'nullable',
                'string',
                'max:255',
            ],
            'forma_farmaceutica' => [
                'nullable',
                'string',
                'max:100',
            ],
            'concentracion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'presentacion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'via_administracion' => [
                'nullable',
                'string',
                'max:50',
            ],
            'unidad_medida' => [
                'nullable',
                'string',
                'max:50',
            ],
            'fracciones_por_unidad' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
            'permite_fraccionar' => [
                'boolean',
            ],
            'lote' => [
                'nullable',
                'string',
                'max:100',
            ],
            'fecha_vencimiento' => [
                'nullable',
                'date',
                'after:today',
            ],
            'registro_sanitario' => [
                'nullable',
                'string',
                'max:100',
            ],
            'refrigeracion_requerida' => [
                'boolean',
            ],
            'dias_para_alertar_vencimiento' => [
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'stock_actual' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'stock_minimo' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'stock_maximo' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:stock_minimo',
            ],
            'precio_compra' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d{1,6}(\.\d{1,2})?$/',
            ],
            'precio_venta' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'regex:/^\d{1,6}(\.\d{1,2})?$/',
                'gt:precio_compra',
            ],
            'margen_sugerido' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,3}(\.\d{1,2})?$/',
            ],
            'impuesto' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'regex:/^\d{1,3}(\.\d{1,2})?$/',
            ],
            'etiquetas' => [
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
                'nullable',
                'string',
                'max:1000',
            ],
            'estado' => [
                'nullable',
                'string',
                Rule::in(['activo', 'inactivo']),
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
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
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
            'estado.in' => 'El estado debe ser activo o inactivo.',
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
        $this->merge([
            'permite_fraccionar' => $this->boolean('permite_fraccionar'),
            'refrigeracion_requerida' => $this->boolean('refrigeracion_requerida'),
            'fracciones_por_unidad' => $this->integer('fracciones_por_unidad') ?? 1,
            'dias_para_alertar_vencimiento' => $this->integer('dias_para_alertar_vencimiento') ?? 60,
            'stock_actual' => $this->integer('stock_actual') ?? 0,
            'stock_minimo' => $this->integer('stock_minimo') ?? 0,
            'stock_maximo' => $this->integer('stock_maximo'),
            'etiquetas' => $this->collect($this->get('etiquetas', []))
                ->map(fn($etiqueta) => trim($etiqueta))
                ->filter(fn($etiqueta) => !empty($etiqueta))
                ->unique()
                ->values()
                ->all(),
            'fotos' => $this->collect($this->get('fotos', []))
                ->filter(fn($foto) => !empty($foto) && filter_var($foto, FILTER_VALIDATE_URL))
                ->values()
                ->all(),
        ]);
    }

    public function getValidatedData(): array
    {
        $data = $this->validated();
        
        // Add user ID for auditing
        $data['crear_usuario_id'] = auth()->id();
        
        return $data;
    }
}

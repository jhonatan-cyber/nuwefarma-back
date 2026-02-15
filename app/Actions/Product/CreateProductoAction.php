<?php

namespace App\Actions\Product;

use App\DTOs\Product\CreateProductoDTO;
use App\Enums\EstadoEnum;
use App\Models\Producto;
use App\ValueObjects\ProductPrice;
use Illuminate\Support\Facades\DB;

readonly class CreateProductoAction
{
    public function __construct(
        private ProductPrice $priceValidator
    ) {}

    public function execute(CreateProductoDTO $data): Producto
    {
        return DB::transaction(function () use ($data) {
            // Validate price using value object
            $price = new ProductPrice(
                $data->precioCompra,
                $data->precioVenta,
                $data->impuesto ?? 0
            );

            // Create product with validated data
            $uuid = \Illuminate\Support\Str::uuid();
            $producto = Producto::create([
                'id' => $uuid,
                'nombre' => $data->nombre,
                'categoria_id' => $data->categoriaId,
                'proveedor_id' => $data->proveedorId,
                'codigo_barras' => $data->codigoBarras,
                'sku' => $data->sku,
                'codigo_interno' => $data->codigoInterno,
                'laboratorio' => $data->laboratorio,
                'forma_farmaceutica' => $data->formaFarmaceutica,
                'concentracion' => $data->concentracion,
                'presentacion' => $data->presentacion,
                'via_administracion' => $data->viaAdministracion,
                'unidad_medida' => $data->unidadMedida,
                'fracciones_por_unidad' => $data->fraccionesPorUnidad,
                'permite_fraccionar' => $data->permiteFraccionar,
                'lote' => $data->lote,
                'fecha_vencimiento' => $data->fechaVencimiento,
                'registro_sanitario' => $data->registroSanitario,
                'refrigeracion_requerida' => $data->refrigeracionRequerida,
                'dias_para_alertar_vencimiento' => $data->diasParaAlertarVencimiento,
                'stock_actual' => $data->stockActual,
                'stock_minimo' => $data->stockMinimo,
                'stock_maximo' => $data->stockMaximo,
                'precio_compra' => $price->getPurchasePrice(),
                'precio_venta' => $price->getSellingPrice(),
                'margen_sugerido' => $price->getMargin(),
                'impuesto' => $price->getTax(),
                'etiquetas' => $data->etiquetas ?? [],
                'fotos' => $data->fotos ?? [],
                'descripcion' => $data->descripcion,
                'estado' => EstadoEnum::ACTIVO->value,
            ]);

            // Fire event for further processing
            event(new \App\Events\Product\ProductCreated($producto));

            return $producto;
        });
    }
}

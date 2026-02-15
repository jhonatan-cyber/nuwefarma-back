<?php

namespace App\DTOs\Product;

readonly class UpdateProductoDTO
{
    public function __construct(
        public ?string $nombre = null,
        public ?string $categoriaId = null,
        public ?string $proveedorId = null,
        public ?string $codigoBarras = null,
        public ?string $sku = null,
        public ?string $codigoInterno = null,
        public ?string $laboratorio = null,
        public ?string $formaFarmaceutica = null,
        public ?string $concentracion = null,
        public ?string $presentacion = null,
        public ?string $viaAdministracion = null,
        public ?string $unidadMedida = null,
        public ?int $fraccionesPorUnidad = null,
        public ?bool $permiteFraccionar = null,
        public ?string $lote = null,
        public ?\DateTime $fechaVencimiento = null,
        public ?string $registroSanitario = null,
        public ?bool $refrigeracionRequerida = null,
        public ?int $diasParaAlertarVencimiento = null,
        public ?int $stockActual = null,
        public ?int $stockMinimo = null,
        public ?int $stockMaximo = null,
        public ?float $precioCompra = null,
        public ?float $precioVenta = null,
        public ?float $margenSugerido = null,
        public ?float $impuesto = null,
        public ?array $etiquetas = null,
        public ?array $fotos = null,
        public ?string $descripcion = null,
        public ?string $estado = null,
        public ?int $actualizarUsuarioId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'] ?? null,
            categoriaId: $data['categoria_id'] ?? null,
            proveedorId: $data['proveedor_id'] ?? null,
            codigoBarras: $data['codigo_barras'] ?? null,
            sku: $data['sku'] ?? null,
            codigoInterno: $data['codigo_interno'] ?? null,
            laboratorio: $data['laboratorio'] ?? null,
            formaFarmaceutica: $data['forma_farmaceutica'] ?? null,
            concentracion: $data['concentracion'] ?? null,
            presentacion: $data['presentacion'] ?? null,
            viaAdministracion: $data['via_administracion'] ?? null,
            unidadMedida: $data['unidad_medida'] ?? null,
            fraccionesPorUnidad: $data['fracciones_por_unidad'] ?? null,
            permiteFraccionar: $data['permite_fraccionar'] ?? null,
            lote: $data['lote'] ?? null,
            fechaVencimiento: isset($data['fecha_vencimiento']) && $data['fecha_vencimiento']
                ? new \DateTime($data['fecha_vencimiento'])
                : null,
            registroSanitario: $data['registro_sanitario'] ?? null,
            refrigeracionRequerida: $data['refrigeracion_requerida'] ?? null,
            diasParaAlertarVencimiento: $data['dias_para_alertar_vencimiento'] ?? null,
            stockActual: $data['stock_actual'] ?? null,
            stockMinimo: $data['stock_minimo'] ?? null,
            stockMaximo: $data['stock_maximo'] ?? null,
            precioCompra: $data['precio_compra'] ?? null,
            precioVenta: $data['precio_venta'] ?? null,
            margenSugerido: $data['margen_sugerido'] ?? null,
            impuesto: $data['impuesto'] ?? null,
            etiquetas: $data['etiquetas'] ?? null,
            fotos: $data['fotos'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'] ?? null,
            actualizarUsuarioId: $data['actualizar_usuario_id'] ?? null
        );
    }

    public static function fromRequest(array $data): self
    {
        return self::fromArray($data);
    }

    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'categoria_id' => $this->categoriaId,
            'proveedor_id' => $this->proveedorId,
            'codigo_barras' => $this->codigoBarras,
            'sku' => $this->sku,
            'codigo_interno' => $this->codigoInterno,
            'laboratorio' => $this->laboratorio,
            'forma_farmaceutica' => $this->formaFarmaceutica,
            'concentracion' => $this->concentracion,
            'presentacion' => $this->presentacion,
            'via_administracion' => $this->viaAdministracion,
            'unidad_medida' => $this->unidadMedida,
            'fracciones_por_unidad' => $this->fraccionesPorUnidad,
            'permite_fraccionar' => $this->permiteFraccionar,
            'lote' => $this->lote,
            'fecha_vencimiento' => $this->fechaVencimiento?->format('Y-m-d'),
            'registro_sanitario' => $this->registroSanitario,
            'refrigeracion_requerida' => $this->refrigeracionRequerida,
            'dias_para_alertar_vencimiento' => $this->diasParaAlertarVencimiento,
            'stock_actual' => $this->stockActual,
            'stock_minimo' => $this->stockMinimo,
            'stock_maximo' => $this->stockMaximo,
            'precio_compra' => $this->precioCompra,
            'precio_venta' => $this->precioVenta,
            'margen_sugerido' => $this->margenSugerido,
            'impuesto' => $this->impuesto,
            'etiquetas' => $this->etiquetas,
            'fotos' => $this->fotos,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'actualizar_usuario_id' => $this->actualizarUsuarioId,
        ], fn ($value) => $value !== null);
    }
}

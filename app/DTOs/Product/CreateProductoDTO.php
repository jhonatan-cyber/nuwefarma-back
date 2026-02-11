<?php

namespace App\DTOs\Product;

readonly class CreateProductoDTO
{
    public function __construct(
        public string $nombre,
        public ?string $categoriaId,
        public ?string $proveedorId,
        public ?string $codigoBarras,
        public ?string $sku,
        public ?string $codigoInterno,
        public ?string $laboratorio,
        public ?string $formaFarmaceutica,
        public ?string $concentracion,
        public ?string $presentacion,
        public ?string $viaAdministracion,
        public ?string $unidadMedida,
        public ?int $fraccionesPorUnidad,
        public ?bool $permiteFraccionar,
        public ?string $lote,
        public ?\DateTime $fechaVencimiento,
        public ?string $registroSanitario,
        public ?bool $refrigeracionRequerida,
        public ?int $diasParaAlertarVencimiento,
        public int $stockActual,
        public int $stockMinimo,
        public ?int $stockMaximo,
        public float $precioCompra,
        public float $precioVenta,
        public ?float $margenSugerido,
        public ?float $impuesto,
        public ?array $etiquetas,
        public ?array $fotos,
        public ?string $descripcion,
        public ?string $estado,
        public ?int $crearUsuarioId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'],
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
            fechaVencimiento: isset($data['fecha_vencimiento']) 
                ? new \DateTime($data['fecha_vencimiento']) 
                : null,
            registroSanitario: $data['registro_sanitario'] ?? null,
            refrigeracionRequerida: $data['refrigeracion_requerida'] ?? null,
            diasParaAlertarVencimiento: $data['dias_para_alertar_vencimiento'] ?? null,
            stockActual: $data['stock_actual'] ?? 0,
            stockMinimo: $data['stock_minimo'] ?? 0,
            stockMaximo: $data['stock_maximo'] ?? null,
            precioCompra: $data['precio_compra'],
            precioVenta: $data['precio_venta'],
            margenSugerido: $data['margen_sugerido'] ?? null,
            impuesto: $data['impuesto'] ?? null,
            etiquetas: $data['etiquetas'] ?? null,
            fotos: $data['fotos'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            estado: $data['estado'] ?? null,
            crearUsuarioId: $data['crear_usuario_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
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
            'crear_usuario_id' => $this->crearUsuarioId,
        ];
    }

    public function withUserId(int $userId): self
    {
        return new self(
            nombre: $this->nombre,
            categoriaId: $this->categoriaId,
            proveedorId: $this->proveedorId,
            codigoBarras: $this->codigoBarras,
            sku: $this->sku,
            codigoInterno: $this->codigoInterno,
            laboratorio: $this->laboratorio,
            formaFarmaceutica: $this->formaFarmaceutica,
            concentracion: $this->concentracion,
            presentacion: $this->presentacion,
            viaAdministracion: $this->viaAdministracion,
            unidadMedida: $this->unidadMedida,
            fraccionesPorUnidad: $this->fraccionesPorUnidad,
            permiteFraccionar: $this->permiteFraccionar,
            lote: $this->lote,
            fechaVencimiento: $this->fechaVencimiento,
            registroSanitario: $this->registroSanitario,
            refrigeracionRequerida: $this->refrigeracionRequerida,
            diasParaAlertarVencimiento: $this->diasParaAlertarVencimiento,
            stockActual: $this->stockActual,
            stockMinimo: $this->stockMinimo,
            stockMaximo: $this->stockMaximo,
            precioCompra: $this->precioCompra,
            precioVenta: $this->precioVenta,
            margenSugerido: $this->margenSugerido,
            impuesto: $this->impuesto,
            etiquetas: $this->etiquetas,
            fotos: $this->fotos,
            descripcion: $this->descripcion,
            estado: $this->estado,
            crearUsuarioId: $userId
        );
    }
}

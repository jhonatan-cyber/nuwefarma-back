<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 9px; color: #666; }
        .info-section { margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .info-item { background: #f8f9fa; padding: 8px; border-radius: 4px; }
        .info-label { font-size: 8px; color: #666; margin-bottom: 2px; }
        .info-value { font-weight: bold; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        th { background: #059669; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f0fdf4; }
        .totals { background: #e5e7eb; padding: 10px; border-radius: 4px; margin-top: 15px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals-row:last-child { margin-bottom: 0; font-weight: bold; font-size: 12px; border-top: 1px solid #333; padding-top: 5px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>{{ $tipo_comprobante }}</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Número de Compra</div>
                <div class="info-value">{{ $compra->numero_compra }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y H:i') }}</div>
            </div>
            @if($compra->proveedor)
            <div class="info-item">
                <div class="info-label">Proveedor</div>
                <div class="info-value">{{ $compra->proveedor->nombre }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">Usuario</div>
                <div class="info-value">{{ $compra->usuario?->nombre ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compra->productos as $producto)
            <tr>
                <td>{{ $producto->producto?->nombre ?? 'Producto' }}</td>
                <td style="text-align: center;">{{ $producto->cantidad }}</td>
                <td style="text-align: right;">{{ number_format($producto->precio_unitario, 2) }}</td>
                <td style="text-align: right;">{{ number_format($producto->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>{{ number_format($compra->subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Impuestos:</span>
            <span>{{ number_format($compra->impuestos, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Descuento:</span>
            <span>{{ number_format($compra->descuento_total ?? 0, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>TOTAL:</span>
            <span>{{ number_format($compra->total, 2) }}</span>
        </div>
    </div>

    @if($compra->notas)
    <div style="margin-top: 15px; padding: 10px; background: #fef9e7; border-radius: 4px;">
        <strong>Notas:</strong> {{ $compra->notas }}
    </div>
    @endif

    <div class="footer">
        <p>NuweFarma - Sistema de Gestión de Farmacia</p>
        <p>Este documento es un comprobante interno</p>
    </div>
</body>
</html>

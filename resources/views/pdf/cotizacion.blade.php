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
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-espera { background: #fef3c7; color: #92400e; }
        .badge-aceptada { background: #d1fae5; color: #065f46; }
        .badge-rechazada { background: #fee2e2; color: #991b1b; }
        .badge-expirada { background: #e5e7eb; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        th { background: #374151; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f9fafb; }
        .totals { background: #e5e7eb; padding: 10px; border-radius: 4px; margin-top: 15px; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals-row:last-child { margin-bottom: 0; font-weight: bold; font-size: 12px; border-top: 1px solid #333; padding-top: 5px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Cotización</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Número de Cotización</div>
                <div class="info-value">{{ $cotizacion->numero_cotizacion }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($cotizacion->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Estado</div>
                <div class="info-value">
                    @php
                        $badgeClass = match($cotizacion->estado) {
                            'aceptada' => 'badge-aceptada',
                            'rechazada' => 'badge-rechazada',
                            'expirada' => 'badge-expirada',
                            default => 'badge-espera'
                        };
                        $estadoLabel = match($cotizacion->estado) {
                            'aceptada' => 'Aceptada',
                            'rechazada' => 'Rechazada',
                            'expirada' => 'Expirada',
                            default => 'En Espera'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $estadoLabel }}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Válido hasta</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($cotizacion->fecha_vencimiento)->format('d/m/Y') }}</div>
            </div>
            @if($cotizacion->cliente)
            <div class="info-item">
                <div class="info-label">Cliente</div>
                <div class="info-value">{{ $cotizacion->cliente->nombre }}</div>
            </div>
            @endif
            <div class="info-item">
                <div class="info-label">Vendedor</div>
                <div class="info-value">{{ $cotizacion->usuario?->nombre ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Descuento</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cotizacion->productos as $producto)
            <tr>
                <td>{{ $producto->producto?->nombre ?? 'Producto' }}</td>
                <td style="text-align: center;">{{ $producto->cantidad }}</td>
                <td style="text-align: right;">{{ number_format($producto->precio_unitario, 2) }}</td>
                <td style="text-align: right;">{{ number_format($producto->descuento ?? 0, 2) }}</td>
                <td style="text-align: right;">{{ number_format($producto->subtotal, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No hay productos en esta cotización</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>{{ number_format($cotizacion->subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Impuestos:</span>
            <span>{{ number_format($cotizacion->impuesto ?? 0, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Descuento:</span>
            <span>{{ number_format($cotizacion->descuento ?? 0, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>TOTAL:</span>
            <span>{{ number_format($cotizacion->total, 2) }}</span>
        </div>
    </div>

    @if($cotizacion->notas)
    <div style="margin-top: 15px; padding: 10px; background: #fef9e7; border-radius: 4px;">
        <strong>Notas:</strong> {{ $cotizacion->notas }}
    </div>
    @endif

    <div class="footer">
        <p>NuweFarma - Sistema de Gestión de Farmacia</p>
        <p>Esta cotización tiene validez hasta la fecha indicada</p>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #333; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .entrada { color: #28a745; }
        .salida { color: #dc3545; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Producto: {{ $producto?->nombre }} ({{ $producto?->codigo_barras }})</p>
        <p>Lote: {{ $lote?->numero_lote }} | Stock Actual: {{ $lote?->stock }}</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    @if($resumen['fecha_inicio'] && $resumen['fecha_fin'])
    <p><strong>Período:</strong> {{ $resumen['fecha_inicio'] }} al {{ $resumen['fecha_fin'] }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo Movimiento</th>
                <th>Cantidad</th>
                <th>Stock Anterior</th>
                <th>Stock Nuevo</th>
                <th>Costo Unitario</th>
                <th>Usuario</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            <tr>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td class="{{ $mov->esEntrada() ? 'entrada' : 'salida' }}">
                    {{ $mov->getTipoFormateadoAttribute() }}
                </td>
                <td style="text-align: center; font-weight: bold;">
                    {{ $mov->esEntrada() ? '+' : '-' }}{{ $mov->cantidad }}
                </td>
                <td style="text-align: center;">{{ $mov->stock_anterior }}</td>
                <td style="text-align: center;">{{ $mov->stock_nuevo }}</td>
                <td style="text-align: right;">${{ number_format($mov->costo_unitario, 2) }}</td>
                <td>{{ $mov->usuario?->nombre ?? 'N/A' }}</td>
                <td>{{ $mov->observaciones ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-around; background: #f8f9fa; padding: 15px; border-radius: 4px; margin-top: 20px;">
        <div><strong>Total Entradas:</strong> {{ $resumen['entradas'] }}</div>
        <div><strong>Total Salidas:</strong> {{ $resumen['salidas'] }}</div>
        <div><strong>Stock Actual:</strong> {{ $resumen['stock_actual'] }}</div>
    </div>

    <div class="footer">
        <p>NuweFarma - Sistema de Gestión de Farmacia</p>
    </div>
</body>
</html>

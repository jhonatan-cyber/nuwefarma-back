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
        .alert { background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f59e0b; color: #000; font-weight: bold; }
        tr:nth-child(even) { background: #fef9e7; }
        .critico { background: #fecaca !important; }
        .color-rojo { color: #dc2626; }
        .color-verde { color: #059669; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Productos con stock por debajo del minimo</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    <div class="alert">
        ALERTA: {{ $total_productos }} productos requieren atencion
    </div>

    @foreach($productos as $item)
    <div style="margin-bottom: 20px; page-break-inside: avoid;">
        <h3 style="background: #333; color: #fff; padding: 8px; margin: 0;">{{ $item['producto']?->nombre }} ({{ $item['producto']?->codigo_barras }})</h3>
        <table>
            <thead>
                <tr>
                    <th>Lote</th>
                    <th>Stock Actual</th>
                    <th>Stock Minimo</th>
                    <th>Diferencia</th>
                    <th>Vencimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($item['lotes'] as $lote)
                @php $diferencia = $lote->stock - $lote->stock_minimo; @endphp
                @php $colorDiferencia = $diferencia <= 0 ? 'color-rojo' : 'color-verde'; @endphp
                <tr class="{{ $diferencia <= 0 ? 'critico' : '' }}">
                    <td>{{ $lote->numero_lote }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $lote->stock }}</td>
                    <td style="text-align: center;">{{ $lote->stock_minimo }}</td>
                    <td style="text-align: center;" class="{{ $colorDiferencia }}">{{ $diferencia }}</td>
                    <td>{{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
                <tr style="background: #e5e7eb; font-weight: bold;">
                    <td colspan="2">TOTAL</td>
                    <td style="text-align: center;">{{ $item['stock_total'] }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        <p>NuweFarma - Sistema de Gestion de Farmacia</p>
        <p>Este reporte requiere accion inmediata</p>
    </div>
</body>
</html>

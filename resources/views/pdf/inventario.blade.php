<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; text-align: left; font-size: 8px; }
        th { background: #333; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .vencido { background: #ffcccc !important; }
        .proximamente { background: #fff3cd !important; }
        .resumen { display: flex; justify-content: space-around; background: #333; color: #fff; padding: 15px; border-radius: 4px; margin-top: 20px; }
        .resumen-item { text-align: center; }
        .resumen-item .label { font-size: 9px; text-transform: uppercase; }
        .resumen-item .value { font-size: 16px; font-weight: bold; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                <th>Lote</th>
                <th>Stock</th>
                <th>Precio Costo</th>
                <th>Valor</th>
                <th>Vencimiento</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lotes as $lote)
            @php
                $diasVencer = $lote->dias_para_vencer;
                $rowClass = '';
                if ($lote->estado === 'vencido') $rowClass = 'vencido';
                elseif ($diasVencer !== null && $diasVencer <= 30) $rowClass = 'proximamente';
            @endphp
            <tr class="{{ $rowClass }}">
                <td>{{ $lote->producto?->nombre }}</td>
                <td>{{ $lote->producto?->codigo_barras }}</td>
                <td>{{ $lote->numero_lote }}</td>
                <td style="text-align: center;">{{ $lote->stock }}</td>
                <td style="text-align: right;">${{ number_format($lote->precio_costo, 2) }}</td>
                <td style="text-align: right;">${{ number_format($lote->stock * $lote->precio_costo, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td>{{ ucfirst($lote->estado) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="resumen">
        <div class="resumen-item">
            <div class="label">Total Lotes</div>
            <div class="value">{{ $resumen['total_lotes'] }}</div>
        </div>
        <div class="resumen-item">
            <div class="label">Stock Total</div>
            <div class="value">{{ number_format($resumen['stock_total']) }}</div>
        </div>
        <div class="resumen-item">
            <div class="label">Valor Inventario</div>
            <div class="value">${{ number_format($resumen['valor_total'], 2) }}</div>
        </div>
        <div class="resumen-item">
            <div class="label">Vencidos</div>
            <div class="value">{{ $resumen['lotes_vencidos'] }}</div>
        </div>
    </div>

    <div class="footer">
        <p>NuweFarma - Sistema de Gestión de Farmacia</p>
    </div>
</body>
</html>

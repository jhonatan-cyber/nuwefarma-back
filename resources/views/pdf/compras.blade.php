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
        .filtros { background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .filtros p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #333; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
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

    @if(!empty($filtros))
    <div class="filtros">
        <strong>Filtros aplicados:</strong>
        @if(isset($filtros['fecha_inicio'])) Desde: {{ $filtros['fecha_inicio'] }} @endif
        @if(isset($filtros['fecha_fin'])) Hasta: {{ $filtros['fecha_fin'] }} @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th># Compra</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Método Pago</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compras as $compra)
            <tr>
                <td>{{ $compra->numero_compra }}</td>
                <td>{{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y H:i') }}</td>
                <td>{{ $compra->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                <td>{{ $compra->usuario?->nombre }}</td>
                <td>{{ ucfirst($compra->estado) }}</td>
                <td>{{ ucfirst($compra->metodo_pago) }}</td>
                <td style="text-align: right;">${{ number_format($compra->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="resumen">
        <div class="resumen-item">
            <div class="label">Total Compras</div>
            <div class="value">${{ number_format($resumen['total_compras'], 2) }}</div>
        </div>
        <div class="resumen-item">
            <div class="label">Órdenes</div>
            <div class="value">{{ $resumen['total_ordenes'] }}</div>
        </div>
        <div class="resumen-item">
            <div class="label">Promedio</div>
            <div class="value">${{ number_format($resumen['promedio'], 2) }}</div>
        </div>
    </div>

    <div class="footer">
        <p>NuweFarma - Sistema de Gestión de Farmacia</p>
    </div>
</body>
</html>

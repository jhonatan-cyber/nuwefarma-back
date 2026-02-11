<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 9px; color: #666; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .summary-box { background: #f3f4f6; padding: 10px; border-radius: 4px; text-align: center; }
        .summary-box .label { font-size: 8px; color: #666; }
        .summary-box .value { font-size: 14px; font-weight: bold; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 8px; }
        th { background: #4b5563; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #f9fafb; }
        .entrada { background: #d1fae5 !important; }
        .salida { background: #fee2e2 !important; }
        .ajuste { background: #fef3c7 !important; }
        .color-entrada { color: #059669; }
        .color-salida { color: #dc2626; }
        .footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Reporte de movimientos de inventario</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    @if(!empty($filtros))
    <div style="margin-bottom: 15px; padding: 10px; background: #f3f4f6; border-radius: 4px;">
        <strong>Filtros aplicados:</strong>
        @if(!empty($filtros['fecha_inicio'])) Desde: {{ $filtros['fecha_inicio'] }} @endif
        @if(!empty($filtros['fecha_fin'])) Hasta: {{ $filtros['fecha_fin'] }} @endif
        @if(!empty($filtros['tipo_movimiento'])) Tipo: {{ $filtros['tipo_movimiento'] }} @endif
    </div>
    @endif

    <div class="summary">
        <div class="summary-box">
            <div class="label">Total Movimientos</div>
            <div class="value">{{ $total_movimientos }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Entradas</div>
            <div class="value">{{ $resumen_por_tipo->where('tipo', 'entrada_compra')->sum('cantidad') + $resumen_por_tipo->where('tipo', 'entrada_traslado')->sum('cantidad') }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Salidas</div>
            <div class="value">{{ $resumen_por_tipo->where('tipo', 'salida_venta')->sum('cantidad') + $resumen_por_tipo->where('tipo', 'salida_traslado')->sum('cantidad') }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Ajustes</div>
            <div class="value">{{ $resumen_por_tipo->where('tipo', 'ajuste_incremento')->count() + $resumen_por_tipo->where('tipo', 'ajuste_decremento')->count() }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Lote</th>
                <th>Cantidad</th>
                <th>Costo</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $movimiento)
            @php
                $tipo = $movimiento->tipo_movimiento;
                $esEntrada = in_array($tipo, ['entrada_compra', 'entrada_traslado', 'entrada_inicial']);
                $esSalida = in_array($tipo, ['salida_venta', 'salida_traslado', 'salida_merma']);
                $tipoClase = $esEntrada ? 'entrada' : ($esSalida ? 'salida' : 'ajuste');
                $colorClase = $esEntrada ? 'color-entrada' : ($esSalida ? 'color-salida' : '');
            @endphp
            <tr class="{{ $tipoClase }}">
                <td>{{ \Carbon\Carbon::parse($movimiento->created_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $tipo }}</td>
                <td>{{ $movimiento->lote?->producto?->nombre ?? 'N/A' }}</td>
                <td>{{ $movimiento->lote?->numero_lote ?? 'N/A' }}</td>
                <td style="text-align: center; font-weight: bold;" class="{{ $colorClase }}">
                    @if($esEntrada)+@endif{{ $movimiento->cantidad }}
                </td>
                <td style="text-align: right;">{{ number_format($movimiento->costo_total ?? 0, 2) }}</td>
                <td>{{ $movimiento->usuario?->nombre ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($resumen_por_tipo->count() > 0)
    <div style="margin-top: 15px;">
        <h4 style="margin-bottom: 10px;">Resumen por Tipo</h4>
        <table>
            <thead>
                <tr>
                    <th>Tipo de Movimiento</th>
                    <th>Movimientos</th>
                    <th>Total Unidades</th>
                    <th>Costo Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumen_por_tipo as $resumen)
                <tr>
                    <td>{{ $resumen['tipo'] }}</td>
                    <td style="text-align: center;">{{ $resumen['movimientos'] }}</td>
                    <td style="text-align: center;">{{ $resumen['cantidad'] }}</td>
                    <td style="text-align: right;">{{ number_format($resumen['costo_total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>NuweFarma - Sistema de Gestion de Farmacia</p>
    </div>
</body>
</html>

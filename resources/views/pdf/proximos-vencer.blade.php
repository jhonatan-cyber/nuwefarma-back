<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #dc2626; }
        .header h1 { font-size: 18px; color: #dc2626; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .alert { background: #fef2f2; padding: 15px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; border: 2px solid #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #dc2626; color: #fff; font-weight: bold; }
        tr:nth-child(even) { background: #fef2f2; }
        .urgente { background: #fecaca !important; }
        .color-rojo { color: #dc2626; }
        .color-verde { color: #059669; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 8px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo }}</h1>
        <p>Productos que vencern en los proximos {{ $dias_alerta }} dias</p>
        <p>Generado: {{ $fecha_generacion }}</p>
    </div>

    <div class="alert">
        ALERTA: {{ $total_lotes }} lotes proximos a vencer ({{ $total_unidades }} unidades en riesgo)
    </div>

    @if($lotes->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Lote</th>
                <th>Stock</th>
                <th>Fecha Vencimiento</th>
                <th>Dias Restantes</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lotes as $lote)
            @php
                $diasRestantes = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($lote->fecha_vencimiento), false);
                $urgente = $diasRestantes <= 7;
                $colorClase = $urgente ? 'color-rojo' : 'color-verde';
            @endphp
            <tr class="{{ $urgente ? 'urgente' : '' }}">
                <td>{{ $lote->producto?->nombre ?? 'N/A' }}</td>
                <td>{{ $lote->numero_lote }}</td>
                <td style="text-align: center;">{{ $lote->stock }}</td>
                <td>{{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}</td>
                <td style="text-align: center; font-weight: bold;" class="{{ $colorClase }}">
                    {{ $diasRestantes }} dias
                </td>
                <td style="text-align: right;">{{ number_format($lote->stock * $lote->precio_costo, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 30px; color: #059669;">
        <p style="font-size: 14px; font-weight: bold;">OK No hay productos proximos a vencer</p>
    </div>
    @endif

    <div class="footer">
        <p>NuweFarma - Sistema de Gestion de Farmacia</p>
        <p>Revisar inventario y tomar acciones preventivas</p>
    </div>
</body>
</html>

<?php

namespace App\Models;

use App\Models\Concerns\HasAuditoria;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caja extends Model
{
    use HasAuditoria, HasFactory, HasUuids;

    protected $table = 'cajas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'saldo_actual',
        'total_ingresos',
        'total_egresos',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'numero_caja',
        'nombre',
        'descripcion',
        'saldo_inicial',
        'saldo_actual',
        'total_ingresos',
        'total_egresos',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'usuario_id',
        'sucursal_id',
        'notas',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuario (cajero responsable)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    /**
     * Generar número de caja automático
     */
    public static function generateNumeroCaja(): string
    {
        $lastCaja = self::orderBy('created_at', 'desc')->first();
        $lastNumber = $lastCaja ? (int) substr($lastCaja->numero_caja, 4) : 0;

        return 'CAJA'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcular saldo actual basado en ingresos y egresos
     */
    public function calcularSaldoActual(): void
    {
        $this->saldo_actual = $this->saldo_inicial + $this->total_ingresos - $this->total_egresos;
        $this->save();
    }

    /**
     * Verificar si la caja puede ser cerrada
     */
    public function puedeSerCerrada(): bool
    {
        return $this->estado === 'abierta';
    }

    /**
     * Cerrar caja
     */
    public function cerrar(): void
    {
        if ($this->puedeSerCerrada()) {
            $this->estado = 'cerrada';
            $this->fecha_cierre = now()->toDateString();
            $this->save();
        }
    }

    /**
     * Abrir caja
     */
    public function abrir(): void
    {
        if ($this->estado === 'cerrada') {
            $this->estado = 'abierta';
            $this->fecha_cierre = null;
            $this->save();
        }
    }
}

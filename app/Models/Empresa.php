<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Map de columnas para empresas
 *
 * @property-read string $id
 * @property-read string $nit
 * @property-read string $razon_social
 * @property-read ?string $nombre_comercial
 * @property-read string $codigo_actividad
 * @property-read ?string $descripcion_actividad
 * @property-read ?string $municipio
 * @property-read ?string $departamento
 * @property-read ?string $direccion
 * @property-read ?string $telefono
 * @property-read ?string $correo_electronico
 * @property-read string $moneda
 * @property-read string $regimen
 * @property-read string $pais
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class Empresa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'empresas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public const MONEDA_BOB = 'BOB';

    public static function activa(): ?self
    {
        return self::where('estado', 'activo')->first();
    }

    public static function obtenerODefault(): self
    {
        $empresa = self::activa();

        if ($empresa) {
            return $empresa;
        }

        return self::create([
            'nit' => config('siat.nit', '1020405026'),
            'razon_social' => config('siat.razon_social', 'NuweFarma S.R.L.'),
            'nombre_comercial' => config('siat.nombre_comercial', 'NuweFarma'),
            'codigo_actividad' => config('siat.codigo_actividad', '477310'),
            'descripcion_actividad' => config('siat.descripcion_actividad', 'Venta al por mayor de productos farmacéuticos'),
            'municipio' => config('siat.municipio', 'La Paz'),
            'departamento' => config('siat.departamento', 'La Paz'),
            'direccion' => config('siat.direccion', 'Av. Principal 1234'),
            'telefono' => config('siat.telefono', '2 2345678'),
            'correo_electronico' => config('siat.correo', 'fiscal@nufarma.bo'),
            'moneda' => self::MONEDA_BOB,
            'regimen' => 'general',
            'pais' => 'Bolivia',
            'estado' => 'activo',
        ]);
    }
}
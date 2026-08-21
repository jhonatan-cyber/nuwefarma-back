<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Map de columnas para siat_credenciales
 *
 * @property-read string $id
 * @property-read string $nombre
 * @property-read string $valor_cifrado
 * @property-read string $ambiente
 * @property-read string $estado
 * @property-read ?\Illuminate\Support\Carbon $created_at
 * @property-read ?\Illuminate\Support\Carbon $updated_at
 */
class SiatCredencial extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'siat_credenciales';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getValorAttribute(): ?string
    {
        return $this->valor_cifrado ? Crypt::decryptString($this->valor_cifrado) : null;
    }

    public function setValorAttribute(?string $value): void
    {
        $this->attributes['valor_cifrado'] = $value === null ? null : Crypt::encryptString($value);
    }

    public static function obtener(string $nombre, ?string $ambiente = 'pruebas'): ?string
    {
        $credencial = self::where('nombre', $nombre)
            ->where('ambiente', $ambiente ?? 'pruebas')
            ->where('estado', 'activa')
            ->first();

        return $credencial?->valor;
    }

    public static function guardarCifrada(string $nombre, string $valor, string $ambiente = 'pruebas'): self
    {
        return self::updateOrCreate(
            ['nombre' => $nombre, 'ambiente' => $ambiente],
            ['valor_cifrado' => Crypt::encryptString($valor), 'estado' => 'activa']
        );
    }
}
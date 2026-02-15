<?php

namespace App\Constants;

class Roles
{
    public const ADMINISTRADOR = 'Administrador';

    public const GERENTE = 'Gerente';

    public const CAJERO = 'Cajero';

    public const VENDEDOR = 'Vendedor';

    public static function all(): array
    {
        return [
            self::ADMINISTRADOR,
            self::GERENTE,
            self::CAJERO,
            self::VENDEDOR,
        ];
    }

    public static function isAdministrador(string $rol): bool
    {
        return strtolower($rol) === strtolower(self::ADMINISTRADOR);
    }

    public static function isGerente(string $rol): bool
    {
        return strtolower($rol) === strtolower(self::GERENTE);
    }
}

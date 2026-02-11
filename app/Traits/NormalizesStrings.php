<?php

namespace App\Traits;

trait NormalizesStrings
{
    protected function normalizeInput(array $data): array
    {
        return collect($data)->map(function ($value) {
            return $value === '' ? null : $value;
        })->toArray();
    }
}

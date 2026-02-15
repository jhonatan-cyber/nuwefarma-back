<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Models\Caja;
use Illuminate\Validation\Rule;

class CreateCajaAction
{
    /**
     * Create a new cash register.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Caja
    {
        $validatedData = $this->validate($data);

        return Caja::create($validatedData);
    }

    /**
     * Validate the cash register data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data): array
    {
        return validator($data, [
            'nombre' => ['required', 'string', 'max:255'],
            'sucursal_id' => ['required', 'exists:sucursals,id'],
            'gerente_id' => ['required', 'exists:usuarios,id'],
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
            'saldo_actual' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'estado' => ['required', Rule::in(['abierta', 'cerrada'])],
        ])->validate();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_only_sees_cash_registers_from_their_branch(): void
    {
        [$cashier, $ownBranch, $otherBranch] = $this->cashierWithTwoBranches();

        $ownCaja = $this->createCaja('CAJA-A', $ownBranch->id, $cashier->id);
        $this->createCaja('CAJA-B', $otherBranch->id);

        $response = $this->withToken($cashier->createToken('branch-test')->plainTextToken)
            ->getJson('/api/v1/cajas');

        $response->assertOk()
            ->assertJsonFragment(['id' => $ownCaja->id])
            ->assertJsonMissing(['numero_caja' => 'CAJA-B']);
    }

    public function test_cashier_cannot_submit_another_branch_id(): void
    {
        [$cashier, , $otherBranch] = $this->cashierWithTwoBranches();

        $response = $this->withToken($cashier->createToken('branch-test')->plainTextToken)
            ->patchJson('/api/v1/cajas/non-existent/abrir', [
                'sucursal_id' => $otherBranch->id,
                'saldo_inicial' => 100,
            ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_cashier_cannot_create_or_delete_cash_registers(): void
    {
        [$cashier, $ownBranch] = $this->cashierWithTwoBranches();
        $token = $cashier->createToken('role-test')->plainTextToken;
        $caja = $this->createCaja('CAJA-A', $ownBranch->id, $cashier->id);

        $this->withToken($token)->postJson('/api/v1/cajas', [
            'numero_caja' => 'NUEVA',
            'nombre' => 'Nueva caja',
            'sucursal_id' => $ownBranch->id,
        ])->assertForbidden();

        $this->withToken($token)->deleteJson("/api/v1/cajas/{$caja->id}")
            ->assertForbidden();
    }

    private function cashierWithTwoBranches(): array
    {
        $role = Rol::factory()->create(['nombre' => 'Cajero']);
        $ownBranch = Sucursal::create([
            'nombre' => 'Sucursal A',
            'direccion' => 'Dirección A',
            'ciudad' => 'La Paz',
            'pais' => 'Bolivia',
            'estado' => 'activo',
        ]);
        $otherBranch = Sucursal::create([
            'nombre' => 'Sucursal B',
            'direccion' => 'Dirección B',
            'ciudad' => 'El Alto',
            'pais' => 'Bolivia',
            'estado' => 'activo',
        ]);
        $cashier = Usuario::factory()->create([
            'rol_id' => $role->id,
            'sucursal_id' => $ownBranch->id,
        ]);

        return [$cashier, $ownBranch, $otherBranch];
    }

    private function createCaja(string $number, string $branchId, ?string $userId = null): Caja
    {
        return Caja::withoutGlobalScopes()->create([
            'numero_caja' => $number,
            'nombre' => $number,
            'saldo_inicial' => 0,
            'saldo_actual' => 0,
            'estado' => 'abierta',
            'usuario_id' => $userId,
            'sucursal_id' => $branchId,
        ]);
    }
}

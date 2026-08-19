<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationalRoutesTest extends TestCase
{
    public function test_operational_module_routes_are_registered(): void
    {
        $expectedRoutes = [
            ['GET', 'api/v1/dashboard/metrics'],
            ['GET', 'api/v1/compras'],
            ['GET', 'api/v1/cotizaciones'],
            ['GET', 'api/v1/gastos'],
            ['GET', 'api/v1/sucursales'],
            ['GET', 'api/v1/lotes'],
            ['GET', 'api/v1/kardex/reportes/movimientos'],
            ['GET', 'api/v1/traslados'],
            ['GET', 'api/v1/ajustes-inventario'],
            ['GET', 'api/v1/notificaciones'],
            ['GET', 'api/v1/activity-logs'],
            ['GET', 'api/v1/pdf/ventas'],
        ];

        foreach ($expectedRoutes as [$method, $uri]) {
            $this->assertTrue(
                Route::getRoutes()->match(request()->create($uri, $method)) !== null,
                "Expected route [{$method} {$uri}] to be registered."
            );
        }
    }

    public function test_public_password_reset_routes_are_registered(): void
    {
        foreach (['api/v1/auth/forgot-password', 'api/v1/auth/reset-password'] as $uri) {
            $route = Route::getRoutes()->match(request()->create($uri, 'POST'));

            $this->assertNotContains('auth:sanctum', $route->gatherMiddleware());
        }
    }

    public function test_operational_routes_require_authentication(): void
    {
        $route = Route::getRoutes()->match(request()->create('api/v1/compras', 'GET'));

        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
    }

    public function test_static_routes_are_not_captured_by_resource_parameters(): void
    {
        $expectedActions = [
            'api/v1/clientes/con-deuda' => 'ClienteController@conDeuda',
            'api/v1/clientes/stats/overview' => 'ClienteController@statsOverview',
            'api/v1/ventas/pendientes' => 'VentaController@pendientes',
            'api/v1/ventas/por-fecha' => 'VentaController@porFecha',
            'api/v1/cajas/abiertas' => 'CajaController@abiertas',
            'api/v1/cajas/cerradas' => 'CajaController@cerradas',
        ];

        foreach ($expectedActions as $uri => $expectedAction) {
            $route = Route::getRoutes()->match(request()->create($uri, 'GET'));

            $this->assertStringEndsWith($expectedAction, $route->getActionName());
        }
    }

    public function test_spanish_resource_parameters_match_controller_arguments(): void
    {
        $expectedParameters = [
            'api/v1/proveedores/{proveedor}',
            'api/v1/cotizaciones/{cotizacion}',
            'api/v1/sucursales/{sucursal}',
            'api/v1/notificaciones/{notificacion}',
        ];

        $registeredUris = collect(Route::getRoutes()->getRoutes())->pluck('uri');

        foreach ($expectedParameters as $uri) {
            $this->assertTrue($registeredUris->contains($uri), "Expected route parameter in [{$uri}].");
        }
    }

    public function test_legacy_unversioned_module_routes_are_not_exposed(): void
    {
        $registeredUris = collect(Route::getRoutes()->getRoutes())->pluck('uri');

        $this->assertFalse($registeredUris->contains('api/compras'));
        $this->assertTrue($registeredUris->contains('api/v1/compras'));
    }

    public function test_unauthenticated_errors_follow_the_v1_contract(): void
    {
        $response = $this->getJson('/api/v1/compras', [
            'X-Request-ID' => 'test-request-1234',
        ]);

        $response->assertUnauthorized()
            ->assertHeader('X-Request-ID', 'test-request-1234')
            ->assertJson([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'request_id' => 'test-request-1234',
            ]);
    }

    public function test_raw_json_responses_are_normalized_by_the_v1_contract(): void
    {
        $response = $this->getJson('/api/v1/info', [
            'X-Request-ID' => 'contract-test-1234',
        ]);

        $response->assertOk()
            ->assertHeader('X-Request-ID', 'contract-test-1234')
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operación exitosa')
            ->assertJsonPath('request_id', 'contract-test-1234')
            ->assertJsonPath('data.name', 'NuweFarma API');
    }
}

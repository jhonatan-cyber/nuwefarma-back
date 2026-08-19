<?php

namespace Tests\Feature;

use App\Actions\Product\CreateProductoAction;
use App\DTOs\Product\CreateProductoDTO;
use App\Enums\EstadoEnum;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Repositories\ProductoRepository;
use App\ValueObjects\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductoBestPracticesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Usuario $usuario;

    private string $token;

    private ProductoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Rol::factory()->create(['nombre' => 'Administrador']);
        $this->usuario = Usuario::factory()->create(['rol_id' => $adminRole->id]);
        $this->token = $this->usuario->createToken('test-token')->plainTextToken;
        $this->repository = new ProductoRepository(new Producto);
    }

    /**
     * Test Action pattern implementation
     */
    public function test_create_producto_action(): void
    {
        $price = new ProductPrice(10.0, 15.0, 10.0);
        $action = new CreateProductoAction($price);

        $data = [
            'nombre' => 'Test Product',
            'precio_compra' => 10.0,
            'precio_venta' => 15.0,
            'impuesto' => 10.0,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'permite_fraccionar' => false,
            'refrigeracion_requerida' => false,
            'dias_para_alertar_vencimiento' => 60,
        ];

        $dto = CreateProductoDTO::fromArray($data);
        $producto = $action->execute($dto);

        $this->assertInstanceOf(Producto::class, $producto);
        $this->assertEquals('Test Product', $producto->nombre);
        $this->assertEquals(15.0, $producto->precio_venta);
        $this->assertEquals(EstadoEnum::ACTIVO->value, $producto->estado);
    }

    /**
     * Test Value Object pattern
     */
    public function test_product_price_value_object(): void
    {
        $price = new ProductPrice(10.0, 15.0, 10.0);

        $this->assertEquals(10.0, $price->getPurchasePrice());
        $this->assertEquals(15.0, $price->getSellingPrice());
        $this->assertEquals(10.0, $price->getTax());
        $this->assertEquals(50.0, $price->getMargin());
        $this->assertEquals(16.5, $price->getPriceWithTax());
        $this->assertEquals(5.0, $price->getProfit());
        $this->assertEquals(50.0, $price->getProfitPercentage());
        $this->assertTrue($price->isProfitable());
    }

    /**
     * Test DTO pattern
     */
    public function test_create_producto_dto(): void
    {
        $data = [
            'nombre' => 'Test Product',
            'precio_compra' => 10.0,
            'precio_venta' => 15.0,
            'stock_actual' => 100,
            'stock_minimo' => 10,
        ];

        $dto = CreateProductoDTO::fromArray($data);

        $this->assertEquals('Test Product', $dto->nombre);
        $this->assertEquals(10.0, $dto->precioCompra);
        $this->assertEquals(15.0, $dto->precioVenta);
        $this->assertEquals(100, $dto->stockActual);
        $this->assertEquals(10, $dto->stockMinimo);
    }

    /**
     * Test Repository pattern
     */
    public function test_producto_repository(): void
    {
        // Create test product
        $producto = Producto::factory()->create();

        // Update with specific values
        $producto->update([
            'nombre' => 'Test Product',
            'estado' => EstadoEnum::ACTIVO->value,
            'permite_fraccionar' => false,
            'refrigeracion_requerida' => false,
            'dias_para_alertar_vencimiento' => 60,
        ]);

        // Test findById
        $found = $this->repository->findById($producto->id);
        $this->assertInstanceOf(Producto::class, $found);
        $this->assertEquals($producto->id, $found->id);

        // Test activos
        $activos = $this->repository->activos();
        $this->assertGreaterThan(0, $activos->count());

        // Test create
        $dto = CreateProductoDTO::fromArray([
            'nombre' => 'New Product',
            'precio_compra' => 5.0,
            'precio_venta' => 8.0,
            'stock_actual' => 50,
            'stock_minimo' => 5,
        ]);

        $newProducto = $this->repository->create($dto);
        $this->assertInstanceOf(Producto::class, $newProducto);
        $this->assertEquals('New Product', $newProducto->nombre);
    }

    /**
     * Test Event-driven architecture
     */
    public function test_product_created_event(): void
    {
        \Event::fake();

        $price = new ProductPrice(10.0, 15.0, 10.0);
        $action = new CreateProductoAction($price);

        $dto = CreateProductoDTO::fromArray([
            'nombre' => 'Test Product',
            'precio_compra' => 10.0,
            'precio_venta' => 15.0,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'permite_fraccionar' => false,
            'refrigeracion_requerida' => false,
            'dias_para_alertar_vencimiento' => 60,
        ]);

        $producto = $action->execute($dto);

        \Event::assertDispatched(\App\Events\Product\ProductCreated::class, function ($event) use ($producto) {
            return $event->producto->id === $producto->id;
        });
    }

    /**
     * Test API with best practices
     */
    public function test_api_with_best_practices(): void
    {
        $data = [
            'nombre' => 'API Test Product',
            'precio_compra' => 10.0,
            'precio_venta' => 15.0,
            'stock_actual' => 100,
            'stock_minimo' => 10,
            'categoria_id' => null,
            'proveedor_id' => null,
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Request-ID' => 'test-request-id',
            'X-Client-Version' => '1.0.0',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/productos', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'nombre',
                        'precio_venta',
                        'stock_actual',
                        'estado',
                    ],
                ],
            ]);

        // Check security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-API-Version', '2.0');
    }

    /**
     * Test validation with custom request
     */
    public function test_validation_with_custom_request(): void
    {
        $data = [
            'nombre' => '', // Invalid: empty
            'precio_compra' => -10, // Invalid: negative
            'precio_venta' => -5, // Invalid: negative
            'stock_actual' => -5, // Invalid: negative
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Request-ID' => 'test-request-id',
            'X-Client-Version' => '1.0.0',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/productos', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'nombre',
                    'precio_compra',
                    'precio_venta',
                    'stock_actual',
                ],
            ]);
    }

    /**
     * Test error handling patterns
     */
    public function test_error_handling_patterns(): void
    {
        // Test not found error
        $uuid = \Illuminate\Support\Str::uuid();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson("/api/v1/productos/{$uuid}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Producto no encontrado',
            ]);
    }

    /**
     * Test caching patterns
     */
    public function test_caching_patterns(): void
    {
        // Create test product
        $producto = Producto::factory()->create();

        // First call should cache the result
        $found1 = $this->repository->findById($producto->id);

        // Second call should hit cache
        $found2 = $this->repository->findById($producto->id);

        $this->assertEquals($found1->id, $found2->id);
        $this->assertEquals($found1->nombre, $found2->nombre);
    }

    /**
     * Test performance optimization
     */
    public function test_performance_optimization(): void
    {
        // Create multiple products
        Producto::factory()->count(50)->create();

        $startTime = microtime(true);

        // Test optimized query
        $productos = $this->repository->paginate(15, ['estado' => 'activo']);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $responseTime, 'Query should complete in under 100ms');
        $this->assertCount(15, $productos->items());
    }

    /**
     * Test bulk operations
     */
    public function test_bulk_operations(): void
    {
        // Create test products
        $productos = Producto::factory()->count(3)->create();

        $updates = [];
        foreach ($productos as $producto) {
            $updates[$producto->id] = [
                'stock_actual' => $producto->stock_actual + 10,
                'precio_venta' => $producto->precio_venta + 1.0,
            ];
        }

        $affected = $this->repository->bulkUpdate($updates);

        $this->assertEquals(3, $affected);

        // Verify updates
        foreach ($productos as $producto) {
            $updated = $this->repository->findById($producto->id);
            $this->assertEquals($producto->stock_actual + 10, $updated->stock_actual);
            $this->assertEquals($producto->precio_venta + 1.0, $updated->precio_venta);
        }
    }

    /**
     * Test security patterns
     */
    public function test_security_patterns(): void
    {
        // Test unauthorized access
        $response = $this->getJson('/api/v1/productos');
        $response->assertStatus(401);

        // Test authenticated request with default headers
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/productos');

        $response->assertStatus(200)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'meta',
                    'links',
                ],
            ]);
    }
}

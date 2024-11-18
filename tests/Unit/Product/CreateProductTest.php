<?php

namespace Tests\Unit\Product;

use App\Shared\Testing\TestCase;
use App\Product\Domain\Entity\Product;
use App\Product\Infrastructure\Repository\ProductRepository;

class CreateProductTest extends TestCase
{
    protected array $fixtures = [
        ProductFixture::class
    ];

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->container->get(ProductRepository::class);
    }

    public function testCreateProduct(): void
    {
        $data = [
            'name' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test Description'
        ];

        $response = $this->actingAs($this->createUser())
            ->json('POST', '/api/products', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'price',
                'created_at'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => 99.99
        ]);
    }

    public function testCreateProductValidation(): void
    {
        $response = $this->json('POST', '/api/products', []);

        $response->assertStatus(400)
            ->assertJson([
                'errors' => [
                    'name' => ['The name field is required'],
                    'price' => ['The price field is required']
                ]
            ]);
    }
} 
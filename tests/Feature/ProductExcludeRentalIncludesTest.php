<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\RentalInclude;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductExcludeRentalIncludesTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey;
    protected $brand;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an API key for testing
        $this->apiKey = ApiKey::create([
            'name' => 'Test API Key',
            'key' => 'test-api-key-' . uniqid(),
            'active' => true,
            'expires_at' => null,
        ]);
        
        // Create shared brand and category for tests
        $this->brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
        ]);
        
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    /** @test */
    public function it_returns_all_products_when_exclude_rental_includes_is_false()
    {
        $parentProduct = Product::create([
            'name' => 'Camera Package',
            'price' => 100000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        $rentalIncludeProduct = Product::create([
            'name' => 'Camera Lens',
            'price' => 50000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        $standaloneProduct = Product::create([
            'name' => 'Standalone Product',
            'price' => 75000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        // Create rental include relationship
        RentalInclude::create([
            'product_id' => $parentProduct->id,
            'include_product_id' => $rentalIncludeProduct->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/products?exclude_rental_includes=false', [
            'X-API-KEY' => $this->apiKey->key
        ]);

        $response->assertStatus(200);
        
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        
        // Should include all products
        $this->assertContains($parentProduct->id, $productIds);
        $this->assertContains($rentalIncludeProduct->id, $productIds);
        $this->assertContains($standaloneProduct->id, $productIds);
    }

    /** @test */
    public function it_excludes_rental_include_products_when_exclude_rental_includes_is_true()
    {
        $parentProduct = Product::create([
            'name' => 'Camera Package 2',
            'price' => 100000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        $rentalIncludeProduct = Product::create([
            'name' => 'Camera Lens 2',
            'price' => 50000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        $standaloneProduct = Product::create([
            'name' => 'Standalone Product 2',
            'price' => 75000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        // Create rental include relationship
        RentalInclude::create([
            'product_id' => $parentProduct->id,
            'include_product_id' => $rentalIncludeProduct->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/products?exclude_rental_includes=true', [
            'X-API-KEY' => $this->apiKey->key
        ]);

        $response->assertStatus(200);
        
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        
        // Should include parent and standalone products but exclude rental include product
        $this->assertContains($parentProduct->id, $productIds);
        $this->assertNotContains($rentalIncludeProduct->id, $productIds);
        $this->assertContains($standaloneProduct->id, $productIds);
    }

    /** @test */
    public function it_defaults_to_false_when_parameter_not_provided()
    {
        $parentProduct = Product::create([
            'name' => 'Camera Package 3',
            'price' => 100000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        $rentalIncludeProduct = Product::create([
            'name' => 'Camera Lens 3',
            'price' => 50000,
            'status' => 'available',
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
        ]);
        
        // Create rental include relationship
        RentalInclude::create([
            'product_id' => $parentProduct->id,
            'include_product_id' => $rentalIncludeProduct->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/products', [
            'X-API-KEY' => $this->apiKey->key
        ]);

        $response->assertStatus(200);
        
        $productIds = collect($response->json('data'))->pluck('id')->toArray();
        
        // Should include all products by default
        $this->assertContains($parentProduct->id, $productIds);
        $this->assertContains($rentalIncludeProduct->id, $productIds);
    }
}

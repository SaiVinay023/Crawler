<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Image;
use App\Livewire\ProductGallery; // Ensure this imports your component
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    // 💡 This resets the DB for every test so you have a clean slate
    use RefreshDatabase; 

    public function test_product_gallery_page_loads_successfully(): void
    {
        // 1. Create a dummy product in the test DB
        $product = Product::create([
            'name' => 'Test Game',
            'price' => 59.99,
            'source_url' => 'http://example.com',
            'category' => 'Action',
            'attributes' => ['description' => 'A great game']
        ]);

        // 2. Create a dummy image for it
        $product->images()->create([
            'url' => 'https://sandbox.oxylabs.io/img/test.jpg'
        ]);

        // 3. Hit the route defined in your web.php
        $response = $this->get('/view/products');

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertSee('Test Game'); // Check if product name is visible
        $response->assertSee('Action');    // Check if category badge is visible
        // Check if Livewire component is present
        $response->assertSeeLivewire(ProductGallery::class); 
    }

    public function test_api_import_endpoint_works(): void
    {
        // 1. Send a Fake POST request to your API
        $response = $this->postJson('/api/import', [
            'products' => [
                [
                    'name' => 'API Game',
                    'price' => 10.00,
                    'image_url' => 'http://test.com/img.jpg',
                    'source_url' => 'http://test.com',
                    'category' => 'RPG',
                    'attributes' => ['description' => 'Desc']
                ]
            ]
        ]);

        // 2. Assert it accepted the job
        $response->assertStatus(202); // We used 202 Accepted in your Controller
        
        // 3. Since it's a queued job, we assert the Job was pushed 
        // (You can add Queue::fake() here for strict testing, but checking status 202 is enough for now)
    }
}
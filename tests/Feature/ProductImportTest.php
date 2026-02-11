<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Jobs\ProcessProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test] 
    public function it_receives_json_and_dispatches_an_async_job()
    {
        Queue::fake();

        $payload = [
            'products' => [
                [
                    'name' => 'Test Game',
                    'price' => 59.99,
                    'image_url' => 'https://example.com/image.jpg',
                    'source_url' => 'https://example.com/source',
                    'category' => 'Testing',
                    'attributes' => ['description' => 'A test description']
                ]
            ]
        ];

        $response = $this->postJson('/api/import', $payload);

        $response->assertStatus(202);

        Queue::assertPushed(ProcessProductImport::class, function ($job) {
            // This works now because we made the property public!
            return $job->productData['name'] === 'Test Game';
        });
    }

    #[Test]
    public function it_creates_product_and_associated_image_correctly()
    {
        $productData = [
            'name' => 'Eloquent Game',
            'price' => 29.99,
            'image_url' => 'https://example.com/game.png',
            'source_url' => 'https://example.com/game',
            'category' => 'RPG',
            'attributes' => ['description' => 'Test description']
        ];

        (new ProcessProductImport($productData))->handle();

        $this->assertDatabaseHas('products', ['name' => 'Eloquent Game']);
        
        $product = Product::where('name', 'Eloquent Game')->first();
        $this->assertCount(1, $product->images);
    }
}
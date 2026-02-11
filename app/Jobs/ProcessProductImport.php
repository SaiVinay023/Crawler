<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessProductImport implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function __construct(public array $productData) {}

    public function handle(): void
    {
        DB::transaction(function () {
            // 1. Create or Update the Product
            $product = Product::updateOrCreate(
                ['name' => $this->productData['name']],
                [
                    'price'      => $this->productData['price'],
                    'source_url' => $this->productData['source_url'],
                    'category'   => $this->productData['category'] ?? 'Uncategorized',
                    'attributes' => $this->productData['attributes'],
                ]
            );

            // 2. Handle the Image relationship (Product has many Images)
            if (!empty($this->productData['image_url'])) {
                $product->images()->updateOrCreate(
                    ['url' => $this->productData['image_url']]
                );
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Import failed for: " . ($this->productData['name'] ?? 'Unknown'), [
            'error' => $exception->getMessage()
        ]);
    }
}
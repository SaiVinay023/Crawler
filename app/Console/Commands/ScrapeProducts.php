<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Panther\Client;
use Illuminate\Support\Facades\Http;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products';
    protected $description = 'Scrape products using a Headless Browser (Panther)';

    public function handle()
    {
        if ($this->confirm('Clear existing products?')) {
            \App\Models\Product::query()->delete();
            $this->info("Database cleared.");
        }

        $this->info("Starting Panther (Headless Chrome)...");

        // 1. Connect to the Selenium Container we added to Docker
        $client = Client::createSeleniumClient('http://selenium:4444/wd/hub');
        
        try {
            // 2. Load the Page
            $url = 'https://sandbox.oxylabs.io/products';
            $client->request('GET', $url);

            // We wait until the class ".product-card" actually appears in the DOM
            $this->info("Waiting for JavaScript to render...");
            $client->waitFor('.product-card', 10); 
            
            // 4. Scrape the Rendered Page
            $crawler = $client->getCrawler();
            
            $scrapedProducts = [];
            
            $crawler->filter('.product-card')->each(function ($node) use (&$scrapedProducts, $client) {
                
                $title = $node->filter('h4')->count() ? $node->filter('h4')->text() : 'No Title';
                if ($title === 'No Title') return;

                // Price
                $priceText = $node->filter('.price-wrapper')->count() ? $node->filter('.price-wrapper')->text() : '0';
                $price = (float) str_replace(',', '.', preg_replace('/[^0-9,]/', '', $priceText));

                // Image - Now accessible because JS ran!
                $imageUrl = null;
                if ($node->filter('img')->count()) {
                    $imageUrl = $node->filter('img')->attr('src');
                }

                // Category
                $category = 'General';
                if ($node->filter('.category')->count()) {
                    $category = $node->filter('.category')->text();
                }

                // Description (We can click into details if needed, or grab summary)
                // For speed, let's grab the visible description
                $description = $node->filter('.description')->count() ? $node->filter('.description')->text() : '';
                
                // Link
                $link = $node->filter('a')->attr('href');

                // Normalize URL
                if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                    $imageUrl = 'https://sandbox.oxylabs.io' . $imageUrl;
                }

                $scrapedProducts[] = [
                    'name'        => trim($title),
                    'price'       => $price,
                    'image_url'   => $imageUrl,
                    'source_url'  => 'https://sandbox.oxylabs.io' . $link,
                    'category'    => trim($category),
                    'attributes'  => ['description' => trim($description)],
                ];

                $this->line("Found: $title ✅");
            });

            // 5. Send to API
            $this->info("Sending " . count($scrapedProducts) . " products to API...");
            
            $response = Http::post('http://laravel.test/api/import', [
                'products' => $scrapedProducts
            ]);

            if ($response->successful()) {
                $this->info("🚀 Success! Import queued.");
            } else {
                $this->error("API Error: " . $response->status());
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        } finally {
            // Always close the browser to free up memory
            $client->quit();
        }
    }
}
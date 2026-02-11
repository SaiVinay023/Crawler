<?php

namespace App\Console\Commands;

use App\Models\Product; 
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products';
    protected $description = 'Extracts product data from the sandbox';

       public function handle()
    {
        if ($this->confirm('Do you want to clear existing products before scraping?')) {
            \App\Models\Product::query()->delete();
            $this->info("Database cleared.");
        }

        $this->info("Starting scraper (Hybrid Mode)...");
        $client = new Client(['verify' => false, 'timeout' => 20]);
        $url = 'https://sandbox.oxylabs.io/products';

        try {
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            
            $imagesList = [];
            
            // Grab the hidden Next.js data blob
            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                $jsonProducts = $jsonData['props']['pageProps']['products'] ?? [];
                
                // Build a simple list of images
                foreach ($jsonProducts as $item) {
                    // Try common keys for image
                    $img = $item['image'] ?? $item['img'] ?? $item['thumbnail'] ?? $item['url'] ?? null;
                    $imagesList[] = $img;
                }
                $this->info("✅ Extracted " . count($imagesList) . " high-res images from hidden data.");
            } else {
                $this->warn("⚠️ Could not find hidden image data. Images might be missing.");
            }

        } catch (\Exception $e) {
            $this->error("Failed to fetch main page: " . $e->getMessage());
            return;
        }

        $scrapedProducts = [];

       
        
        // Notice we added '$i' to track which product we are on
        $crawler->filter('.product-card')->each(function (Crawler $node, $i) use (&$scrapedProducts, $imagesList) {
            
            $title = $node->filter('h4.title')->count() ? $node->filter('h4.title')->text() : 'No Title';
            if ($title === 'No Title') return;

            // 1. Category
            $category = $node->filter('.category span')->each(fn($s) => trim($s->text()));
            $categoryString = implode(', ', array_filter($category));

            // Instead of deep scraping, we just take the image from our JSON list at the same index
            $imageUrl = $imagesList[$i] ?? null;

            // Normalize Image URL
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = 'https://sandbox.oxylabs.io' . (str_starts_with($imageUrl, '/') ? '' : '/') . $imageUrl;
            }

            // 3. Price
            $priceText = $node->filter('.price-wrapper')->count() ? $node->filter('.price-wrapper')->text() : '0';
            $price = (float) str_replace(',', '.', preg_replace('/[^0-9,]/', '', $priceText));

            
            $description = $node->filter('.description')->count() ? $node->filter('.description')->text() : '';

            $productPath = $node->filter('a.card-header')->count() ? $node->filter('a.card-header')->attr('href') : '';

            $scrapedProducts[] = [
                'name' => trim($title),
                'price' => $price,
                'image_url' => $imageUrl,
                'source_url' => 'https://sandbox.oxylabs.io' . $productPath,
                'category' => substr($categoryString, 0, 255),
                'attributes' => ['description' => trim($description)],
            ];
            
            $this->line("🔍 Prepared: " . trim($title));
        });

        // 5. Send to API
        $this->info("Sending " . count($scrapedProducts) . " products to API...");
        
        try {
            $response = Http::post('http://laravel.test/api/import', [
                'products' => $scrapedProducts
            ]);

            if ($response->successful()) {
                $this->info("🚀 Success! API received data.");
            } else {
                $this->error("API Error: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
        }
    } 
   
}
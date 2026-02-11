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
        $this->info("Starting scraper...");
        $client = new Client(['verify' => false]);
        $url = 'https://sandbox.oxylabs.io/products';

        try {
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);
        } catch (\Exception $e) {
            $this->error("Failed to fetch URL: " . $e->getMessage());
            return;
        }

        $scrapedProducts = [];

        $crawler->filter('.product-card')->each(function (Crawler $node) use (&$scrapedProducts) {
            $title = $node->filter('h4.title')->count() ? $node->filter('h4.title')->text() : 'No Title';
            if ($title === 'No Title') return;

            // 1. Category
            $category = $node->filter('.category span')->each(fn($s) => trim($s->text()));
            $categoryString = implode(', ', array_filter($category));

           /* // 2. Image Logic
            $imageUrl = null;

            // Try <source> inside <picture> first
            $sourceNode = $node->filter('picture.product-image source');
            if ($sourceNode->count()) {
                $srcset = $sourceNode->attr('srcset'); // FIXED: Removed stray $
                $firstCandidate = explode(',', $srcset)[0];
                $imageUrl = trim(explode(' ', trim($firstCandidate))[0]);
            }

            // Fallback to <img> if <source> was empty
            if (!$imageUrl) {
                $imgNode = $node->filter('img.image');
                if ($imgNode->count()) {
                    $src = $imgNode->attr('src');
                    $srcset = $imgNode->attr('srcset');

                    if ($src && !str_contains($src, 'base64')) {
                        $imageUrl = $src;
                    } elseif ($srcset) {
                        $firstCandidate = explode(',', $srcset)[0];
                        $imageUrl = trim(explode(' ', trim($firstCandidate))[0]);
                    }
                }
            }

            // Ensure the URL is absolute
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = 'https://sandbox.oxylabs.io' . (str_starts_with($imageUrl, '/') ? '' : '/') . $imageUrl;
            } */
            // 2. Image Logic: from product DETAIL page instead of list card
$imageUrl = null;

// Build product URL from card link
$productPath = $node->filter('a.card-header')->count()
    ? $node->filter('a.card-header')->attr('href')
    : null;

$productUrl = $productPath
    ? 'https://sandbox.oxylabs.io' . (str_starts_with($productPath, '/') ? '' : '/') . $productPath
    : null;

if ($productUrl) {
    try {
        // Reuse Guzzle client from outer scope (pass via use(...) if needed)
        $productHtml = (new \GuzzleHttp\Client(['verify' => false]))
            ->get($productUrl)
            ->getBody()
            ->getContents();

        $productCrawler = new Crawler($productHtml);

        // Detail page SVG <img>
        $imgNode = $productCrawler->filter('.product-image img');
        if ($imgNode->count()) {
            $src = $imgNode->attr('src');          // e.g. "/images/games/1.svg"
            if ($src && !str_starts_with($src, 'data:')) {
                $imageUrl = $src;
            }
        }
    } catch (\Exception $e) {
        // optional: log or ignore
    }
}

// Normalize to absolute
if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
    $imageUrl = 'https://sandbox.oxylabs.io' . (str_starts_with($imageUrl, '/') ? '' : '/') . $imageUrl;
}

            // 3. Price
            $priceText = $node->filter('.price-wrapper')->count() ? $node->filter('.price-wrapper')->text() : '0';
            $price = (float) str_replace(',', '.', preg_replace('/[^0-9,]/', '', $priceText));

            // 4. Collect Data
            $scrapedProducts[] = [
                'name' => trim($title),
                'price' => $price,
                'image_url' => $imageUrl,
                'source_url' => 'https://sandbox.oxylabs.io' . ($node->filter('a.card-header')->count() ? $node->filter('a.card-header')->attr('href') : ''),
                'category' => substr($categoryString, 0, 255),
                'attributes' => ['description' => $node->filter('.description')->count() ? $node->filter('.description')->text() : ''],
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
                $this->info("API received data. Import started asynchronously.");
            } else {
                $this->error("API Error: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
        }
    }
}
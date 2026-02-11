

---

# 🎮 GameVault Scraper & Async Importer

A high-performance Laravel 12 application that scrapes product data asynchronously using a producer-consumer architecture.

## 🚀 Quick Start (Sail)

Ensure you have Docker installed and running.

### 1. Installation & Environment

```bash
# Clone and enter the directory
cd crawler

# Install dependencies
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Create environment file
cp .env.example .env

# Start Laravel Sail
./vendor/bin/sail up -d

```

### 2. Database & API Setup

```bash
# Generate app key
./vendor/bin/sail artisan key:generate

# Run migrations (creates products and images tables)
./vendor/bin/sail artisan migrate

# Install API scaffolding (Required for Laravel 11+)
./vendor/bin/sail artisan install:api

# Clear route cache to register /api/import
./vendor/bin/sail artisan route:clear

```

### 3. Execution Pipeline

To satisfy the **Asynchronous Import** requirement, you must run the producer and consumer in parallel.

**Tab A: The Consumer (Background Worker)**
Keep this running to process the incoming JSON data.

```bash
./vendor/bin/sail artisan queue:work

```

**Tab B: The Producer (Scraper)**
Run the scraper to extract data and POST it to the API.

```bash
./vendor/bin/sail php artisan scrape:products

```

---

## 🛠 Project Architecture

1. **Crawler (Command):** Extracts data from Oxylabs Sandbox using `Guzzle` and `DomCrawler`. It specifically targets `<picture>` and `<source>` tags for high-res images.
2. **API (`POST /api/import`):** Receives JSON payloads and dispatches background Jobs.
3. **Queue Job (`ProcessProductImport`):** Handles the database logic, managing the `1:N` relationship between `Products` and `Images`.
4. **Admin (`Filament`):** Provides a dashboard to manage scraped items with image previews.
5. **Frontend (`Livewire + AlpineJS`):** A responsive gallery with dynamic sorting and 25-per-page pagination.

---

## 🧪 Testing

Run the automated feature tests to verify the API and Relationship logic:

```bash
./vendor/bin/sail artisan test

```

---

## 🆘 Troubleshooting & Common Issues

### 1. `404 Not Found` on API Import

* **Cause:** API routes aren't loaded or cached.
* **Fix:** Ensure `api: __DIR__.'/../routes/api.php'` is present in `bootstrap/app.php` and run:
```bash
./vendor/bin/sail artisan route:clear

```



### 2. `Connection Refused` in Scraper

* **Cause:** Docker internal networking cannot resolve `localhost`.
* **Fix:** In `ScrapeProducts.php`, ensure the `Http::post` URL is set to `http://laravel.test/api/import` (Sail's internal service name).

### 3. `Command "scrape:products" not defined`

* **Cause:** Syntax error in the command file or stale bootstrap cache.
* **Fix:**
```bash
# Check for syntax errors
php -l app/Console/Commands/ScrapeProducts.php
# Clear discovery cache
./vendor/bin/sail artisan optimize:clear

```



### 4. Images are not showing in the Gallery

* **Cause:** The queue worker isn't running, or the sandbox URLs are relative.
* **Fix:** Ensure `./vendor/bin/sail artisan queue:work` is active. Our scraper automatically normalizes relative URLs to `https://sandbox.oxylabs.io/`.

### 5. UI looks "Black and White" (No Styles)

* **Cause:** Tailwind CSS v4 assets aren't compiled.
* **Fix:** Run the Vite development server:
```bash
./vendor/bin/sail npm run dev

```



---

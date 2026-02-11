<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Crawler</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans">
    <nav class="bg-white shadow-sm mb-8 py-4">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-xl font-bold text-gray-800">Crawler Gallery</h1>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductImportController;

// This defines POST http://laravel.test/api/import
Route::post('/import', [ProductImportController::class, 'store']);
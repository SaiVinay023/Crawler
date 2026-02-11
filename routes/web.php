<?php

use App\Livewire\ProductGallery;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// Full-page Livewire component
Route::get('/view/products', ProductGallery::class);
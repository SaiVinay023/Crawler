<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessProductImport;

class ProductImportController extends Controller
{
    public function store(Request $request)
    {
        // Validate that we received an array of products
        $request->validate(['products' => 'required|array']);

        foreach ($request->products as $productData) {
            // This is where the "Asynchronous" requirement is met
            ProcessProductImport::dispatch($productData);
        }

        return response()->json([
            'message' => 'Import successfully queued for ' . count($request->products) . ' products.'
        ], 202);
    }
}
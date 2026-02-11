<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductGallery extends Component
{
    use WithPagination;

    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        return view('livewire.product-gallery', [
            // Requirement: 25 per page + Eager load images relationship
            'products' => Product::with('images')
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate(25),
        ])->layout('layouts.app');
    }
}
<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class ProductManager extends Component
{
    public $products = [];

    public $categories = [];

    public $showModal = false;

    public $productId = null;

    public $name = '';

    public $price = '';

    public $categoryId = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->products = Product::with('category')->get();
        $this->categories = Category::all();
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->price = number_format($product->price / 100, 2, '.', '');
        $this->categoryId = $product->category_id;
        $this->showModal = true;
    }

    public function create()
    {
        $this->productId = null;
        $this->name = '';
        $this->price = '';
        $this->categoryId = $this->categories->first()->id ?? '';
        $this->showModal = true;
    }

    public function save()
    {
        $priceCents = (int) ($this->price * 100);

        if ($this->productId) {
            Product::find($this->productId)->update([
                'name' => $this->name,
                'price' => $priceCents,
                'category_id' => $this->categoryId,
            ]);
        } else {
            Product::create([
                'name' => $this->name,
                'price' => $priceCents,
                'category_id' => $this->categoryId,
            ]);
        }

        $this->showModal = false;
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.product-manager')->layout('layouts.admin');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Component;

class CategoryManager extends Component
{
    public $categories = [];

    public $showModal = false;

    public $categoryId = null;

    public $name = '';

    public $slug = '';

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->categories = Category::withCount('products')->get();
    }

    public function edit($id)
    {
        $category = Category::find($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->showModal = true;
    }

    public function create()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->slug = '';
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->categoryId) {
            Category::find($this->categoryId)->update([
                'name' => $this->name,
                'slug' => $this->slug ?: Str::slug($this->name),
            ]);
        } else {
            Category::create([
                'name' => $this->name,
                'slug' => $this->slug ?: Str::slug($this->name),
            ]);
        }

        $this->showModal = false;
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.admin.category-manager')->layout('layouts.admin');
    }
}

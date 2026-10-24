<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $bieren = Category::firstOrCreate(['slug' => 'bieren'], ['name' => 'Bieren']);
        $fris = Category::firstOrCreate(['slug' => 'fris'], ['name' => 'Frisdrank']);
        $monster = Category::firstOrCreate(['slug' => 'monster'], ['name' => 'Monster']);
        $eten = Category::firstOrCreate(['slug' => 'eten'], ['name' => 'Eten']);

        Product::firstOrCreate(['name' => 'Stella Artois'], ['category_id' => $bieren->id, 'price' => 300]);
        Product::firstOrCreate(['name' => 'Duvel'], ['category_id' => $bieren->id, 'price' => 450]);

        Product::firstOrCreate(['name' => 'Cola'], ['category_id' => $fris->id, 'price' => 250]);

        Product::firstOrCreate(['name' => 'Monster Energy'], ['category_id' => $monster->id, 'price' => 350]);

        Product::firstOrCreate(['name' => 'Hamburger'], ['category_id' => $eten->id, 'price' => 600]);
        Product::firstOrCreate(['name' => 'Frieten'], ['category_id' => $eten->id, 'price' => 350]);
    }
}

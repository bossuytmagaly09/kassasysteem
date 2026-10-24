<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('pos:sync-products')]
#[Description('Sync products and categories from the external database')]
class SyncProductsCommand extends Command
{
    public function handle()
    {
        $this->info('Starting product sync...');

        try {
            if (Schema::connection('external')->hasTable('categories')) {
                $externalCategories = DB::connection('external')->table('categories')->get();

                foreach ($externalCategories as $extCat) {
                    Category::updateOrCreate(
                        ['slug' => $extCat->slug ?? str()->slug($extCat->name)],
                        [
                            'name' => $extCat->name,
                            'is_active' => $extCat->is_active ?? true,
                        ]
                    );
                }
                $this->info('Categories synced.');
            } else {
                $this->warn('No categories table found on external DB, skipping categories.');
            }

            if (Schema::connection('external')->hasTable('products')) {
                $externalProducts = DB::connection('external')->table('products')->get();

                foreach ($externalProducts as $extProd) {
                    $category = Category::where('name', $extProd->category_name ?? '')->first();

                    Product::updateOrCreate(
                        ['external_id' => $extProd->id],
                        [
                            'category_id' => $category ? $category->id : 1,
                            'name' => $extProd->name,
                            'description' => $extProd->description ?? null,
                            'price' => $extProd->price,
                            'stock' => $extProd->stock ?? 0,
                            'is_active' => $extProd->is_active ?? true,
                        ]
                    );
                }
                $this->info('Products synced successfully!');
            } else {
                $this->warn('No products table found on external DB, skipping products.');
            }

        } catch (\Exception $e) {
            $this->error('Sync failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Signature('pos:sync-products')]
#[Description('Sync products and categories from the external database')]
class SyncProductsCommand extends Command
{
    public function handle()
    {
        $this->info('Starting product sync...');

        try {
            $categoriesMap = [];

            // 1. Synchroniseer vanuit de categories tabel op de externe database indien aanwezig
            if (Schema::connection('external')->hasTable('categories')) {
                $externalCategories = DB::connection('external')->table('categories')->get();

                foreach ($externalCategories as $extCat) {
                    $slug = $extCat->slug ?? Str::slug($extCat->name);
                    $localCat = Category::updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $extCat->name,
                            'is_active' => $extCat->is_active ?? true,
                        ]
                    );

                    if (isset($extCat->id)) {
                        $categoriesMap[(string) $extCat->id] = $localCat;
                    }
                    $categoriesMap[strtolower($extCat->name)] = $localCat;
                }
                $this->info('Categories synced from external categories table.');
            } else {
                $this->warn('No categories table found on external DB.');
            }

            // 2. Synchroniseer producten en ontdek categorieën
            if (Schema::connection('external')->hasTable('products')) {
                $hasCategoryCol = Schema::connection('external')->hasColumn('products', 'category');
                $hasCategoryNameCol = Schema::connection('external')->hasColumn('products', 'category_name');
                $hasCategoryIdCol = Schema::connection('external')->hasColumn('products', 'category_id');
                $hasParentIdCol = Schema::connection('external')->hasColumn('products', 'parent_id');

                // Indien parent_id aanwezig is (zoals drankdb hiërarchie: bv. Monster -> Monster ultra)
                $parentProductsMap = [];
                if ($hasParentIdCol) {
                    $parents = DB::connection('external')->table('products')->whereNull('parent_id')->get();
                    foreach ($parents as $parent) {
                        $hasChildren = DB::connection('external')->table('products')->where('parent_id', $parent->id)->exists();
                        if ($hasChildren) {
                            $parentCat = Category::firstOrCreate(
                                ['slug' => Str::slug($parent->name)],
                                ['name' => $parent->name, 'is_active' => true]
                            );
                            $parentProductsMap[$parent->id] = $parentCat;
                        }
                    }
                }

                $externalProducts = DB::connection('external')->table('products')->get();

                foreach ($externalProducts as $extProd) {
                    $category = null;

                    // A. Check category_id
                    if ($hasCategoryIdCol && ! empty($extProd->category_id) && isset($categoriesMap[(string) $extProd->category_id])) {
                        $category = $categoriesMap[(string) $extProd->category_id];
                    }

                    // B. Check category kolom (string)
                    if (! $category && $hasCategoryCol && ! empty($extProd->category)) {
                        $catName = trim($extProd->category);
                        $category = Category::firstOrCreate(
                            ['slug' => Str::slug($catName)],
                            ['name' => $catName, 'is_active' => true]
                        );
                    }

                    // C. Check category_name kolom (string)
                    if (! $category && $hasCategoryNameCol && ! empty($extProd->category_name)) {
                        $catName = trim($extProd->category_name);
                        $category = Category::firstOrCreate(
                            ['slug' => Str::slug($catName)],
                            ['name' => $catName, 'is_active' => true]
                        );
                    }

                    // D. Check parent_id mapping
                    if (! $category && $hasParentIdCol && ! empty($extProd->parent_id) && isset($parentProductsMap[$extProd->parent_id])) {
                        $category = $parentProductsMap[$extProd->parent_id];
                    }

                    // E. Check bestaand lokaal product
                    $existing = Product::where('external_id', $extProd->id)->first();
                    if (! $category && $existing && $existing->category_id) {
                        $category = Category::find($existing->category_id);
                    }

                    // F. Heuristische toewijzing o.b.v. productnaam
                    if (! $category) {
                        $category = $this->resolveCategoryByName($extProd->name);
                    }

                    // G. Fallback
                    if (! $category) {
                        $category = Category::firstOrCreate(
                            ['slug' => 'dranken'],
                            ['name' => 'Dranken', 'is_active' => true]
                        );
                    }

                    $priceCents = (int) round(((float) $extProd->price) * 100);

                    Product::updateOrCreate(
                        ['external_id' => $extProd->id],
                        [
                            'category_id' => $category->id,
                            'name' => $extProd->name,
                            'description' => $extProd->description ?? null,
                            'price' => $priceCents,
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

    private function resolveCategoryByName(string $name): ?Category
    {
        $lower = strtolower($name);
        $allCategories = Category::where('is_active', true)->get();

        foreach ($allCategories as $cat) {
            $catLower = strtolower($cat->name);
            if ($catLower === $lower || str_contains($lower, $catLower)) {
                return $cat;
            }
        }

        if (preg_match('/(bier|jupiler|stella|duvel|carlsberg|omer|vedett|kriek|leffe|tripel|blonde|blond|pils)/i', $lower)) {
            return $allCategories->firstWhere('slug', 'bieren')
                ?? Category::firstOrCreate(['slug' => 'bieren'], ['name' => 'Bieren', 'is_active' => true]);
        }

        if (preg_match('/(fris|cola|fanta|sprite|ice[\s-]?tea|fuze|water|chaudfontaine|tonic|spa|red[\s-]?bull|hell|rodeo)/i', $lower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['fris', 'frisdrank', 'frisdranken']))
                ?? Category::firstOrCreate(['slug' => 'frisdrank'], ['name' => 'Frisdrank', 'is_active' => true]);
        }

        if (preg_match('/(monster)/i', $lower)) {
            return $allCategories->firstWhere('slug', 'monster')
                ?? Category::firstOrCreate(['slug' => 'monster'], ['name' => 'Monster', 'is_active' => true]);
        }

        if (preg_match('/(eten|chips|pizza|snack|nootjes|pasta|croque|burger|hotdog|noodles)/i', $lower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['eten', 'snacks']))
                ?? Category::firstOrCreate(['slug' => 'eten'], ['name' => 'Eten', 'is_active' => true]);
        }

        if (preg_match('/(cocktail|gin|rum|vodka|wodka|whisky|whiskey|shot|aperitief|wijn)/i', $lower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['cocktails', 'sterke-dranken', 'wijn-en-bubbels']))
                ?? Category::firstOrCreate(['slug' => 'cocktails'], ['name' => 'Cocktails', 'is_active' => true]);
        }

        return null;
    }
}

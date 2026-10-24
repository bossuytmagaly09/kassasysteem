<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EventManager;
use App\Models\Category;
use App\Models\Event;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders(): void
    {
        $this->get(route('admin.events'))->assertOk()->assertSee('Nieuw Event');
    }

    public function test_an_event_can_be_created_with_payment_mode_and_vak_price(): void
    {
        Livewire::test(EventManager::class)
            ->call('createEvent')
            ->set('name', 'Fuif')
            ->set('paymentMode', 'drankkaart')
            ->set('vakPrice', '1.50')
            ->call('saveEvent')
            ->assertHasNoErrors()
            ->assertSet('showEventModal', false);

        $this->assertDatabaseHas('events', ['name' => 'Fuif', 'payment_mode' => 'drankkaart', 'vak_price' => 150]);
    }

    public function test_a_bottle_can_be_added_and_sold_per_glass_with_automatic_stock_calculation(): void
    {
        $category = Category::create(['name' => 'Frisdrank', 'slug' => 'frisdrank']);
        $event = Event::factory()->create();

        // Organiser bought 10 bottles of Cola at € 1.50 each, sells as glass (5 glasses per bottle) at € 2.50 per glass
        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Cola')
            ->set('categoryId', $category->id)
            ->set('purchaseUnit', 'fles')
            ->set('purchaseQuantity', '10')
            ->set('purchasePrice', '1.50')
            ->set('bottleSellMode', 'glas')
            ->set('glassesPerBottle', '5')
            ->set('glassPrice', '2.50')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        // Check that product Cola was created with price 250 and 50 glasses stock
        $this->assertDatabaseHas('products', [
            'name' => 'Cola',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'price' => 250,
            'stock_planned' => 50,
            'purchase_unit' => 'fles',
            'purchase_quantity' => 10,
            'purchase_price' => 150,
            'servings_per_unit' => 5,
            'serving_unit' => 'glas',
        ]);
    }

    public function test_custom_liter_and_serving_volume_can_be_set_and_calculates_glasses(): void
    {
        $category = Category::create(['name' => 'Frisdrank', 'slug' => 'frisdrank']);
        $event = Event::factory()->create();

        // 10 bottles of 1.5L, served in 25cl glasses -> 150 / 25 = 6 glasses per bottle -> 60 glasses stock
        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Ice Tea')
            ->set('categoryId', $category->id)
            ->set('purchaseUnit', 'fles')
            ->call('setVolumePreset', '1.5', 'L')
            ->call('setServingPreset', '25')
            ->assertSet('packageVolume', '1.5')
            ->assertSet('packageVolumeUnit', 'L')
            ->assertSet('servingVolume', '25')
            ->assertSet('glassesPerBottle', '6')
            ->set('purchaseQuantity', '10')
            ->set('purchasePrice', '2.00')
            ->set('glassPrice', '2.80')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'stock_planned' => 60,
            'package_volume' => '1.5 L',
            'servings_per_unit' => 6,
            'serving_volume' => '25 cl',
        ]);
    }

    public function test_a_bottle_can_be_added_and_sold_as_whole_bottle(): void
    {
        $category = Category::create(['name' => 'Wijnen', 'slug' => 'wijnen']);
        $event = Event::factory()->create();

        // Organiser bought 12 bottles of Cava, sells as whole bottle at € 18.00 per bottle
        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Cava')
            ->set('categoryId', $category->id)
            ->set('purchaseUnit', 'fles')
            ->set('purchaseQuantity', '12')
            ->set('bottleSellMode', 'fles')
            ->set('bottlePrice', '18.00')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Cava',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'price' => 1800,
            'stock_planned' => 12,
            'purchase_unit' => 'fles',
            'purchase_quantity' => 12,
            'servings_per_unit' => 1,
            'serving_unit' => 'fles',
        ]);
    }

    public function test_a_can_can_be_added(): void
    {
        $category = Category::create(['name' => 'Frisdrank', 'slug' => 'frisdrank']);
        $event = Event::factory()->create();

        // Organiser bought 48 cans of Fanta, sells at € 2.20 per can
        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Fanta')
            ->set('categoryId', $category->id)
            ->set('purchaseUnit', 'blik')
            ->set('purchaseQuantity', '48')
            ->set('canPrice', '2.20')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Fanta',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'price' => 220,
            'stock_planned' => 48,
            'purchase_unit' => 'blik',
            'purchase_quantity' => 48,
        ]);
    }

    public function test_a_keg_can_be_added_and_sold_per_glass(): void
    {
        $category = Category::create(['name' => 'Bieren', 'slug' => 'bieren']);
        $event = Event::factory()->create();

        // 2 kegs of 50L (150 glasses per keg) at € 2.00
        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Stella')
            ->set('categoryId', $category->id)
            ->set('purchaseUnit', 'vat')
            ->set('purchaseQuantity', '2')
            ->set('glassesPerKeg', '150')
            ->set('kegGlassPrice', '2.00')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Stella',
        ]);

        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'price' => 200,
            'stock_planned' => 300,
            'purchase_unit' => 'vat',
            'purchase_quantity' => 2,
        ]);
    }

    public function test_a_custom_item_with_a_new_category_can_be_created(): void
    {
        $event = Event::factory()->create();

        Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('productName', 'Mojito Cocktail')
            ->call('toggleNewCategory')
            ->assertSet('showNewCategoryInput', true)
            ->set('newCategoryName', 'Cocktails')
            ->set('purchaseUnit', 'stuk')
            ->set('purchaseQuantity', '50')
            ->set('itemPrice', '8.50')
            ->call('addNewProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Cocktails']);
        $this->assertDatabaseHas('products', ['name' => 'Mojito Cocktail']);
        $this->assertDatabaseHas('event_product', [
            'event_id' => $event->id,
            'price' => 850,
            'stock_planned' => 50,
        ]);
    }

    public function test_existing_catalog_product_can_be_added_updated_and_removed(): void
    {
        $category = Category::create(['name' => 'Bier', 'slug' => 'bier']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Pils', 'price' => 200]);
        $event = Event::factory()->create();

        $component = Livewire::test(EventManager::class)
            ->call('selectEvent', $event->id)
            ->set('addMode', 'existing')
            ->set('newProductId', $product->id)
            ->assertSet('newPrice', '2.00')
            ->set('newPrice', '2.50')
            ->set('newStock', '48')
            ->call('addExistingProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_product', ['event_id' => $event->id, 'product_id' => $product->id, 'price' => 250, 'stock_planned' => 48]);

        $component->set("productPrices.{$product->id}", '3.00')
            ->set("productStocks.{$product->id}", '')
            ->call('updateProduct', $product->id);

        $this->assertDatabaseHas('event_product', ['product_id' => $product->id, 'price' => 300, 'stock_planned' => null]);

        $component->call('removeProduct', $product->id);

        $this->assertDatabaseCount('event_product', 0);
    }

    public function test_products_can_be_copied_from_another_event(): void
    {
        $category = Category::create(['name' => 'Bier', 'slug' => 'bier']);
        $p1 = Product::create(['category_id' => $category->id, 'name' => 'Stella', 'price' => 250]);
        $p2 = Product::create(['category_id' => $category->id, 'name' => 'Jupiler', 'price' => 250]);

        $event1 = Event::factory()->create();
        $event1->products()->attach($p1->id, ['price' => 260, 'stock_planned' => 100]);
        $event1->products()->attach($p2->id, ['price' => 260, 'stock_planned' => 100]);

        $event2 = Event::factory()->create();

        Livewire::test(EventManager::class)
            ->call('selectEvent', $event2->id)
            ->set('copyFromEventId', $event1->id)
            ->call('copyFromEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('event_product', 4);
        $this->assertDatabaseHas('event_product', ['event_id' => $event2->id, 'product_id' => $p1->id, 'price' => 260]);
    }

    public function test_only_one_event_can_be_active(): void
    {
        $first = Event::factory()->active()->create();
        $second = Event::factory()->create();

        Livewire::test(EventManager::class)->call('activateEvent', $second->id);

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }
}

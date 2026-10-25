<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SalesLog;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_log_page_renders_successfully(): void
    {
        $this->get(route('admin.sales'))
            ->assertOk()
            ->assertSee('Verkoop- & Bestellingenoverzicht', false);
    }

    public function test_sales_log_displays_kpi_and_product_sales_breakdown(): void
    {
        $event = Event::factory()->create(['name' => 'Kermis 2026', 'is_active' => true]);
        $category = Category::create(['name' => 'Bieren', 'slug' => 'bieren']);
        $stella = Product::create(['category_id' => $category->id, 'name' => 'Stella Artois', 'price' => 150]);

        // Order 1: 2x Stella (Cash)
        $order1 = Order::create([
            'event_id' => $event->id,
            'total_amount' => 300,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $stella->id,
            'product_name' => 'Stella Artois',
            'product_price' => 150,
            'quantity' => 2,
        ]);

        // Order 2: 1x Drankkaart (QR)
        $order2 = Order::create([
            'event_id' => $event->id,
            'total_amount' => 2000,
            'status' => 'paid',
            'payment_method' => 'qr',
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => null,
            'product_name' => 'Drankkaart',
            'product_price' => 2000,
            'quantity' => 1,
        ]);

        Livewire::test(SalesLog::class, ['eventId' => $event->id])
            ->assertSee('Kermis 2026')
            ->assertSee('€ 23,00', false) // Total revenue
            ->assertSee('1 stuks') // Drankkaarten sold
            ->assertSee('Stella Artois')
            ->assertSee('2x')
            ->assertSee('Drankkaart');
    }

    public function test_sales_log_orders_tab_lists_chronological_orders(): void
    {
        $event = Event::factory()->create(['name' => 'Fuif 2026']);

        $order = Order::create([
            'event_id' => $event->id,
            'total_amount' => 500,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Cola',
            'product_price' => 250,
            'quantity' => 2,
        ]);

        Livewire::test(SalesLog::class, ['eventId' => $event->id])
            ->call('setTab', 'orders')
            ->assertSee('Fuif 2026')
            ->assertSee('Contant (Cash)')
            ->assertSee('2x Cola')
            ->assertSee('€ 5,00', false);
    }

    public function test_sales_log_can_filter_by_payment_method(): void
    {
        $event = Event::factory()->create();

        Order::create([
            'event_id' => $event->id,
            'total_amount' => 1000,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);

        Order::create([
            'event_id' => $event->id,
            'total_amount' => 2000,
            'status' => 'paid',
            'payment_method' => 'qr',
        ]);

        Livewire::test(SalesLog::class, ['eventId' => $event->id])
            ->set('paymentMethodFilter', 'qr')
            ->assertSee('€ 20,00', false)
            ->assertDontSee('€ 30,00', false);
    }
}

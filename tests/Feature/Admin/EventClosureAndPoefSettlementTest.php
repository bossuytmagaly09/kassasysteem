<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EventManager;
use App\Livewire\Admin\SalesLog;
use App\Livewire\Pos\Terminal;
use App\Models\Card;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use App\Services\EventSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventClosureAndPoefSettlementTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Product $cola;

    private Poef $poef;

    private Card $card;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Dranken', 'slug' => 'dranken']);

        $this->cola = Product::create([
            'category_id' => $category->id,
            'name' => 'Cola',
            'price' => 250,
            'punch_cost' => 1,
        ]);

        $this->event = Event::factory()->active()->create([
            'name' => 'Zomerfuif 2026',
            'vak_price' => 100,
            'card_price' => 2000,
        ]);
        $this->event->products()->attach($this->cola->id, ['price' => 250]);

        $this->poef = Poef::create([
            'name' => 'Bjarne',
            'poef_name' => 'Bjarne / Eowyn',
            'hangaar_user_id' => 101,
            'external_id' => 101,
            'has_poef' => true,
        ]);

        $this->card = Card::create([
            'poef_id' => $this->poef->id,
            'uid' => 'CARD-BJARNE',
            'hangaar_user_id' => 101,
            'card_type' => 'badge',
            'status' => 'active',
        ]);
    }

    public function test_poef_orders_remain_unsettled_during_live_event(): void
    {
        // Cashier processes an order with a badge scan
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'CARD-BJARNE')
            ->assertSet('paymentSuccess', true);

        $order = Order::where('event_id', $this->event->id)->firstOrFail();
        $this->assertEquals(250, $order->total_amount);
        $this->assertEquals($this->poef->id, $order->poef_id);
        // During event, order must NOT be settled to Beheer yet
        $this->assertNull($order->settled_at);
        $this->assertEquals('pending', $order->beheer_status);
    }

    public function test_closing_an_event_settles_all_poef_orders_for_that_event(): void
    {
        // 1. Create two poef orders during the event
        Order::create([
            'event_id' => $this->event->id,
            'poef_id' => $this->poef->id,
            'payment_method' => 'scanner',
            'total_amount' => 500,
            'settled_at' => null,
            'uuid' => (string) Str::uuid(),
            'status' => 'paid',
        ]);

        Order::create([
            'event_id' => $this->event->id,
            'poef_id' => $this->poef->id,
            'payment_method' => 'scanner',
            'total_amount' => 300,
            'settled_at' => null,
            'uuid' => (string) Str::uuid(),
            'status' => 'paid',
        ]);

        // 2. Close the event via EventSettlementService / EventManager
        Livewire::test(EventManager::class)
            ->call('closeEvent', $this->event->id);

        $this->event->refresh();
        $this->assertTrue($this->event->is_closed);
        $this->assertFalse($this->event->is_active);
        $this->assertNotNull($this->event->closed_at);
        $this->assertTrue($this->event->poef_settled);

        // All orders for this event must now be settled
        $unsettledCount = Order::where('event_id', $this->event->id)
            ->whereNotNull('poef_id')
            ->whereNull('settled_at')
            ->count();
        $this->assertEquals(0, $unsettledCount);
    }

    public function test_closed_event_can_be_reopened(): void
    {
        $this->event->update(['is_closed' => true, 'closed_at' => now(), 'is_active' => false]);

        Livewire::test(EventManager::class)
            ->call('reopenEvent', $this->event->id);

        $this->event->refresh();
        $this->assertFalse($this->event->is_closed);
        $this->assertTrue($this->event->is_active);
        $this->assertNull($this->event->closed_at);
    }

    public function test_verkoop_log_page_and_push_to_beheer(): void
    {
        Order::create([
            'event_id' => $this->event->id,
            'cashier_name' => 'Wim Kassier',
            'device' => 'kassa-1',
            'total_amount' => 250,
            'payment_method' => 'cash',
            'beheer_status' => 'pending',
            'uuid' => (string) Str::uuid(),
            'status' => 'paid',
        ]);

        // Access dedicated sales log route
        $this->get(route('admin.sales-log'))
            ->assertOk()
            ->assertSee('Gedetailleerde Bestellingen Log')
            ->assertSee('Wim Kassier')
            ->assertSee('Kassa 1')
            ->assertSee('Pushen naar Beheerssite');

        // Test pushing to beheer via Livewire
        Livewire::test(SalesLog::class, ['eventId' => $this->event->id])
            ->call('pushPendingToBeheer')
            ->assertSet('isSyncing', false);
    }

    public function test_sales_logs_remain_intact_and_viewable_after_event_is_closed(): void
    {
        $order = Order::create([
            'event_id' => $this->event->id,
            'cashier_name' => 'Sarah',
            'device' => 'kassa-2',
            'total_amount' => 750,
            'payment_method' => 'qr',
            'uuid' => (string) Str::uuid(),
            'status' => 'paid',
        ]);

        // Close the event
        app(EventSettlementService::class)->closeAndSettle($this->event);
        $this->event->refresh();
        $this->assertTrue($this->event->is_closed);

        // Order remains in database with all details
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'event_id' => $this->event->id,
            'cashier_name' => 'Sarah',
            'device' => 'kassa-2',
            'total_amount' => 750,
        ]);

        // Viewing the closed event in SalesLog still displays the order and closed badge
        Livewire::test(SalesLog::class, ['eventId' => $this->event->id, 'tab' => 'orders'])
            ->assertSee('Sarah')
            ->assertSee('Kassa 2')
            ->assertSee('Afgesloten');
    }
}

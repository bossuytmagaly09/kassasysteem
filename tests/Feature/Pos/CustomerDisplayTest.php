<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\CustomerDisplay;
use App\Livewire\Pos\Terminal;
use App\Models\Card;
use App\Models\Category;
use App\Models\Event;
use App\Models\Poef;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDisplayTest extends TestCase
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
            'name' => 'Zomerfeest 2026',
            'vak_price' => 100,
            'card_price' => 2000,
        ]);
        $this->event->products()->attach($this->cola->id, ['price' => 250]);

        $this->poef = Poef::create([
            'external_id' => 7,
            'hangaar_user_id' => 7,
            'name' => 'Jonathan',
            'poef_name' => 'Jonathan / Febe',
            'has_poef' => true,
        ]);

        $this->card = Card::create([
            'poef_id' => $this->poef->id,
            'uid' => '04A23B19',
            'status' => 'active',
        ]);
    }

    public function test_customer_display_routes_are_accessible(): void
    {
        $this->get(route('pos.display'))
            ->assertOk()
            ->assertSee('Zomerfeest 2026')
            ->assertSee('Welkom bij Zomerfeest 2026');

        $this->get('/display')
            ->assertOk()
            ->assertSee('Zomerfeest 2026');
    }

    public function test_customer_display_shows_idle_state_initially(): void
    {
        Cache::forget('pos_customer_display_state');

        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'idle')
            ->assertSee('Welkom bij Zomerfeest 2026')
            ->assertSee('Bestel gerust aan de bar');
    }

    public function test_terminal_broadcasts_cart_items_and_totals_to_customer_display(): void
    {
        // 1. Terminal adds items to order
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id);

        // 2. Customer display should pick up the live cart
        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'cart')
            ->assertSet('total', 500)
            ->assertSet('vakjes', 5)
            ->assertSee('Cola')
            ->assertSee('2x')
            ->assertSee('€ 5,00')
            ->assertSee('5 vakjes');
    }

    public function test_customer_display_shows_poef_total_spent_after_payment(): void
    {
        // Place a prior order of € 10,00 for this poef
        $this->event->orders()->create([
            'poef_id' => $this->poef->id,
            'card_id' => $this->card->id,
            'total_amount' => 1000,
            'status' => 'paid',
            'payment_method' => 'scanner',
        ]);

        // Now place a new order on the Terminal for € 5,00
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', '04A23B19')
            ->assertSet('paymentSuccess', true)
            ->assertSet('scannedPoefName', 'Jonathan')
            ->assertSet('lastPoefTotalSpent', 1500); // 1000 + 500 = 1500 cents (€ 15.00)

        // Customer display should display the success state and current total spent tonight
        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'success')
            ->assertSet('scannedPoefName', 'Jonathan')
            ->assertSet('poefTotalSpent', 1500)
            ->assertSee('Bedankt voor uw bestelling!')
            ->assertSee('Jonathan')
            ->assertSee('Jonathan / Febe')
            ->assertSee('Huidig saldo / Totaal uitgegeven vanavond:')
            ->assertSee('€ 15,00');
    }

    public function test_customer_display_idle_state_shows_consumptiekaart_price_box(): void
    {
        Cache::forget('pos_customer_display_state');

        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'idle')
            ->assertSee('Consumptiekaart')
            ->assertSee('€ 20,00')
            ->assertSee('20 vakjes');
    }

    public function test_customer_display_does_not_show_kassa_number(): void
    {
        Livewire::test(CustomerDisplay::class)
            ->assertDontSee('Kassa 1')
            ->assertDontSee('Kassa 2');
    }

    public function test_consumptiekaart_only_order_does_not_show_or_calculate_vakjes_on_customer_display(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart');

        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'cart')
            ->assertSet('total', 2000)
            ->assertSet('vakjes', 0)
            ->assertSee('Consumptiekaart')
            ->assertSee('€ 20,00')
            ->assertDontSee('Of consumptiekaart:')
            ->assertDontSee('Vakjes voor consumpties:');
    }

    public function test_consumptiekaart_with_drinks_order_only_calculates_vakjes_for_drinks(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('addToOrder', $this->cola->id);

        Livewire::test(CustomerDisplay::class)
            ->assertSet('state', 'cart')
            ->assertSet('total', 2250)
            ->assertSet('vakjes', 3)
            ->assertSee('Vakjes voor consumpties:')
            ->assertSee('3 vakjes');
    }
}

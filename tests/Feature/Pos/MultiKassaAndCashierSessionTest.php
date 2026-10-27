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

class MultiKassaAndCashierSessionTest extends TestCase
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
            'name' => 'Kassa Test Event',
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
            'uid' => 'BADGE-BJARNE',
            'hangaar_user_id' => 101,
            'card_type' => 'badge',
            'status' => 'active',
        ]);
    }

    public function test_kassa_terminal_can_be_unlocked_with_pin(): void
    {
        config(['kassa.enforce_pos_lock_in_tests' => true]);
        config(['kassa.pos_pin' => '1234']);

        $component = Livewire::test(Terminal::class, ['kassa' => '2'])
            ->assertSet('isLocked', true)
            ->assertSet('kassaId', '2')
            ->set('customCashierName', 'Bjarne Kassier')
            ->call('appendLockPin', '1')
            ->call('appendLockPin', '2')
            ->call('appendLockPin', '3')
            ->call('appendLockPin', '4')
            ->assertSet('isLocked', false)
            ->assertSet('cashierName', 'Bjarne Kassier');

        $this->assertDatabaseHas('cashier_sessions', [
            'kassa_id' => '2',
            'cashier_name' => 'Bjarne Kassier',
            'auth_method' => 'pin',
            'logged_out_at' => null,
        ]);
    }

    public function test_kassa_terminal_can_be_unlocked_with_badge_scan(): void
    {
        config(['kassa.enforce_pos_lock_in_tests' => true]);

        Livewire::test(Terminal::class, ['kassa' => '3'])
            ->assertSet('isLocked', true)
            ->assertSet('kassaId', '3')
            ->call('handleScan', 'BADGE-BJARNE')
            ->assertSet('isLocked', false)
            ->assertSet('cashierName', 'Bjarne');

        $this->assertDatabaseHas('cashier_sessions', [
            'kassa_id' => '3',
            'cashier_name' => 'Bjarne',
            'auth_method' => 'badge',
            'logged_out_at' => null,
        ]);
    }

    public function test_order_records_device_and_cashier_name(): void
    {
        Livewire::test(Terminal::class, ['kassa' => '2'])
            ->set('cashierName', 'Vrijwilliger Lisa')
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'BADGE-BJARNE')
            ->assertSet('paymentSuccess', true);

        $this->assertDatabaseHas('orders', [
            'device' => 'kassa-2',
            'cashier_name' => 'Vrijwilliger Lisa',
            'total_amount' => 250,
            'payment_method' => 'scanner',
        ]);
    }

    public function test_badge_scan_resolves_specific_user_name_over_shared_poef_name(): void
    {
        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'BADGE-BJARNE')
            ->assertSet('paymentSuccess', true)
            ->assertSet('scannedPoefName', 'Bjarne')
            ->assertSet('scannedSharedPoefName', 'Bjarne / Eowyn');
    }

    public function test_customer_display_is_isolated_per_kassa_id(): void
    {
        Cache::put('pos_customer_display_state_1', ['state' => 'idle', 'cart' => []]);
        Cache::put('pos_customer_display_state_2', ['state' => 'cart', 'total' => 500, 'items' => []]);

        Livewire::test(CustomerDisplay::class, ['kassa' => '1'])
            ->assertSet('state', 'idle');

        Livewire::test(CustomerDisplay::class, ['kassa' => '2'])
            ->assertSet('state', 'cart')
            ->assertSet('total', 500);
    }

    public function test_admin_pin_middleware_protects_admin_routes(): void
    {
        config(['kassa.enforce_pin_in_tests' => true]);

        // Unauthenticated access redirects to /admin/pin
        $this->get(route('admin.sales'))->assertRedirect(route('admin.pin'));
        $this->get('/scanner')->assertRedirect(route('admin.pin'));

        // Customer display is NOT protected (per user requirement)
        $this->get('/klantenscherm')->assertOk();
        $this->get('/display/2')->assertOk();
    }
}

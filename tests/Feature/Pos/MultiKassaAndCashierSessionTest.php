<?php

namespace Tests\Feature\Pos;

use App\Livewire\Admin\PinLogin;
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
        config(['kassa.pos_pin' => '1989']);

        $component = Livewire::test(Terminal::class, ['kassa' => '2'])
            ->assertSet('isLocked', true)
            ->assertSet('kassaId', '2')
            ->set('customCashierName', 'Bjarne Kassier')
            ->call('appendLockPin', '1')
            ->call('appendLockPin', '9')
            ->call('appendLockPin', '8')
            ->call('appendLockPin', '9')
            ->assertSet('isLocked', false)
            ->assertSet('cashierName', 'Bjarne Kassier');

        $this->assertDatabaseHas('cashier_sessions', [
            'kassa_id' => '2',
            'cashier_name' => 'Bjarne Kassier',
            'auth_method' => 'pin',
            'logged_out_at' => null,
        ]);
    }

    public function test_kassa_terminal_quick_unlock_with_1989(): void
    {
        config(['kassa.enforce_pos_lock_in_tests' => true]);

        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->assertSet('isLocked', true)
            ->call('quickUnlock')
            ->assertSet('isLocked', false);
    }

    public function test_kassa_terminal_can_be_unlocked_with_badge_or_input(): void
    {
        config(['kassa.enforce_pos_lock_in_tests' => true]);

        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->assertSet('isLocked', true)
            ->call('unlockWithBadgeOrInput', '1989')
            ->assertSet('isLocked', false);
    }

    public function test_payment_resolves_card_by_label(): void
    {
        $this->card->update(['label' => 'Hangaar Pas Bjarne']);

        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'Hangaar Pas Bjarne')
            ->assertSet('paymentSuccess', true);
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

    public function test_admin_pin_protects_admin_routes_and_unlocks_with_1989(): void
    {
        config(['kassa.enforce_pin_in_tests' => true]);

        // Unauthenticated access to admin routes redirects to /admin/pin
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.pin'));
        $this->get(route('admin.sales'))->assertRedirect(route('admin.pin'));

        // PinLogin unlocks with 1989 and redirects to intended URL (admin.sales)
        Livewire::test(PinLogin::class)
            ->call('appendDigit', '1')
            ->call('appendDigit', '9')
            ->call('appendDigit', '8')
            ->call('appendDigit', '9')
            ->assertRedirect(route('admin.sales'));

        $this->assertTrue(session('admin_pin_authenticated'));

        // With session, admin routes are accessible
        $this->withSession(['admin_pin_authenticated' => true])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_visiting_dashboard_or_exit_locks_beheer_and_kassa(): void
    {
        // Set up active sessions
        session([
            'admin_pin_authenticated' => true,
            'pos_cashier_1' => 'Kassier 1',
            'pos_cashier_session_id_1' => 999,
        ]);

        // Visiting dashboard locks beheer and cashier sessions
        $this->get(route('dashboard'))->assertOk();

        $this->assertNull(session('admin_pin_authenticated'));
        $this->assertNull(session('pos_cashier_1'));
        $this->assertNull(session('pos_cashier_session_id_1'));
    }

    public function test_scanner_and_customer_display_routes_are_always_directly_accessible(): void
    {
        config(['kassa.enforce_pin_in_tests' => true]);

        // Scanner (card testing tool) and customer display are never blocked
        $this->get('/scanner')->assertOk();
        $this->get('/klantenscherm')->assertOk();
        $this->get('/display/2')->assertOk();
    }

    public function test_leaving_kassa_to_dashboard_or_exit_clears_open_order_and_resets_customer_display(): void
    {
        // 1. Kassier voegt artikelen toe aan bestelling op Kassa 1
        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->call('addToOrder', $this->cola->id);

        $cachedBefore = Cache::get('pos_customer_display_state_1');
        $this->assertNotEmpty($cachedBefore['items'] ?? []);
        $this->assertEquals(250, $cachedBefore['total'] ?? 0);

        // 2. Kassier verlaat kassa scherm naar pos.exit
        $this->get(route('pos.exit', ['kassa' => '1']))->assertRedirect(route('dashboard'));

        $cachedAfterExit = Cache::get('pos_customer_display_state_1');
        $this->assertEquals('idle', $cachedAfterExit['state'] ?? '');
        $this->assertEmpty($cachedAfterExit['items'] ?? []);
        $this->assertEquals(0, $cachedAfterExit['total'] ?? 0);

        // Klantenscherm staat op idle zonder open bestellingen
        Livewire::test(CustomerDisplay::class, ['kassa' => '1'])
            ->assertSet('state', 'idle')
            ->assertSet('total', 0)
            ->assertSet('items', []);
    }

    public function test_switching_kassa_clears_open_order_on_previous_kassa(): void
    {
        // 1. Kassier heeft open bestelling op Kassa 1
        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->call('addToOrder', $this->cola->id)
            ->call('switchKassa', '2')
            ->assertRedirect(route('pos.index', ['kassa' => '2']));

        // Kassa 1 klantenscherm moet direct leeggemaakt zijn
        $cachedKassa1 = Cache::get('pos_customer_display_state_1');
        $this->assertEquals('idle', $cachedKassa1['state'] ?? '');
        $this->assertEmpty($cachedKassa1['items'] ?? []);
        $this->assertEquals(0, $cachedKassa1['total'] ?? 0);

        // En via route pos.switch
        Livewire::test(Terminal::class, ['kassa' => '2'])
            ->call('addToOrder', $this->cola->id);

        $this->get(route('pos.switch', ['toKassa' => '1', 'from' => '2']))
            ->assertRedirect(route('pos.index', ['kassa' => '1']));

        $cachedKassa2 = Cache::get('pos_customer_display_state_2');
        $this->assertEquals('idle', $cachedKassa2['state'] ?? '');
        $this->assertEmpty($cachedKassa2['items'] ?? []);
    }

    public function test_beacon_endpoint_clears_active_order_when_leaving_page(): void
    {
        Livewire::test(Terminal::class, ['kassa' => '1'])
            ->call('addToOrder', $this->cola->id);

        $this->post(route('pos.clear-active-order', ['kassa' => '1']))
            ->assertNoContent();

        $cached = Cache::get('pos_customer_display_state_1');
        $this->assertEquals('idle', $cached['state'] ?? '');
        $this->assertEmpty($cached['items'] ?? []);
    }
}

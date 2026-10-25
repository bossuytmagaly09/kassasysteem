<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\Terminal;
use App\Models\Card;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use App\PaymentMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TerminalTest extends TestCase
{
    use RefreshDatabase;

    private Product $cola;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Frisdrank', 'slug' => 'frisdrank']);

        $this->cola = Product::create([
            'category_id' => $category->id,
            'name' => 'Cola',
            'price' => 200,
        ]);

        $this->event = Event::factory()->active()->create(['vak_price' => 100]);
        $this->event->products()->attach($this->cola->id, ['price' => 250, 'stock_planned' => null]);
    }

    public function test_the_pos_page_renders_with_livewire_scripts(): void
    {
        $this->get(route('pos.index'))
            ->assertOk()
            ->assertSee($this->event->name)
            ->assertSee('Cola')
            ->assertSee('livewire', false);
    }

    public function test_without_active_event_a_message_is_shown(): void
    {
        $this->event->update(['is_active' => false]);

        Livewire::test(Terminal::class)
            ->assertSee('Geen actief event')
            ->assertDontSee('Cola');
    }

    public function test_only_products_of_the_active_event_are_shown_at_event_price(): void
    {
        Product::create(['category_id' => $this->cola->category_id, 'name' => 'Fanta', 'price' => 200]);

        Livewire::test(Terminal::class)
            ->assertSee('Cola')
            ->assertSee('€ 2,50')
            ->assertDontSee('Fanta');
    }

    public function test_products_can_be_added_and_removed(): void
    {
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id)
            ->assertSet('orderItems.0.quantity', 2)
            ->assertSee('€ 5,00')
            ->call('removeOne', 0)
            ->assertSet('orderItems.0.quantity', 1)
            ->call('removeOne', 0)
            ->assertSet('orderItems', []);
    }

    public function test_scanner_opens_scan_modal_and_annuleren_closes_it(): void
    {
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->assertSet('awaitingPayment', true)
            ->assertSee('Wachten op kaart')
            ->call('cancelPayment')
            ->assertSet('awaitingPayment', false)
            ->assertDontSee('Wachten op kaart')
            ->assertSet('orderItems.0.quantity', 1);
    }

    public function test_scanner_does_nothing_with_an_empty_cart(): void
    {
        Livewire::test(Terminal::class)
            ->call('startPayment')
            ->assertSet('awaitingPayment', false);
    }

    public function test_scanning_any_card_pays_the_order_without_poef_link(): void
    {
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'CARD-123')
            ->assertSet('awaitingPayment', false)
            ->assertSet('paymentSuccess', true)
            ->assertSet('orderItems', [])
            ->assertSee('Betaald!')
            ->call('closeSuccess')
            ->assertSet('paymentSuccess', false);

        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'card_uid' => 'CARD-123',
            'payment_method' => 'scanner',
            'total_amount' => 500,
        ]);
        $this->assertDatabaseHas('order_items', ['product_name' => 'Cola', 'quantity' => 2, 'product_price' => 250]);
    }

    public function test_drankkaart_uses_vak_price_from_event(): void
    {
        $this->event->update(['vak_price' => 50]);

        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('openDrankkaart')
            ->assertSet('showDrankkaartModal', true)
            ->assertSee('Consumptiekaart Afrekenen')
            ->assertViewHas('vakjes', 5)
            ->call('closeDrankkaart')
            ->assertSet('showDrankkaartModal', false)
            ->call('openDrankkaart')
            ->call('completeDrankkaart')
            ->assertSet('showDrankkaartModal', false)
            ->assertSet('orderItems', []);

        $this->assertDatabaseHas('orders', ['payment_method' => 'drankkaart', 'vakjes' => 5, 'total_amount' => 250]);
    }

    public function test_payment_buttons_follow_event_payment_mode(): void
    {
        $this->event->update(['payment_mode' => PaymentMode::Drankkaart]);

        Livewire::test(Terminal::class)
            ->assertSee('btn-drankkaart', false)
            ->assertDontSee('btn-afrekenen', false)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->assertSet('awaitingPayment', false);

        $this->event->update(['payment_mode' => PaymentMode::Scanner]);

        Livewire::test(Terminal::class)
            ->assertDontSee('btn-drankkaart', false)
            ->assertSee('btn-afrekenen', false)
            ->call('addToOrder', $this->cola->id)
            ->call('openDrankkaart')
            ->assertSet('showDrankkaartModal', false);
    }

    public function test_stock_limits_sales_and_marks_product_sold_out(): void
    {
        $this->event->products()->updateExistingPivot($this->cola->id, ['stock_planned' => 2]);

        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id)
            ->call('addToOrder', $this->cola->id)
            ->assertSet('orderItems.0.quantity', 2)
            ->call('startPayment')
            ->call('handleScan', 'CARD-1')
            ->assertSee('UITVERKOCHT')
            ->call('closeSuccess')
            ->call('addToOrder', $this->cola->id)
            ->assertSet('orderItems', []);
    }

    public function test_cart_cannot_change_while_a_modal_is_open(): void
    {
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('addToOrder', $this->cola->id)
            ->assertSet('orderItems.0.quantity', 1);
    }

    public function test_drankkaart_can_be_purchased_at_event_card_price(): void
    {
        $this->event->update(['card_price' => 2500]); // € 25.00

        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->assertSet('orderItems.0.name', 'Consumptiekaart')
            ->assertSet('orderItems.0.price', 2500)
            ->assertSet('orderItems.0.quantity', 1)
            ->assertSee('€ 25,00')
            ->call('addDrankkaart')
            ->assertSet('orderItems.0.quantity', 2)
            ->assertSee('€ 50,00');
    }

    public function test_drankkaart_can_be_purchased_together_with_drinks(): void
    {
        $this->event->update(['card_price' => 2000, 'vak_price' => 100]);

        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('addToOrder', $this->cola->id)
            ->assertCount('orderItems', 2)
            ->assertSee('€ 22,50')
            ->call('openDrankkaart')
            ->assertSet('showDrankkaartModal', true)
            ->assertSee('Te betalen voor consumptiekaart:')
            ->assertSee('€ 20,00')
            ->assertSee('3')
            ->assertSee('vakjes');
    }

    public function test_drankkaart_with_drinks_can_be_completed_with_cash_and_records_vakjes(): void
    {
        $this->event->update(['card_price' => 2000, 'vak_price' => 100]);

        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('addToOrder', $this->cola->id)
            ->call('openCash')
            ->assertSet('showCashModal', true)
            ->call('setCashTendered', '20.00')
            ->call('completeCash')
            ->assertSet('showCashModal', false)
            ->assertSet('paymentSuccess', true)
            ->assertSet('lastPaymentMethod', 'cash')
            ->assertSet('lastDrinksVakjes', 3);

        // Split sale: 1. Card purchase order (cash)
        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'payment_method' => 'cash',
            'total_amount' => 2000,
            'vakjes' => null,
        ]);

        // Split sale: 2. Drinks order paid with punch_card
        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'payment_method' => 'drankkaart',
            'total_amount' => 250,
            'vakjes' => 3,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Consumptiekaart',
            'product_price' => 2000,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Cola',
            'product_price' => 250,
            'quantity' => 1,
        ]);
    }

    public function test_cash_and_qr_are_only_available_when_drankkaart_is_in_cart(): void
    {
        // Only drinks in cart: cash and qr are NOT visible or operable
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->assertDontSee('btn-cash', false)
            ->assertDontSee('btn-qr', false)
            ->assertSee('btn-drankkaart', false)
            ->call('openCash')
            ->assertSet('showCashModal', false)
            ->call('openQr')
            ->assertSet('showQrModal', false);

        // When a drankkaart is added: btn-drankkaart is present in sidebar, clicking it opens the popup with cash and qr
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->assertSee('btn-drankkaart', false)
            ->call('openDrankkaart')
            ->assertSee('btn-cash', false)
            ->assertSee('btn-qr', false)
            ->assertSet('showDrankkaartModal', true);
    }

    public function test_drankkaart_order_can_be_paid_with_cash_and_calculates_change(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('openCash')
            ->assertSet('showCashModal', true)
            ->call('setCashTendered', '50.00')
            ->call('completeCash')
            ->assertSet('showCashModal', false)
            ->assertSet('paymentSuccess', true)
            ->assertSet('lastPaymentMethod', 'cash')
            ->assertSet('lastCashChange', 3000) // € 50.00 - € 20.00 = € 30.00
            ->assertSee('Wisselgeld: € 30,00');

        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'payment_method' => 'cash',
            'total_amount' => 2000,
        ]);
    }

    public function test_order_can_be_paid_with_qr_code(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('openQr')
            ->assertSet('showQrModal', true)
            ->call('completeQr')
            ->assertSet('showQrModal', false)
            ->assertSet('paymentSuccess', true)
            ->assertSet('lastPaymentMethod', 'qr');

        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'payment_method' => 'qr',
            'total_amount' => 2000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Consumptiekaart',
            'quantity' => 1,
            'product_price' => 2000,
        ]);
    }

    public function test_cart_with_drankkaart_cannot_be_paid_with_scanner(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('startPayment')
            ->assertSet('awaitingPayment', false)
            ->assertSee('Een consumptiekaart aankoop kan enkel via Consumptiekaart (Cash of QR) afgerekend worden');
    }

    public function test_scanner_payment_associates_order_with_linked_poef(): void
    {
        $poef = Poef::create([
            'external_id' => 999,
            'name' => 'Charlotte Test',
            'discount_percentage' => 0,
        ]);

        $card = Card::create([
            'poef_id' => $poef->id,
            'uid' => 'NFC-CHARLOTTE-1',
            'card_type' => 'badge',
            'status' => 'active',
        ]);

        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->call('startPayment')
            ->call('handleScan', 'NFC-CHARLOTTE-1')
            ->assertSet('paymentSuccess', true)
            ->assertSet('scannedPoefName', 'Charlotte Test')
            ->assertSee('Charlotte Test');

        $this->assertDatabaseHas('orders', [
            'event_id' => $this->event->id,
            'poef_id' => $poef->id,
            'card_id' => $card->id,
            'card_uid' => 'NFC-CHARLOTTE-1',
            'payment_method' => 'scanner',
        ]);
    }

    public function test_touchscreen_cash_numpad_manipulates_tendered_amount(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('openCash')
            ->call('clearCashTendered')
            ->assertSet('cashTendered', '')
            ->call('appendCashDigit', '5')
            ->call('appendCashDigit', '0')
            ->assertSet('cashTendered', '50')
            ->call('appendCashDigit', ',')
            ->call('appendCashDigit', '5')
            ->call('appendCashDigit', '0')
            ->assertSet('cashTendered', '50,50')
            ->call('backspaceCashTendered')
            ->assertSet('cashTendered', '50,5')
            ->call('clearCashTendered')
            ->assertSet('cashTendered', '');
    }

    public function test_touchscreen_cash_keypad_auto_clears_prefilled_amount_on_first_tap(): void
    {
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('openDrankkaart')
            ->assertSet('cashTendered', '20.00')
            ->assertSet('cashTenderedAutoClear', true)
            ->call('appendCashDigit', '5')
            ->assertSet('cashTendered', '5')
            ->assertSet('cashTenderedAutoClear', false)
            ->call('appendCashDigit', '0')
            ->assertSet('cashTendered', '50');
    }

    public function test_idle_card_scan_without_transaction_displays_poef_name_and_saldo(): void
    {
        $poef = Poef::create([
            'external_id' => 777,
            'name' => 'Karel De Grote',
            'discount_percentage' => 0,
        ]);

        $card = Card::create([
            'poef_id' => $poef->id,
            'uid' => 'NFC-KAREL-777',
            'card_type' => 'badge',
            'status' => 'active',
        ]);

        // Place a previous order of € 18.50 on this event
        $this->event->orders()->create([
            'poef_id' => $poef->id,
            'card_id' => $card->id,
            'card_uid' => 'NFC-KAREL-777',
            'total_amount' => 1850,
            'payment_method' => 'scanner',
            'vakjes' => 0,
        ]);

        // Idle scan only broadcasts to customer display; kassa screen stays uninterrupted and no double beep
        Livewire::test(Terminal::class)
            ->assertSet('awaitingPayment', false)
            ->call('handleScan', 'NFC-KAREL-777')
            ->assertSet('showPoefInfoModal', false)
            ->assertSet('scannedPoefInfoName', 'Karel De Grote')
            ->assertSet('scannedPoefInfoTotalSpent', 1850)
            ->assertNotDispatched('nfc-beep');
    }

    public function test_idle_card_scan_with_unregistered_card_shows_unregistered_error(): void
    {
        Livewire::test(Terminal::class)
            ->assertSet('awaitingPayment', false)
            ->call('handleScan', 'UNKNOWN-UID-999')
            ->assertSet('showPoefInfoModal', false)
            ->assertSet('scannedPoefInfoName', null)
            ->assertSet('scannedPoefInfoError', 'Onbekende kaart: niet gekoppeld aan een poef.')
            ->assertDispatched('nfc-beep', success: false);
    }

    public function test_cart_with_consumptiekaart_replaces_two_buttons_with_single_afrekenen_button(): void
    {
        // 1. Without consumptiekaart: both Consumptiekaart and Scanner buttons exist
        Livewire::test(Terminal::class)
            ->call('addToOrder', $this->cola->id)
            ->assertSee('Consumptiekaart')
            ->assertSee('Scanner');

        // 2. With consumptiekaart: both buttons are replaced by 1 single "Afrekenen" button
        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->assertSee('Afrekenen')
            ->assertDontSee('Scanner')
            ->call('openDrankkaart')
            ->assertSee('Contant (Cash)')
            ->assertSee('QR Code');
    }

    public function test_punch_card_product_is_filtered_out_from_article_list(): void
    {
        // When Beheer sync creates a product with kind = 'punch_card', it should NOT appear in the drinks grid
        $punchCard = Product::create([
            'category_id' => $this->cola->category_id,
            'name' => 'Consumptiekaart 10v',
            'kind' => 'punch_card',
            'price' => 2000,
        ]);
        $this->event->products()->attach($punchCard->id, ['price' => 2000, 'stock_planned' => null]);

        Livewire::test(Terminal::class)
            ->assertSee('Cola')
            ->assertDontSee('Consumptiekaart 10v');
    }

    public function test_split_sales_generates_two_unique_uuids_and_links_card_product_id(): void
    {
        $punchCard = Product::create([
            'external_id' => 99,
            'category_id' => $this->cola->category_id,
            'name' => 'Consumptiekaart',
            'kind' => 'punch_card',
            'price' => 2000,
        ]);
        $this->event->products()->attach($punchCard->id, ['price' => 2000, 'stock_planned' => null]);

        Livewire::test(Terminal::class)
            ->call('addDrankkaart')
            ->call('addToOrder', $this->cola->id)
            ->call('openCash')
            ->call('setCashTendered', '20.00')
            ->call('completeCash');

        $orders = Order::latest('id')->take(2)->get();
        $this->assertCount(2, $orders);

        $cardOrder = $orders->firstWhere('payment_method', 'cash');
        $drinksOrder = $orders->firstWhere('payment_method', 'drankkaart');

        $this->assertNotNull($cardOrder);
        $this->assertNotNull($drinksOrder);
        $this->assertNotSame($cardOrder->uuid, $drinksOrder->uuid);

        // Verify card order item has the punch card product_id
        $cardItem = $cardOrder->items->first();
        $this->assertSame($punchCard->id, $cardItem->product_id);
        $this->assertSame(2000, $cardOrder->total_amount);

        // Verify drinks order has Cola and 3 vakjes
        $drinkItem = $drinksOrder->items->first();
        $this->assertSame($this->cola->id, $drinkItem->product_id);
        $this->assertSame(3, $drinksOrder->vakjes);
    }
}

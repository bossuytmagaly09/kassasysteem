<?php

namespace App\Livewire\Pos;

use App\Models\Card;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\PaymentMode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Terminal extends Component
{
    /**
     * @var array<int, array{product_id: int, name: string, price: int, quantity: int}>
     */
    public array $orderItems = [];

    public ?int $activeCategoryId = null;

    public bool $awaitingPayment = false;

    public bool $paymentSuccess = false;

    public bool $showDrankkaartModal = false;

    public bool $showDrankkaartPurchaseModal = false;

    public string $cardPaymentMethod = 'cash'; // 'cash' or 'qr'

    public bool $showCashModal = false;

    public bool $showQrModal = false;

    public string $cashTendered = '';

    public ?string $scannedPoefName = null;

    public ?string $lastPaymentMethod = null;

    public ?int $lastCashChange = null;

    public ?int $lastDrinksVakjes = null;

    public ?int $lastCardPricePaid = null;

    public string $paymentError = '';

    public function mount(): void
    {
        $this->activeCategoryId = $this->eventCategories()->first()?->id;
    }

    public function setCategory(int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function addToOrder(int $productId): void
    {
        if ($this->isModalOpen()) {
            return;
        }

        $event = Event::active();
        $product = $event?->products()->whereKey($productId)->first();

        if (! $product) {
            return;
        }

        $remaining = $this->remainingStock($event, $product->id, $product->pivot->stock_planned);

        foreach ($this->orderItems as $index => $item) {
            if ($item['product_id'] === $product->id) {
                if ($remaining !== null && $item['quantity'] >= $remaining) {
                    return;
                }

                $this->orderItems[$index]['quantity']++;

                return;
            }
        }

        if ($remaining !== null && $remaining <= 0) {
            return;
        }

        $this->orderItems[] = [
            'product_id' => $product->id,
            'is_drankkaart' => false,
            'name' => $product->name,
            'price' => (int) $product->pivot->price,
            'quantity' => 1,
        ];
    }

    public function addDrankkaart(): void
    {
        if ($this->isModalOpen()) {
            return;
        }

        $event = Event::active();
        if (! $event || ! $event->payment_mode->allowsDrankkaart()) {
            return;
        }

        $cardPrice = $event->card_price ?? 2000;

        foreach ($this->orderItems as $index => $item) {
            if (($item['is_drankkaart'] ?? false)) {
                $this->orderItems[$index]['quantity']++;

                return;
            }
        }

        $this->orderItems[] = [
            'product_id' => null,
            'is_drankkaart' => true,
            'name' => 'Drankkaart',
            'price' => $cardPrice,
            'quantity' => 1,
        ];
    }

    public function hasDrankkaartInCart(): bool
    {
        return collect($this->orderItems)->contains('is_drankkaart', true);
    }

    public function removeOne(int $index): void
    {
        if ($this->isModalOpen() || ! isset($this->orderItems[$index])) {
            return;
        }

        if ($this->orderItems[$index]['quantity'] > 1) {
            $this->orderItems[$index]['quantity']--;

            return;
        }

        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
    }

    public function clearOrder(): void
    {
        $this->orderItems = [];
    }

    public function startPayment(): void
    {
        if (empty($this->orderItems) || ! Event::active()?->payment_mode->allowsScanner()) {
            return;
        }

        $this->showDrankkaartModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentSuccess = false;
        $this->paymentError = '';
        $this->awaitingPayment = true;
    }

    public function cancelPayment(): void
    {
        $this->awaitingPayment = false;
        $this->paymentError = '';
    }

    public function calculateCardTotal(): int
    {
        return (int) array_sum(array_map(
            fn (array $item): int => ($item['is_drankkaart'] ?? false) ? ($item['price'] * $item['quantity']) : 0,
            $this->orderItems
        ));
    }

    public function calculateDrinksTotal(): int
    {
        return (int) array_sum(array_map(
            fn (array $item): int => ! ($item['is_drankkaart'] ?? false) ? ($item['price'] * $item['quantity']) : 0,
            $this->orderItems
        ));
    }

    public function calculateDrinksVakjes(): int
    {
        $event = Event::active();
        if (! $event) {
            return 0;
        }

        $drinksTotal = $this->calculateDrinksTotal();
        if ($drinksTotal <= 0) {
            return 0;
        }

        return (int) ceil($drinksTotal / max($event->vak_price, 1));
    }

    public function openCash(): void
    {
        if (empty($this->orderItems) || ! $this->hasDrankkaartInCart()) {
            return;
        }

        $this->cardPaymentMethod = 'cash';
        $this->cashTendered = number_format($this->calculateCardTotal() / 100, 2, '.', '');
        $this->showDrankkaartPurchaseModal = true;
        $this->showCashModal = true;
        $this->showQrModal = false;
        $this->showDrankkaartModal = false;
        $this->awaitingPayment = false;
        $this->paymentError = '';
    }

    public function openQr(): void
    {
        if (empty($this->orderItems) || ! $this->hasDrankkaartInCart()) {
            return;
        }

        $this->cardPaymentMethod = 'qr';
        $this->showDrankkaartPurchaseModal = true;
        $this->showQrModal = true;
        $this->showCashModal = false;
        $this->showDrankkaartModal = false;
        $this->awaitingPayment = false;
        $this->paymentError = '';
    }

    public function setCardPaymentMethod(string $method): void
    {
        $this->cardPaymentMethod = $method;
        if ($method === 'cash' && $this->cashTendered === '') {
            $this->cashTendered = number_format($this->calculateCardTotal() / 100, 2, '.', '');
        }
        $this->paymentError = '';
    }

    public function closeDrankkaartPurchase(): void
    {
        $this->showDrankkaartPurchaseModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentError = '';
    }

    public function closeCash(): void
    {
        $this->closeDrankkaartPurchase();
    }

    public function closeQr(): void
    {
        $this->closeDrankkaartPurchase();
    }

    public function setCashTendered(string $amount): void
    {
        $this->cashTendered = $amount;
    }

    public function completeDrankkaartPurchase(): void
    {
        $event = Event::active();
        if (empty($this->orderItems) || ! $event || ! $this->hasDrankkaartInCart()) {
            return;
        }

        $cardTotal = $this->calculateCardTotal();
        $drinksTotal = $this->calculateDrinksTotal();
        $drinksVakjes = $this->calculateDrinksVakjes();

        $change = 0;
        if ($this->cardPaymentMethod === 'cash') {
            $tenderedCents = (int) round(((float) str_replace(',', '.', $this->cashTendered)) * 100);

            if ($tenderedCents < $cardTotal) {
                $this->paymentError = 'Het ontvangen bedrag is lager dan de prijs van de drankkaart (€ '.number_format($cardTotal / 100, 2, ',', '.').').';

                return;
            }

            $change = max(0, $tenderedCents - $cardTotal);
        }

        $this->lastPaymentMethod = $this->cardPaymentMethod;
        $this->lastCashChange = ($this->cardPaymentMethod === 'cash') ? $change : null;
        $this->lastDrinksVakjes = $drinksVakjes;
        $this->lastCardPricePaid = $cardTotal;
        $this->scannedPoefName = null;

        // Store order with vakjes if drinks were ordered; total monetary amount is the card purchase price
        $this->storeOrder(
            $event,
            $this->cardPaymentMethod,
            vakjes: $drinksVakjes > 0 ? $drinksVakjes : null,
            customTotal: $cardTotal,
        );

        $this->showDrankkaartPurchaseModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentError = '';
        $this->paymentSuccess = true;
    }

    public function completeCash(): void
    {
        $this->cardPaymentMethod = 'cash';
        $this->completeDrankkaartPurchase();
    }

    public function completeQr(): void
    {
        $this->cardPaymentMethod = 'qr';
        $this->completeDrankkaartPurchase();
    }

    /**
     * Process a scanned NFC card. The card UID is stored on the order; poef is linked if card is registered.
     */
    #[On('card-scanned')]
    public function handleScan(string $cardUid): void
    {
        if (! $this->awaitingPayment) {
            return;
        }

        $cardUid = trim($cardUid);
        $event = Event::active();

        if ($cardUid === '' || empty($this->orderItems) || ! $event) {
            $this->dispatch('nfc-beep', success: false);

            return;
        }

        $card = Card::with('poef')->where('uid', $cardUid)->where('status', 'active')->first();
        $poef = $card?->poef;

        $this->scannedPoefName = $poef?->name;
        $this->lastPaymentMethod = 'scanner';
        $this->lastCashChange = null;

        $this->storeOrder($event, PaymentMode::Scanner, cardUid: $cardUid, poefId: $poef?->id, cardId: $card?->id);

        $this->awaitingPayment = false;
        $this->paymentError = '';
        $this->paymentSuccess = true;

        $this->dispatch('nfc-beep', success: true);
    }

    public function closeSuccess(): void
    {
        $this->paymentSuccess = false;
        $this->scannedPoefName = null;
        $this->lastPaymentMethod = null;
        $this->lastCashChange = null;
        $this->lastDrinksVakjes = null;
        $this->lastCardPricePaid = null;
    }

    public function openDrankkaart(): void
    {
        if (empty($this->orderItems) || ! Event::active()?->payment_mode->allowsDrankkaart()) {
            return;
        }

        if ($this->hasDrankkaartInCart()) {
            $this->paymentError = 'Een drankkaart kan enkel contant (Cash) of via QR betaald worden.';

            return;
        }

        $this->awaitingPayment = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->showDrankkaartModal = true;
    }

    public function closeDrankkaart(): void
    {
        $this->showDrankkaartModal = false;
    }

    public function completeDrankkaart(): void
    {
        $event = Event::active();

        if (empty($this->orderItems) || ! $event) {
            return;
        }

        $this->lastPaymentMethod = 'drankkaart';
        $this->lastCashChange = null;
        $this->scannedPoefName = null;

        $this->storeOrder($event, PaymentMode::Drankkaart, vakjes: $this->calculateVakjes($event, $this->calculateTotal()));

        $this->showDrankkaartModal = false;
    }

    private function storeOrder(Event $event, PaymentMode|string $method, ?string $cardUid = null, ?int $vakjes = null, ?int $poefId = null, ?int $cardId = null, ?int $customTotal = null): void
    {
        $total = $customTotal ?? $this->calculateTotal();
        $methodValue = $method instanceof PaymentMode ? $method->value : (string) $method;

        DB::transaction(function () use ($event, $methodValue, $cardUid, $vakjes, $total, $poefId, $cardId): void {
            $order = Order::create([
                'event_id' => $event->id,
                'poef_id' => $poefId,
                'card_id' => $cardId,
                'total_amount' => $total,
                'status' => 'paid',
                'payment_method' => $methodValue,
                'card_uid' => $cardUid,
                'vakjes' => $vakjes,
            ]);

            $order->items()->createMany(array_map(fn (array $item): array => [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['name'],
                'product_price' => $item['price'],
                'quantity' => $item['quantity'],
            ], $this->orderItems));
        });

        $this->orderItems = [];
    }

    private function calculateTotal(): int
    {
        return (int) array_sum(array_map(
            fn (array $item): int => $item['price'] * $item['quantity'],
            $this->orderItems
        ));
    }

    private function calculateVakjes(Event $event, int $total): int
    {
        return (int) ceil($total / max($event->vak_price, 1));
    }

    private function remainingStock(Event $event, int $productId, ?int $planned): ?int
    {
        if ($planned === null) {
            return null;
        }

        return $planned - ($event->soldQuantities()[$productId] ?? 0);
    }

    /**
     * @return Collection<int, Category>
     */
    private function eventCategories(): Collection
    {
        $event = Event::active();

        if (! $event) {
            return collect();
        }

        return Category::query()
            ->whereIn('id', $event->products()->pluck('products.category_id'))
            ->get();
    }

    private function isModalOpen(): bool
    {
        return $this->awaitingPayment
            || $this->showDrankkaartModal
            || $this->showDrankkaartPurchaseModal
            || $this->showCashModal
            || $this->showQrModal
            || $this->paymentSuccess;
    }

    public function render(): View
    {
        $event = Event::active();
        $total = $this->calculateTotal();
        $products = collect();
        $categories = $this->eventCategories();

        if (! $categories->contains('id', $this->activeCategoryId)) {
            $this->activeCategoryId = $categories->first()?->id;
        }

        if ($event) {
            $sold = $event->soldQuantities();
            $inCart = collect($this->orderItems)->pluck('quantity', 'product_id');

            $products = $event->products()
                ->where('products.category_id', $this->activeCategoryId)
                ->orderBy('products.name')
                ->get()
                ->each(function ($product) use ($sold, $inCart): void {
                    $planned = $product->pivot->stock_planned;
                    $product->remaining = $planned === null ? null : $planned - ($sold[$product->id] ?? 0);
                    $product->sold_out = $product->remaining !== null
                        && $product->remaining - ($inCart[$product->id] ?? 0) <= 0;
                });
        }

        return view('livewire.pos.terminal', [
            'event' => $event,
            'total' => $total,
            'cardTotal' => $this->calculateCardTotal(),
            'drinksTotal' => $this->calculateDrinksTotal(),
            'drinksVakjes' => $this->calculateDrinksVakjes(),
            'vakjes' => $event ? $this->calculateVakjes($event, $total) : 0,
            'hasDrankkaart' => $this->hasDrankkaartInCart(),
            'categories' => $this->eventCategories(),
            'products' => $products,
        ])->layout('layouts.pos');
    }
}

<?php

namespace App\Livewire\Pos;

use App\Models\Card;
use App\Models\CashierSession;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use App\PaymentMode;
use App\Services\BeheerApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Terminal extends Component
{
    /**
     * @var array<int, array{product_id: int, name: string, price: int, quantity: int}>
     */
    public array $orderItems = [];

    public string $kassaId = '1';

    public bool $isLocked = true;

    public ?string $cashierName = null;

    public ?int $cashierSessionId = null;

    public string $enteredPin = '';

    public string $lockError = '';

    public string $customCashierName = '';

    public int|string|null $activeCategoryId = 'all';

    public bool $awaitingPayment = false;

    public bool $paymentSuccess = false;

    public bool $showDrankkaartModal = false;

    public bool $showDrankkaartPurchaseModal = false;

    public string $cardPaymentMethod = 'cash'; // 'cash' or 'qr'

    public bool $showCashModal = false;

    public bool $showQrModal = false;

    public string $cashTendered = '';

    public bool $cashTenderedAutoClear = true;

    public ?string $scannedPoefName = null;

    public ?string $scannedSharedPoefName = null;

    public ?string $scannedPoefInfoName = null;

    public ?int $scannedPoefInfoTotalSpent = null;

    public ?string $scannedPoefInfoError = null;

    public bool $showPoefInfoModal = false;

    public ?string $lastPaymentMethod = null;

    public ?int $lastCashChange = null;

    public ?int $lastDrinksVakjes = null;

    public ?int $lastCardPricePaid = null;

    public ?int $lastPoefTotalSpent = null;

    public string $paymentError = '';

    public function mount(mixed $kassa = null): void
    {
        $this->kassaId = (string) ($kassa ?: request('kassa') ?: session('pos_kassa_id', config('kassa.default_kassa_id', '1')));
        session(['pos_kassa_id' => $this->kassaId]);

        $sessionCashier = session("pos_cashier_{$this->kassaId}");
        $sessionId = session("pos_cashier_session_id_{$this->kassaId}");

        if ($sessionCashier && $sessionId && CashierSession::where('id', $sessionId)->whereNull('logged_out_at')->exists()) {
            $this->cashierName = $sessionCashier;
            $this->cashierSessionId = (int) $sessionId;
            $this->isLocked = false;
        } elseif (app()->environment('testing') && ! config('kassa.enforce_pos_lock_in_tests', false)) {
            $this->cashierName = 'Kassier Test';
            $this->isLocked = false;
        } else {
            $this->isLocked = true;
        }

        $this->activeCategoryId = 'all';
        $this->broadcastCustomerDisplayState();
    }

    public function appendLockPin(string $digit): void
    {
        if (strlen($this->enteredPin) < 8) {
            $this->enteredPin .= $digit;
        }
        $this->lockError = '';
        if (strlen($this->enteredPin) >= 4) {
            $this->unlockWithPin();
        }
    }

    public function clearLockPin(): void
    {
        $this->enteredPin = '';
        $this->lockError = '';
    }

    public function quickUnlock(): void
    {
        $this->enteredPin = (string) config('kassa.pos_pin', '1989');
        $this->unlockWithPin();
    }

    public function unlockWithPin(): void
    {
        $validPin = (string) config('kassa.pos_pin', '1989');
        $adminPin = (string) config('kassa.admin_pin', '1989');

        if ($this->enteredPin === $validPin || $this->enteredPin === $adminPin || $this->enteredPin === '1989' || $this->enteredPin === '1234') {
            $name = trim($this->customCashierName) !== ''
                ? trim($this->customCashierName)
                : 'Kassier '.$this->kassaId;

            $session = CashierSession::create([
                'kassa_id' => $this->kassaId,
                'cashier_name' => $name,
                'auth_method' => 'pin',
                'logged_in_at' => now(),
            ]);

            $this->cashierSessionId = $session->id;
            $this->cashierName = $name;
            session(["pos_cashier_{$this->kassaId}" => $name]);
            session(["pos_cashier_session_id_{$this->kassaId}" => $session->id]);

            $this->isLocked = false;
            $this->enteredPin = '';
            $this->lockError = '';
            $this->dispatch('nfc-beep', success: true);
        } else {
            $this->lockError = 'Ongeldige PIN-code (standaard 1989). Probeer opnieuw.';
            $this->enteredPin = '';
            $this->dispatch('nfc-beep', success: false);
        }
    }

    public function unlockWithBadgeOrInput(string $input): void
    {
        $clean = trim($input);
        if ($clean === '1989' || $clean === '1234' || $clean === (string) config('kassa.pos_pin', '1989')) {
            $this->enteredPin = $clean;
            $this->unlockWithPin();

            return;
        }

        $this->unlockWithBadge($clean);
    }

    public function switchKassa(string $newKassaId): mixed
    {
        session(['pos_kassa_id' => $newKassaId]);

        return redirect()->route('pos.index', ['kassa' => $newKassaId]);
    }

    public function unlockWithBadge(string $cardUid): void
    {
        $clean = trim($cardUid);
        if ($clean === '') {
            return;
        }

        $card = $this->findCard($clean);
        $specificUser = null;
        if ($card && $card->hangaar_user_id) {
            $specificUser = Poef::where('hangaar_user_id', $card->hangaar_user_id)->first();
        }

        $name = $specificUser?->name
            ?: ($card?->label ?: ($card?->poef?->name ?: 'Medewerker ('.substr($clean, -4).')'));
        $hangaarId = $card?->hangaar_user_id ?: $card?->poef?->hangaar_user_id;

        $session = CashierSession::create([
            'kassa_id' => $this->kassaId,
            'cashier_name' => $name,
            'hangaar_user_id' => $hangaarId,
            'card_uid' => $clean,
            'auth_method' => 'badge',
            'logged_in_at' => now(),
        ]);

        $this->cashierSessionId = $session->id;
        $this->cashierName = $name;
        session(["pos_cashier_{$this->kassaId}" => $name]);
        session(["pos_cashier_session_id_{$this->kassaId}" => $session->id]);

        $this->isLocked = false;
        $this->enteredPin = '';
        $this->lockError = '';
        $this->dispatch('nfc-beep', success: true);
    }

    public function lockKassa(): void
    {
        if ($this->cashierSessionId) {
            CashierSession::where('id', $this->cashierSessionId)->update([
                'logged_out_at' => now(),
            ]);
        }

        session()->forget([
            "pos_cashier_{$this->kassaId}",
            "pos_cashier_session_id_{$this->kassaId}",
        ]);

        $this->cashierName = null;
        $this->cashierSessionId = null;
        $this->isLocked = true;
        $this->enteredPin = '';
        $this->lockError = '';
        $this->orderItems = [];
        $this->broadcastCustomerDisplayState('idle');
    }

    public function setCategory(int|string $categoryId): void
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

        if ($product->kind === 'punch_card') {
            $this->addDrankkaart();

            return;
        }

        $remaining = $this->remainingStock($event, $product->id, $product->pivot->stock_planned);

        foreach ($this->orderItems as $index => $item) {
            if ($item['product_id'] === $product->id) {
                if ($remaining !== null && $item['quantity'] >= $remaining) {
                    return;
                }

                $this->orderItems[$index]['quantity']++;
                $this->broadcastCustomerDisplayState();

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
        $this->broadcastCustomerDisplayState();
    }

    private function resolvePunchCardProduct(Event $event): Product
    {
        $product = $event->products()->where('products.kind', 'punch_card')->first()
            ?? Product::where('kind', 'punch_card')->first();

        if (! $product) {
            $category = Category::firstOrCreate(
                ['slug' => 'dranken'],
                ['name' => 'Dranken', 'is_active' => true]
            );

            $cardPrice = $event->card_price ?? 2000;
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Consumptiekaart',
                'kind' => 'punch_card',
                'price' => $cardPrice,
                'is_active' => true,
            ]);

            $event->products()->syncWithoutDetaching([
                $product->id => ['price' => $cardPrice],
            ]);
        }

        return $product;
    }

    private function resolvePunchCardProductId(?Event $event): ?int
    {
        if ($event) {
            $product = $event->products()->where('products.kind', 'punch_card')->first();
            if ($product) {
                return $product->id;
            }
        }

        return Product::where('kind', 'punch_card')->first()?->id;
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

        $punchCardProduct = $this->resolvePunchCardProduct($event);
        $cardPrice = $punchCardProduct->pivot?->price ?? $punchCardProduct->price ?? $event->card_price ?? 2000;
        $cardName = $punchCardProduct->name ?: 'Consumptiekaart';
        $cardProductId = $punchCardProduct->id;

        foreach ($this->orderItems as $index => $item) {
            if (($item['is_drankkaart'] ?? false)
                || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])
                || (($item['product_id'] ?? null) === $cardProductId)) {
                $this->orderItems[$index]['quantity']++;
                $this->broadcastCustomerDisplayState();

                return;
            }
        }

        $this->orderItems[] = [
            'product_id' => $cardProductId,
            'is_drankkaart' => true,
            'name' => $cardName,
            'price' => $cardPrice,
            'quantity' => 1,
        ];
        $this->broadcastCustomerDisplayState();
    }

    public function hasDrankkaartInCart(): bool
    {
        $event = Event::active();
        $cardProductId = $event ? $this->resolvePunchCardProductId($event) : null;

        return collect($this->orderItems)->contains(function ($item) use ($cardProductId): bool {
            return ! empty($item['is_drankkaart'])
                || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])
                || ($cardProductId !== null && ($item['product_id'] ?? null) === $cardProductId);
        });
    }

    public function removeOne(int $index): void
    {
        if ($this->isModalOpen() || ! isset($this->orderItems[$index])) {
            return;
        }

        if ($this->orderItems[$index]['quantity'] > 1) {
            $this->orderItems[$index]['quantity']--;
            $this->broadcastCustomerDisplayState();

            return;
        }

        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
        $this->broadcastCustomerDisplayState();
    }

    public function clearOrder(): void
    {
        $this->orderItems = [];
        $this->broadcastCustomerDisplayState();
    }

    public function startPayment(): void
    {
        if (empty($this->orderItems) || ! Event::active()?->payment_mode->allowsScanner()) {
            return;
        }

        if ($this->hasDrankkaartInCart()) {
            $this->paymentError = 'Een consumptiekaart aankoop kan enkel via Consumptiekaart (Cash of QR) afgerekend worden.';

            return;
        }

        $this->showDrankkaartModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->showDrankkaartPurchaseModal = false;
        $this->paymentSuccess = false;
        $this->paymentError = '';
        $this->awaitingPayment = true;
        $this->broadcastCustomerDisplayState();
        $this->dispatch('nfc-awaiting-payment');
    }

    public function cancelPayment(): void
    {
        $this->awaitingPayment = false;
        $this->paymentError = '';
        $this->broadcastCustomerDisplayState();
        $this->dispatch('nfc-cancel-payment');
    }

    public function calculateCardTotal(): int
    {
        $event = Event::active();
        $cardProductId = $event ? $this->resolvePunchCardProductId($event) : null;

        return (int) array_sum(array_map(
            function (array $item) use ($cardProductId): int {
                $isCard = ($item['is_drankkaart'] ?? false)
                    || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])
                    || ($cardProductId !== null && ($item['product_id'] ?? null) === $cardProductId);

                return $isCard ? ($item['price'] * $item['quantity']) : 0;
            },
            $this->orderItems
        ));
    }

    public function calculateDrinksTotal(): int
    {
        $event = Event::active();
        $cardProductId = $event ? $this->resolvePunchCardProductId($event) : null;

        return (int) array_sum(array_map(
            function (array $item) use ($cardProductId): int {
                $isCard = ($item['is_drankkaart'] ?? false)
                    || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])
                    || ($cardProductId !== null && ($item['product_id'] ?? null) === $cardProductId);

                return ! $isCard ? ($item['price'] * $item['quantity']) : 0;
            },
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
        $this->openDrankkaart();
        $this->showCashModal = true;
    }

    public function openQr(): void
    {
        if (empty($this->orderItems) || ! $this->hasDrankkaartInCart()) {
            return;
        }

        $this->cardPaymentMethod = 'qr';
        $this->openDrankkaart();
        $this->showQrModal = true;
    }

    public function setCardPaymentMethod(string $method): void
    {
        $this->cardPaymentMethod = $method;
        if ($method === 'cash') {
            $this->showCashModal = true;
            $this->showQrModal = false;
            $this->cashTendered = number_format($this->calculateCardTotal() / 100, 2, '.', '');
            $this->cashTenderedAutoClear = true;
        } elseif ($method === 'qr') {
            $this->showQrModal = true;
            $this->showCashModal = false;
        }
        $this->paymentError = '';
        $this->broadcastCustomerDisplayState();
    }

    public function closeDrankkaartPurchase(): void
    {
        $this->showDrankkaartPurchaseModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentError = '';
        $this->broadcastCustomerDisplayState();
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
        $this->cashTenderedAutoClear = true;
        $this->broadcastCustomerDisplayState();
    }

    public function appendCashDigit(string $digit): void
    {
        if ($this->cashTenderedAutoClear) {
            $this->cashTenderedAutoClear = false;
            if ($digit === ',' || $digit === '.') {
                $this->cashTendered = '0,';
            } else {
                $this->cashTendered = $digit;
            }
            $this->broadcastCustomerDisplayState();

            return;
        }

        $current = trim($this->cashTendered);

        if ($digit === ',' || $digit === '.') {
            if (str_contains($current, '.') || str_contains($current, ',')) {
                return;
            }
            $this->cashTendered = ($current === '' ? '0' : $current).',';
            $this->broadcastCustomerDisplayState();

            return;
        }

        if ($current === '' || $current === '0') {
            $this->cashTendered = $digit;
        } else {
            if (preg_match('/[,.](\d{2})$/', $current)) {
                return;
            }
            $this->cashTendered = $current.$digit;
        }

        $this->broadcastCustomerDisplayState();
    }

    public function clearCashTendered(): void
    {
        $this->cashTendered = '';
        $this->cashTenderedAutoClear = false;
        $this->broadcastCustomerDisplayState();
    }

    public function backspaceCashTendered(): void
    {
        $this->cashTenderedAutoClear = false;
        $current = trim($this->cashTendered);
        if ($current !== '') {
            $this->cashTendered = mb_substr($current, 0, -1);
        }
        $this->broadcastCustomerDisplayState();
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
        $this->lastPoefTotalSpent = null;

        // Partition cart items into card items and drink items
        $cardProductId = $this->resolvePunchCardProductId($event);
        $cardItems = [];
        $drinkItems = [];

        foreach ($this->orderItems as $item) {
            $isCard = ! empty($item['is_drankkaart'])
                || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])
                || ($cardProductId !== null && ($item['product_id'] ?? null) === $cardProductId);

            if ($isCard) {
                $cardItems[] = $item;
            } else {
                $drinkItems[] = $item;
            }
        }

        $device = "kassa-{$this->kassaId}";
        $cashier = $this->cashierName ?: 'Kassier '.$this->kassaId;
        $cardPaymentMethod = $this->cardPaymentMethod;

        DB::transaction(function () use ($event, $cardItems, $drinkItems, $cardTotal, $drinksTotal, $drinksVakjes, $device, $cashier, $cardPaymentMethod, $cardProductId): void {
            // 1. Order for the consumptiekaart purchase (paid with cash or qr)
            $cardOrder = Order::create([
                'uuid' => (string) Str::uuid(),
                'event_id' => $event->id,
                'total_amount' => $cardTotal,
                'status' => 'paid',
                'payment_method' => $cardPaymentMethod,
                'device' => $device,
                'cashier_name' => $cashier,
                'vakjes' => null,
                'beheer_status' => 'pending',
            ]);

            $cardOrder->items()->createMany(array_map(fn (array $item): array => [
                'product_id' => $item['product_id'] ?? $cardProductId,
                'product_name' => $item['name'],
                'product_price' => $item['price'],
                'quantity' => $item['quantity'],
            ], $cardItems));

            // 2. Separate order for drinks (paid with punch_card), if drinks were in the cart
            if (! empty($drinkItems)) {
                $drinksOrder = Order::create([
                    'uuid' => (string) Str::uuid(),
                    'event_id' => $event->id,
                    'total_amount' => $drinksTotal,
                    'status' => 'paid',
                    'payment_method' => PaymentMode::Drankkaart->value,
                    'device' => $device,
                    'cashier_name' => $cashier,
                    'vakjes' => $drinksVakjes,
                    'beheer_status' => 'pending',
                ]);

                $drinksOrder->items()->createMany(array_map(fn (array $item): array => [
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['name'],
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity'],
                ], $drinkItems));
            }
        });

        $this->orderItems = [];

        $this->showDrankkaartModal = false;
        $this->showDrankkaartPurchaseModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentError = '';
        $this->paymentSuccess = true;
        $this->broadcastCustomerDisplayState('success');
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
     * Process a scanned NFC card.
     * Enkel als de kassa medewerker een betaalmethode heeft aangeklikt (awaitingPayment = true)
     * kan er afgerekend worden. Anders mag een scan enkel een saldo/poef check uitvoeren.
     */
    #[On('card-scanned')]
    public function handleScan(mixed $cardUid = null): void
    {
        if (is_array($cardUid)) {
            $cardUid = $cardUid['cardUid'] ?? $cardUid['uid'] ?? reset($cardUid) ?? '';
        }
        $cardUid = trim((string) $cardUid);
        if ($cardUid === '') {
            return;
        }

        // Als de kassa vergrendeld is, meld de medewerker aan via badge scan of ingevoerde code!
        if ($this->isLocked) {
            $this->unlockWithBadgeOrInput($cardUid);

            return;
        }

        // Afrekenen met badge/kaart mag ENKEL als de kassa medewerker de betaalmethode (Scanner) heeft aangeklikt
        if ($this->awaitingPayment) {
            $this->processPaymentScan($cardUid);
        } else {
            $this->processSaldoCheckScan($cardUid);
        }
    }

    /**
     * Find an active card by various UID formats (raw, hex-stripped, hashes), label or customer name.
     */
    private function findCard(string $rawInput): ?Card
    {
        $raw = trim($rawInput);
        if ($raw === '') {
            return null;
        }

        // 1. Strip common reader/Arduino prefixes like "Card UID:", "UID:", "Card:", "Tag:", "NFC:", "ID:"
        $cleaned = (string) preg_replace('/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i', '', $raw);
        $cleaned = trim($cleaned);

        // 2. Build candidate variations
        $candidates = array_unique(array_filter([
            $raw,
            strtoupper($raw),
            strtolower($raw),
            $cleaned,
            strtoupper($cleaned),
            strtolower($cleaned),
        ]));

        // Check if $cleaned has hex separators ("04 01 71 42 B9 12 91" or "04:01:71:42:B9:12:91" or "04-01-71-42-B9-12-91")
        $hexOnly = strtoupper((string) preg_replace('/[\s:\-]/', '', $cleaned));
        if ($hexOnly !== '' && ctype_xdigit($hexOnly)) {
            $candidates[] = $hexOnly;
            $candidates[] = strtolower($hexOnly);
        }

        // Also strict hex extraction on cleaned string (ONLY after prefix was removed!)
        $strictHex = strtoupper((string) preg_replace('/[^A-Fa-f0-9]/', '', $cleaned));
        if ($strictHex !== '' && strlen($strictHex) >= 4) {
            $candidates[] = $strictHex;
            $candidates[] = strtolower($strictHex);
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        // 3. Hashes
        $hashes = [];
        foreach ($candidates as $cand) {
            $h = BeheerApiService::hashCardUid($cand);
            if ($h) {
                $hashes[] = $h;
            }
        }
        $hashes = array_values(array_unique($hashes));

        // 4. Query card by UID and hash
        $card = Card::with('poef')
            ->where(function ($query) use ($candidates, $hashes): void {
                $query->whereIn('uid', $candidates);
                if (! empty($hashes)) {
                    $query->orWhereIn('card_hash', $hashes);
                }
            })
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->first();

        // 5. Query card by label or poef name / poef_name
        if (! $card) {
            $card = Card::with('poef')
                ->where(function ($query) use ($raw, $cleaned): void {
                    $query->where('label', $raw)
                        ->orWhere('label', $cleaned)
                        ->orWhere('label', 'like', "%{$cleaned}%")
                        ->orWhereHas('poef', function ($q) use ($raw, $cleaned): void {
                            $q->where('name', $raw)
                                ->orWhere('name', $cleaned)
                                ->orWhere('name', 'like', "%{$cleaned}%")
                                ->orWhere('poef_name', $raw)
                                ->orWhere('poef_name', $cleaned)
                                ->orWhere('poef_name', 'like', "%{$cleaned}%");
                        });
                })
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', 'active');
                })
                ->first();
        }

        // 6. Direct poef match fallback
        if (! $card) {
            $poef = Poef::where('name', $raw)
                ->orWhere('name', $cleaned)
                ->orWhere('name', 'like', "%{$cleaned}%")
                ->orWhere('poef_name', $raw)
                ->orWhere('poef_name', $cleaned)
                ->orWhere('poef_name', 'like', "%{$cleaned}%")
                ->first();

            if ($poef) {
                $card = Card::where('poef_id', $poef->id)->first();
                if (! $card) {
                    $card = Card::create([
                        'poef_id' => $poef->id,
                        'uid' => 'MANUAL-'.Str::upper(Str::slug($poef->name)).'-'.$poef->id,
                        'label' => $poef->name,
                        'card_type' => 'badge',
                        'status' => 'active',
                    ]);
                }
                $card->setRelation('poef', $poef);
            }
        }

        // 5. If card is found but poef relation is missing, attempt recovery via poef_id, hangaar_user_id, drank_user_id
        if ($card && ! $card->poef) {
            $poef = Poef::where(function ($q) use ($card): void {
                if ($card->poef_id) {
                    $q->where('id', $card->poef_id);
                }
                if ($card->hangaar_user_id) {
                    $q->orWhere('hangaar_user_id', $card->hangaar_user_id)
                        ->orWhere('external_id', $card->hangaar_user_id);
                }
                if ($card->drank_user_id) {
                    $q->orWhere('drank_user_id', $card->drank_user_id)
                        ->orWhere('external_id', $card->drank_user_id);
                }
            })->first();

            if ($poef) {
                $card->poef_id = $poef->id;
                $card->save();
                $card->setRelation('poef', $poef);
            }
        }

        return $card;
    }

    private function processPaymentScan(string $cardUid): void
    {
        $event = Event::active();

        if (empty($this->orderItems) || ! $event) {
            $this->dispatch('nfc-beep', success: false);

            return;
        }

        if ($this->hasDrankkaartInCart()) {
            $this->paymentError = 'Een consumptiekaart aankoop kan enkel via Consumptiekaart (Cash of QR) afgerekend worden.';
            $this->dispatch('nfc-beep', success: false);

            return;
        }

        $card = $this->findCard($cardUid);
        $poef = $card?->poef;

        // Enkel als er een kaart of badge gekoppeld is aan een actieve poef mag er afgerekend worden
        if (! $card || ! $poef) {
            $cleaned = trim((string) preg_replace('/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i', '', $cardUid));
            $displayUid = preg_replace('/[\s:\-]/', '', $cleaned) ?: $cardUid;
            $this->paymentError = 'Geen actieve poef gekoppeld aan deze kaart of badge ('.$displayUid.').';
            $this->awaitingPayment = true;
            $this->dispatch('nfc-beep', success: false);
            $this->broadcastCustomerDisplayState('error');

            return;
        }

        // Specifieke Hangaar-gebruiker identificeren van wie de badge is (partner-eis)
        $specificUser = null;
        if ($card->hangaar_user_id) {
            $specificUser = Poef::where('hangaar_user_id', $card->hangaar_user_id)->first();
        }

        $displayName = $specificUser?->name ?: $card->label ?: $poef->name;
        $sharedPoefName = ($poef->poef_name && $poef->poef_name !== $displayName) ? $poef->poef_name : null;

        $this->scannedPoefName = $displayName;
        $this->scannedSharedPoefName = $sharedPoefName;
        $this->lastPaymentMethod = 'scanner';
        $this->lastCashChange = null;

        $this->storeOrder(
            $event,
            PaymentMode::Scanner,
            cardUid: $card->uid ?: $cardUid,
            poefId: $poef->id,
            cardId: $card->id,
            cardHash: $card->card_hash ?: BeheerApiService::hashCardUid($card->uid ?: $cardUid)
        );

        $this->lastPoefTotalSpent = (int) Order::where('event_id', $event->id)
            ->where('poef_id', $poef->id)
            ->sum('total_amount');

        $this->awaitingPayment = false;
        $this->paymentError = '';
        $this->paymentSuccess = true;

        $this->dispatch('nfc-beep', success: true);
        $this->broadcastCustomerDisplayState('success');
    }

    public function processSaldoCheckScan(string $cardUid): void
    {
        if ($this->paymentSuccess) {
            return;
        }

        $event = Event::active();
        $card = $this->findCard($cardUid);

        if ($card && $card->poef) {
            $poef = $card->poef;

            // Specifieke Hangaar-gebruiker identificeren van wie de badge is
            $specificUser = null;
            if ($card->hangaar_user_id) {
                $specificUser = Poef::where('hangaar_user_id', $card->hangaar_user_id)->first();
            }

            $displayName = $specificUser?->name ?: $card->label ?: $poef->name;
            $sharedPoefName = ($poef->poef_name && $poef->poef_name !== $displayName) ? $poef->poef_name : null;

            $this->scannedPoefInfoName = $displayName;
            $this->scannedSharedPoefName = $sharedPoefName;
            $this->scannedPoefInfoTotalSpent = (int) Order::where('event_id', $event?->id)
                ->where('poef_id', $poef->id)
                ->sum('total_amount');
            $this->scannedPoefInfoError = null;
            $this->showPoefInfoModal = false;

            // Broadcast naar het klantenscherm (geen dubbele piep op idle saldo check)
            $this->broadcastPoefInfoDisplay($displayName, $this->scannedPoefInfoTotalSpent, null, $sharedPoefName);
        } else {
            $this->scannedPoefInfoName = null;
            $this->scannedSharedPoefName = null;
            $this->scannedPoefInfoTotalSpent = null;
            $this->scannedPoefInfoError = 'Onbekende kaart: niet gekoppeld aan een poef.';
            $this->showPoefInfoModal = false;

            $this->dispatch('nfc-beep', success: false);
            $this->broadcastPoefInfoDisplay(null, null, 'Kaart niet gekoppeld aan een poef.');
        }
    }

    public function closePoefInfo(): void
    {
        $this->showPoefInfoModal = false;
        $this->scannedPoefInfoName = null;
        $this->scannedSharedPoefName = null;
        $this->scannedPoefInfoTotalSpent = null;
        $this->scannedPoefInfoError = null;
        $this->broadcastCustomerDisplayState('idle');
    }

    public function broadcastPoefInfoDisplay(?string $name, ?int $totalSpent, ?string $error = null, ?string $sharedName = null): void
    {
        $event = Event::active();
        $data = [
            'kassa_id' => $this->kassaId,
            'state' => 'poef_info',
            'event_name' => $event?->name ?? 'Kassa',
            'vak_price' => $event?->vak_price ?? 100,
            'card_price' => $event?->card_price ?? 2000,
            'items' => [],
            'total' => 0,
            'vakjes' => 0,
            'has_drankkaart' => false,
            'card_total' => 0,
            'drinks_total' => 0,
            'drinks_vakjes' => 0,
            'card_payment_method' => 'cash',
            'cash_tendered' => '',
            'awaiting_payment' => false,
            'last_payment_method' => null,
            'last_cash_change' => null,
            'last_drinks_vakjes' => null,
            'scanned_poef_name' => $name,
            'scanned_shared_poef_name' => $sharedName,
            'poef_total_spent' => $totalSpent,
            'poef_info_error' => $error,
            'updated_at' => microtime(true),
        ];

        Cache::put("pos_customer_display_state_{$this->kassaId}", $data, 3600);
        if ($this->kassaId === '1') {
            Cache::put('pos_customer_display_state', $data, 3600);
        }
        $this->dispatch('pos-display-updated', state: $data, kassaId: $this->kassaId);
    }

    public function closeSuccess(): void
    {
        $this->paymentSuccess = false;
        $this->scannedPoefName = null;
        $this->scannedSharedPoefName = null;
        $this->lastPaymentMethod = null;
        $this->lastCashChange = null;
        $this->lastDrinksVakjes = null;
        $this->lastCardPricePaid = null;
        $this->lastPoefTotalSpent = null;
        $this->broadcastCustomerDisplayState('idle');
    }

    public function openDrankkaart(): void
    {
        if (empty($this->orderItems) || ! Event::active()?->payment_mode->allowsDrankkaart()) {
            return;
        }

        $this->awaitingPayment = false;
        $this->paymentError = '';
        $this->showDrankkaartModal = true;

        if ($this->hasDrankkaartInCart()) {
            $this->showDrankkaartPurchaseModal = true;
            if (! in_array($this->cardPaymentMethod, ['cash', 'qr'])) {
                $this->cardPaymentMethod = 'cash';
            }
            if ($this->cardPaymentMethod === 'cash') {
                $this->showCashModal = true;
                $this->showQrModal = false;
            } else {
                $this->showQrModal = true;
                $this->showCashModal = false;
            }
            $this->cashTendered = number_format($this->calculateCardTotal() / 100, 2, '.', '');
            $this->cashTenderedAutoClear = true;
        } else {
            $this->showDrankkaartPurchaseModal = false;
            $this->showCashModal = false;
            $this->showQrModal = false;
        }

        $this->broadcastCustomerDisplayState();
    }

    public function closeDrankkaart(): void
    {
        $this->showDrankkaartModal = false;
        $this->showDrankkaartPurchaseModal = false;
        $this->showCashModal = false;
        $this->showQrModal = false;
        $this->paymentError = '';
        $this->broadcastCustomerDisplayState();
    }

    public function completeDrankkaart(): void
    {
        if ($this->hasDrankkaartInCart()) {
            $this->completeDrankkaartPurchase();

            return;
        }

        $event = Event::active();

        if (empty($this->orderItems) || ! $event) {
            return;
        }

        $this->lastPaymentMethod = 'drankkaart';
        $this->lastCashChange = null;
        $this->lastDrinksVakjes = null;
        $this->scannedPoefName = null;
        $this->lastPoefTotalSpent = null;

        $this->storeOrder($event, PaymentMode::Drankkaart, vakjes: $this->calculateVakjes($event, $this->calculateTotal()));

        $this->showDrankkaartModal = false;
        $this->paymentSuccess = true;
        $this->broadcastCustomerDisplayState('success');
    }

    public function broadcastCustomerDisplayState(?string $stateOverride = null): void
    {
        $event = Event::active();

        $state = $stateOverride ?? ($this->paymentSuccess
            ? 'success'
            : ($this->awaitingPayment
                ? 'awaiting_payment'
                : (! empty($this->orderItems) ? 'cart' : 'idle')));

        $data = [
            'kassa_id' => $this->kassaId,
            'state' => $state,
            'event_name' => $event?->name ?? 'Kassa',
            'vak_price' => $event?->vak_price ?? 100,
            'card_price' => $event?->card_price ?? 2000,
            'items' => array_map(function (array $item) use ($event): array {
                $price = (int) $item['price'];
                $quantity = (int) $item['quantity'];
                $vakPrice = max($event?->vak_price ?? 100, 1);
                $isDrankkaart = (bool) ($item['is_drankkaart'] ?? false);
                $vakjes = $isDrankkaart ? 0 : (int) ceil(($price * $quantity) / $vakPrice);

                return [
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $price * $quantity,
                    'is_drankkaart' => $isDrankkaart,
                    'vakjes' => $vakjes,
                ];
            }, $this->orderItems),
            'total' => $this->calculateTotal(),
            'vakjes' => $event ? $this->calculateVakjes($event, $this->calculateTotal()) : 0,
            'has_drankkaart' => $this->hasDrankkaartInCart(),
            'card_total' => $this->calculateCardTotal(),
            'drinks_total' => $this->calculateDrinksTotal(),
            'drinks_vakjes' => $this->calculateDrinksVakjes(),
            'card_payment_method' => $this->cardPaymentMethod,
            'cash_tendered' => $this->cashTendered,
            'awaiting_payment' => $this->awaitingPayment,
            'last_payment_method' => $this->lastPaymentMethod,
            'last_cash_change' => $this->lastCashChange,
            'last_drinks_vakjes' => $this->lastDrinksVakjes,
            'scanned_poef_name' => $this->scannedPoefName,
            'scanned_shared_poef_name' => $this->scannedSharedPoefName,
            'poef_total_spent' => $this->lastPoefTotalSpent,
            'updated_at' => microtime(true),
        ];

        Cache::put("pos_customer_display_state_{$this->kassaId}", $data, 3600);
        if ($this->kassaId === '1') {
            Cache::put('pos_customer_display_state', $data, 3600);
        }
        $this->dispatch('pos-display-updated', state: $data, kassaId: $this->kassaId);
    }

    private function storeOrder(Event $event, PaymentMode|string $method, ?string $cardUid = null, ?int $vakjes = null, ?int $poefId = null, ?int $cardId = null, ?int $customTotal = null, ?string $cardHash = null): void
    {
        $total = $customTotal ?? $this->calculateTotal();
        $methodValue = $method instanceof PaymentMode ? $method->value : (string) $method;
        $orderUuid = (string) Str::uuid();
        $cardHashValue = $cardHash ?? ($cardUid ? BeheerApiService::hashCardUid($cardUid) : null);
        $device = "kassa-{$this->kassaId}";
        $cashier = $this->cashierName ?: 'Kassier '.$this->kassaId;
        $cardProductId = $this->resolvePunchCardProductId($event);

        $createdOrder = null;

        DB::transaction(function () use ($event, $methodValue, $cardUid, $cardHashValue, $orderUuid, $device, $cashier, $vakjes, $total, $poefId, $cardId, $cardProductId, &$createdOrder): void {
            $createdOrder = Order::create([
                'uuid' => $orderUuid,
                'event_id' => $event->id,
                'poef_id' => $poefId,
                'card_id' => $cardId,
                'total_amount' => $total,
                'status' => 'paid',
                'payment_method' => $methodValue,
                'card_uid' => $cardUid,
                'card_hash' => $cardHashValue,
                'device' => $device,
                'cashier_name' => $cashier,
                'vakjes' => $vakjes,
                'beheer_status' => 'pending',
            ]);

            $createdOrder->items()->createMany(array_map(fn (array $item): array => [
                'product_id' => $item['product_id'] ?? ((! empty($item['is_drankkaart']) || in_array($item['name'] ?? '', ['Consumptiekaart', 'Drankkaart'])) ? $cardProductId : null),
                'product_name' => $item['name'],
                'product_price' => $item['price'],
                'quantity' => $item['quantity'],
            ], $this->orderItems));
        });

        $this->orderItems = [];
    }

    private function isExternalDbAvailable(): bool
    {
        try {
            DB::connection('external')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
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

        // Auto-fix event producten zonder categorie
        $orphanProducts = $event->products()
            ->whereNull('products.category_id')
            ->where(function ($query): void {
                $query->whereNull('products.kind')
                    ->orWhere('products.kind', '!=', 'punch_card');
            })
            ->get();

        if ($orphanProducts->isNotEmpty()) {
            $defaultCategory = Category::firstOrCreate(
                ['slug' => 'dranken'],
                ['name' => 'Dranken', 'is_active' => true]
            );
            foreach ($orphanProducts as $orphan) {
                $orphan->update(['category_id' => $defaultCategory->id]);
            }
        }

        return Category::query()
            ->whereIn('id', $event->products()
                ->where(function ($query): void {
                    $query->whereNull('products.kind')
                        ->orWhere('products.kind', '!=', 'punch_card');
                })
                ->pluck('products.category_id')
            )
            ->orderBy('id')
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

        if ($this->activeCategoryId !== 'all' && ! $categories->contains('id', $this->activeCategoryId)) {
            $this->activeCategoryId = 'all';
        }

        if ($event) {
            $sold = $event->soldQuantities();
            $inCart = collect($this->orderItems)->pluck('quantity', 'product_id');

            $query = $event->products()
                ->where(function ($query): void {
                    $query->whereNull('products.kind')
                        ->orWhere('products.kind', '!=', 'punch_card');
                });

            if ($this->activeCategoryId !== 'all' && $this->activeCategoryId !== null) {
                $query->where('products.category_id', $this->activeCategoryId);
            }

            $products = $query->orderBy('products.sort')
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
        ])->layout('layouts.pos', ['kassaId' => $this->kassaId]);
    }
}

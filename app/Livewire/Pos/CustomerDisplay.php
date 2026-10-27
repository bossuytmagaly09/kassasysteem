<?php

namespace App\Livewire\Pos;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Klantenscherm')]
class CustomerDisplay extends Component
{
    public string $kassaId = '1';

    public string $state = 'idle'; // idle | cart | awaiting_payment | success

    public string $eventName = '';

    public int $vakPrice = 100;

    public int $cardPrice = 2000;

    public array $items = [];

    public int $total = 0;

    public int $vakjes = 0;

    public bool $hasDrankkaart = false;

    public int $cardTotal = 0;

    public int $drinksTotal = 0;

    public int $drinksVakjes = 0;

    public string $cardPaymentMethod = 'cash';

    public bool $awaitingPayment = false;

    public ?string $lastPaymentMethod = null;

    public ?int $lastCashChange = null;

    public ?int $lastDrinksVakjes = null;

    public ?string $scannedPoefName = null;

    public ?string $scannedSharedPoefName = null;

    public ?int $poefTotalSpent = null;

    public ?string $poefInfoError = null;

    public array $eventProducts = [];

    public float $stateTimestamp = 0.0;

    public function mount(mixed $kassa = null): void
    {
        $this->kassaId = (string) ($kassa ?: request('kassa') ?: '1');
        $this->loadFromCache();
    }

    public function syncState(): void
    {
        $this->loadFromCache();
    }

    protected function loadFromCache(): void
    {
        $cacheKey = "pos_customer_display_state_{$this->kassaId}";
        $cached = Cache::get($cacheKey);

        if (! is_array($cached) && $this->kassaId === '1') {
            $cached = Cache::get('pos_customer_display_state');
        }

        if (! is_array($cached)) {
            $event = Event::active();
            $this->state = 'idle';
            $this->eventName = $event?->name ?? 'Kassa';
            $this->vakPrice = $event?->vak_price ?? 100;
            $this->cardPrice = $event?->card_price ?? 2000;
            $this->items = [];
            $this->total = 0;
            $this->vakjes = 0;
            $this->loadEventProducts();

            return;
        }

        $this->loadEventProducts();

        $now = microtime(true);
        $updatedAt = (float) ($cached['updated_at'] ?? $now);

        // If in success state and more than 12 seconds passed, automatically revert to idle
        if (($cached['state'] ?? '') === 'success' && ($now - $updatedAt) > 12.0) {
            $this->state = 'idle';
            $this->items = [];
            $this->total = 0;
            $this->vakjes = 0;
            $this->awaitingPayment = false;
            $this->scannedPoefName = null;
            $this->scannedSharedPoefName = null;

            return;
        }

        // If in poef_info state and more than 7 seconds passed, automatically revert to idle
        if (($cached['state'] ?? '') === 'poef_info' && ($now - $updatedAt) > 7.0) {
            $this->state = 'idle';
            $this->scannedPoefName = null;
            $this->scannedSharedPoefName = null;
            $this->poefTotalSpent = null;
            $this->poefInfoError = null;

            return;
        }

        $this->state = $cached['state'] ?? 'idle';
        $this->eventName = $cached['event_name'] ?? 'Kassa';
        $this->vakPrice = (int) ($cached['vak_price'] ?? 100);
        $this->cardPrice = (int) ($cached['card_price'] ?? 2000);
        $this->items = (array) ($cached['items'] ?? []);
        $this->total = (int) ($cached['total'] ?? 0);
        $this->vakjes = (int) ($cached['vakjes'] ?? 0);
        $this->hasDrankkaart = (bool) ($cached['has_drankkaart'] ?? false);
        $this->cardTotal = (int) ($cached['card_total'] ?? 0);
        $this->drinksTotal = (int) ($cached['drinks_total'] ?? 0);
        $this->drinksVakjes = (int) ($cached['drinks_vakjes'] ?? 0);
        $this->cardPaymentMethod = (string) ($cached['card_payment_method'] ?? 'cash');
        $this->awaitingPayment = (bool) ($cached['awaiting_payment'] ?? false);
        $this->lastPaymentMethod = $cached['last_payment_method'] ?? null;
        $this->lastCashChange = isset($cached['last_cash_change']) ? (int) $cached['last_cash_change'] : null;
        $this->lastDrinksVakjes = isset($cached['last_drinks_vakjes']) ? (int) $cached['last_drinks_vakjes'] : null;
        $this->scannedPoefName = $cached['scanned_poef_name'] ?? null;
        $this->scannedSharedPoefName = $cached['scanned_shared_poef_name'] ?? null;
        $this->poefTotalSpent = isset($cached['poef_total_spent']) ? (int) $cached['poef_total_spent'] : null;
        $this->poefInfoError = $cached['poef_info_error'] ?? null;
        $this->stateTimestamp = $updatedAt;
    }

    protected function loadEventProducts(): void
    {
        $event = Event::active();
        if (! $event) {
            $this->eventProducts = [];

            return;
        }

        $this->eventProducts = $event->products()
            ->with('category')
            ->where('is_active', true)
            ->where('kind', '!=', 'punch_card')
            ->orderBy('category_id')
            ->orderBy('sort')
            ->get()
            ->map(fn ($p): array => [
                'id' => $p->id,
                'name' => preg_replace('/\s*\(.*?\)/', '', $p->name),
                'price' => (int) $p->pivot->price,
                'category' => $p->category?->name ?? 'Dranken',
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.pos.customer-display')->layout('layouts.display');
    }
}

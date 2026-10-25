<?php

namespace App\Livewire\Pos;

use App\Models\Card;
use App\Models\Poef;
use App\Services\BeheerApiService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class KoppelPoef extends Component
{
    public bool $isOpen = false;

    public string $search = '';

    public ?int $selectedPoefId = null;

    public string $cardUid = '';

    public string $cardType = 'badge'; // 'badge', 'bankkaart', 'kaart', 'sleutelhanger', 'andere'

    public string $label = '';

    public ?string $message = null;

    public ?string $errorMessage = null;

    public bool $isSyncing = false;

    public string $activeTab = 'koppel'; // 'koppel' or 'overzicht'

    #[On('open-koppel-poef')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->message = null;
        $this->errorMessage = null;
        $this->syncUsersFromExternal();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['selectedPoefId', 'cardUid', 'label', 'search', 'message', 'errorMessage']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->message = null;
        $this->errorMessage = null;
    }

    public function selectPoef(int $poefId): void
    {
        $this->selectedPoefId = $poefId;
        $this->message = null;
        $this->errorMessage = null;
    }

    #[On('card-scanned')]
    public function handleCardScanned(string $cardUid): void
    {
        if (! $this->isOpen) {
            return;
        }

        $clean = trim($cardUid);
        if ($clean === '') {
            return;
        }

        $this->cardUid = $clean;
        $this->message = "Scancode ontvangen: {$clean}. Kies een lid en type om te koppelen.";
        $this->errorMessage = null;
        $this->dispatch('nfc-beep', success: true);
    }

    public function linkCard(): void
    {
        $this->errorMessage = null;
        $this->message = null;

        $cleanUid = trim($this->cardUid);

        if (! $this->selectedPoefId) {
            $this->errorMessage = 'Selecteer eerst een lid (poef).';

            return;
        }

        if ($cleanUid === '') {
            $this->errorMessage = 'Scan een kaart/badge of typ het serienummer (UID) in.';

            return;
        }

        $poef = Poef::find($this->selectedPoefId);
        if (! $poef) {
            $this->errorMessage = 'Gekozen poef niet gevonden.';

            return;
        }

        $cardHash = BeheerApiService::hashCardUid($cleanUid);

        // Link card in local database
        $card = Card::updateOrCreate(
            ['uid' => $cleanUid],
            [
                'poef_id' => $poef->id,
                'card_hash' => $cardHash,
                'hangaar_user_id' => $poef->hangaar_user_id ?? $poef->external_id,
                'drank_user_id' => $poef->drank_user_id,
                'card_type' => $this->cardType,
                'label' => trim($this->label) !== '' ? trim($this->label) : null,
                'status' => 'active',
            ]
        );

        // Send to Beheer API if token configured
        try {
            if (! empty(config('beheer.api_token')) && ($poef->hangaar_user_id || $poef->external_id)) {
                app(BeheerApiService::class)->linkCard($cardHash, (int) ($poef->hangaar_user_id ?? $poef->external_id), replace: true);
            }
        } catch (\Throwable) {
        }

        // Push directly to external drankdb if available
        $this->pushSingleCardToExternal($card, $poef);

        $this->message = "Succes! {$this->cardTypeLabel($this->cardType)} ({$cleanUid}) is gekoppeld aan {$poef->name}.";
        $this->cardUid = '';
        $this->label = '';
    }

    public function unlinkCard(int $cardId): void
    {
        $card = Card::find($cardId);
        if (! $card) {
            return;
        }

        $uid = $card->uid;
        $hash = $card->card_hash ?? ($uid ? BeheerApiService::hashCardUid($uid) : null);
        $card->delete();

        // Also delete from Beheer if token configured
        try {
            if (! empty(config('beheer.api_token')) && $hash) {
                app(BeheerApiService::class)->unlinkCard($hash);
            }
        } catch (\Throwable) {
        }

        // Also delete from external database
        $this->removeCardFromExternal($uid);

        $this->message = "Koppeling voor UID {$uid} verwijderd.";
    }

    /**
     * Push all local card links to drankdb and pull any new cards/users.
     */
    public function pushAndSync(): void
    {
        $this->isSyncing = true;
        $this->errorMessage = null;
        $this->message = null;

        try {
            $this->ensureExternalCardsTable();
            $this->syncUsersFromExternal();

            $pushed = 0;
            $activeCards = Card::with('poef')->whereNotNull('poef_id')->get();

            foreach ($activeCards as $card) {
                if ($card->poef?->external_id) {
                    DB::connection('external')->table('cards')->updateOrInsert(
                        ['uid' => $card->uid],
                        [
                            'user_id' => $card->poef->external_id,
                            'card_type' => $card->card_type,
                            'label' => $card->label,
                            'updated_at' => now(),
                        ]
                    );
                    $pushed++;
                }
            }

            // Pull any external cards back into local kassa cards
            $pulled = 0;
            $externalCards = DB::connection('external')->table('cards')->get();
            foreach ($externalCards as $extCard) {
                $poef = Poef::where('external_id', $extCard->user_id)->first();
                if ($poef) {
                    Card::updateOrCreate(
                        ['uid' => $extCard->uid],
                        [
                            'poef_id' => $poef->id,
                            'card_type' => $extCard->card_type ?? 'badge',
                            'label' => $extCard->label,
                            'status' => 'active',
                        ]
                    );
                    $pulled++;
                }
            }

            $this->message = "Gesynchroniseerd met beheerwebsite! {$pushed} kaarten gepusht naar drank website.";
        } catch (\Throwable $e) {
            $this->errorMessage = 'Synchronisatie mislukt: '.$e->getMessage();
        } finally {
            $this->isSyncing = false;
        }
    }

    private function syncUsersFromExternal(): void
    {
        try {
            if (! Schema::connection('external')->hasTable('users')) {
                return;
            }

            $users = DB::connection('external')
                ->table('users')
                ->whereNull('deleted_at')
                ->get();

            foreach ($users as $user) {
                Poef::updateOrCreate(
                    ['external_id' => $user->id],
                    [
                        'name' => $user->name,
                        'discount_percentage' => $user->discount_percentage ?? 0,
                    ]
                );
            }
        } catch (\Throwable) {
            // Silently ignore if external db is currently unreachable
        }
    }

    private function ensureExternalCardsTable(): void
    {
        try {
            DB::connection('external')->statement('
                CREATE TABLE IF NOT EXISTS cards (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    uid VARCHAR(191) NOT NULL UNIQUE,
                    card_type VARCHAR(50) NULL DEFAULT "badge",
                    label VARCHAR(100) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX idx_user_id (user_id)
                ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
        } catch (\Throwable) {
            // External table creation error
        }
    }

    private function pushSingleCardToExternal(Card $card, Poef $poef): void
    {
        try {
            $this->ensureExternalCardsTable();

            if ($poef->external_id) {
                DB::connection('external')->table('cards')->updateOrInsert(
                    ['uid' => $card->uid],
                    [
                        'user_id' => $poef->external_id,
                        'card_type' => $card->card_type,
                        'label' => $card->label,
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (\Throwable) {
            // Silently ignore if external db is temporarily unreachable
        }
    }

    private function removeCardFromExternal(string $uid): void
    {
        try {
            if (Schema::connection('external')->hasTable('cards')) {
                DB::connection('external')->table('cards')->where('uid', $uid)->delete();
            }
        } catch (\Throwable) {
            // Ignore external error
        }
    }

    private function cardTypeLabel(string $type): string
    {
        return match ($type) {
            'badge' => 'Badge',
            'bankkaart' => 'Bankkaart',
            'kaart' => 'Drankkaart',
            'sleutelhanger' => 'Sleutelhanger / Tag',
            default => 'Kaart',
        };
    }

    /**
     * @return Collection<int, Poef>
     */
    public function getPoefsProperty(): Collection
    {
        return Poef::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $query->where('name', 'like', '%'.trim($this->search).'%');
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return EloquentCollection<int, Card>
     */
    public function getLinkedCardsProperty(): EloquentCollection
    {
        return Card::with('poef')
            ->whereNotNull('poef_id')
            ->where('status', 'active')
            ->latest()
            ->get();
    }

    public function render(): View
    {
        return view('livewire.pos.koppel-poef');
    }
}

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

    public string $cardType = 'nfc_kaart'; // 'bankkaart', 'badge', 'nfc_kaart'

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

    public function updatedCardUid(?string $value): void
    {
        $this->message = null;
        $this->errorMessage = null;

        if (! empty($value)) {
            $existing = $this->findExistingLinkedCard($value);
            if ($existing) {
                $typeLabel = $this->cardTypeLabel($existing['card_type'] ?? 'kaart');
                $this->errorMessage = "Fout / Kaart Reeds Gekoppeld: Deze {$typeLabel} ({$value}) is reeds gekoppeld aan {$existing['owner_name']}! Overschrijven naar een ander lid is niet toegestaan.";
                $this->dispatch('nfc-beep', success: false);
            }
        }
    }

    #[On('card-scanned')]
    public function handleCardScanned(mixed $cardUid = null): void
    {
        if (! $this->isOpen) {
            return;
        }

        if (is_array($cardUid)) {
            $cardUid = $cardUid['cardUid'] ?? $cardUid['uid'] ?? reset($cardUid) ?? '';
        }

        $clean = trim((string) $cardUid);
        if ($clean === '') {
            return;
        }

        $stripped = (string) preg_replace('/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i', '', $clean);
        $stripped = trim($stripped);
        if (preg_match('/^[0-9A-Fa-f]{2}(?:[\s:\-][0-9A-Fa-f]{2}){2,15}$/', $stripped)) {
            $stripped = strtoupper((string) preg_replace('/[\s:\-]/', '', $stripped));
        }

        $this->cardUid = $stripped !== '' ? $stripped : $clean;

        // Security check direct bij scannen: als kaart AL gekoppeld is, GEEN groen licht maar direct een foutcode!
        $existing = $this->findExistingLinkedCard($this->cardUid);
        if ($existing) {
            $typeLabel = $this->cardTypeLabel($existing['card_type'] ?? 'kaart');
            $this->errorMessage = "Fout / Kaart Reeds Gekoppeld: Deze {$typeLabel} ({$this->cardUid}) is reeds gekoppeld aan {$existing['owner_name']}! Overschrijven naar een ander lid is niet toegestaan.";
            $this->message = null;
            $this->dispatch('nfc-beep', success: false);

            return;
        }

        $this->message = "Scancode ontvangen: {$this->cardUid}. Kies een lid en type om te koppelen.";
        $this->errorMessage = null;
        $this->dispatch('nfc-beep', success: true);
    }

    /**
     * Zoek of een UID/kaart al gekoppeld is in de lokale database of externe drankdb.
     *
     * @return array{id: ?int, poef_id: ?int, owner_name: string, card_type: string, uid: string}|null
     */
    public function findExistingLinkedCard(?string $rawUid): ?array
    {
        if (! $rawUid) {
            return null;
        }

        $raw = trim($rawUid);
        if ($raw === '') {
            return null;
        }

        $clean = (string) preg_replace('/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i', '', $raw);
        $clean = trim($clean);

        $candidates = array_unique(array_filter([
            $raw,
            strtoupper($raw),
            strtolower($raw),
            $clean,
            strtoupper($clean),
            strtolower($clean),
        ]));

        $hexOnly = strtoupper((string) preg_replace('/[\s:\-]/', '', $clean));
        if ($hexOnly !== '' && ctype_xdigit($hexOnly)) {
            $candidates[] = $hexOnly;
            $candidates[] = strtolower($hexOnly);
        }

        $hashes = [];
        foreach (array_unique($candidates) as $cand) {
            $hash = BeheerApiService::hashCardUid($cand);
            if ($hash && ! in_array($hash, $hashes, true)) {
                $hashes[] = $hash;
            }
        }

        // 1. Controleer lokale database (Card gekoppeld aan Poef)
        $card = Card::with('poef')
            ->where(function ($query) use ($candidates, $hashes): void {
                $query->whereIn('uid', $candidates);
                if (! empty($hashes)) {
                    $query->orWhereIn('card_hash', $hashes);
                }
            })
            ->where('status', 'active')
            ->whereNotNull('poef_id')
            ->first();

        if ($card) {
            $ownerName = $card->poef?->poef_name ?: $card->poef?->name ?: 'Onbekend lid';

            return [
                'id' => $card->id,
                'poef_id' => $card->poef_id,
                'owner_name' => $ownerName,
                'card_type' => $card->card_type,
                'uid' => $card->uid,
            ];
        }

        // 2. Controleer externe database indien bereikbaar
        if ($this->isExternalDbAvailable()) {
            try {
                $extCard = DB::connection('external')->table('cards')
                    ->whereIn('uid', $candidates)
                    ->whereNotNull('user_id')
                    ->first();

                if ($extCard && $extCard->user_id) {
                    $localPoef = Poef::where('external_id', $extCard->user_id)
                        ->orWhere('hangaar_user_id', $extCard->user_id)
                        ->first();
                    $ownerName = $localPoef?->name;

                    if (! $ownerName) {
                        $extUser = DB::connection('external')->table('users')
                            ->where('id', $extCard->user_id)
                            ->first();
                        $ownerName = $extUser ? trim(($extUser->first_name ?? '').' '.($extUser->last_name ?? '')) : 'Lid #'.$extCard->user_id;
                    }

                    return [
                        'id' => null,
                        'poef_id' => $localPoef?->id,
                        'owner_name' => $ownerName ?: 'Lid #'.$extCard->user_id,
                        'card_type' => $extCard->card_type ?? 'kaart',
                        'uid' => $extCard->uid,
                    ];
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    public function linkCard(): void
    {
        $this->errorMessage = null;
        $this->message = null;

        $cleanUid = trim($this->cardUid);
        $stripped = (string) preg_replace('/^(?:card\s*uid|uid|card|tag|nfc|id)\s*[:=\-]\s*/i', '', $cleanUid);
        $stripped = trim($stripped);
        if (preg_match('/^[0-9A-Fa-f]{2}(?:[\s:\-][0-9A-Fa-f]{2}){2,15}$/', $stripped)) {
            $stripped = strtoupper((string) preg_replace('/[\s:\-]/', '', $stripped));
        }
        $cleanUid = $stripped !== '' ? $stripped : $cleanUid;

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

        // Security check: kaart mag NIET overschreven worden als deze al aan een lid gekoppeld is!
        $existing = $this->findExistingLinkedCard($cleanUid);
        if ($existing && $existing['poef_id'] !== $poef->id) {
            $typeLabel = $this->cardTypeLabel($existing['card_type'] ?? $this->cardType);
            $this->errorMessage = "Beveiliging: Deze {$typeLabel} ({$cleanUid}) is reeds gekoppeld aan {$existing['owner_name']}. Overschrijven naar een ander lid is niet toegestaan! Verwijder de kaart eerst in het overzicht alvorens opnieuw toe te wijzen.";
            $this->dispatch('nfc-beep', success: false);

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
     * Push all local card links to Beheer / drankdb and pull any new cards/users.
     */
    public function pushAndSync(): void
    {
        $this->isSyncing = true;
        $this->errorMessage = null;
        $this->message = null;

        try {
            $pushed = 0;
            $apiSuccess = false;
            $dbSuccess = false;
            $activeCards = Card::with('poef')->whereNotNull('poef_id')->get();

            // 1. Synchronisatie via officiële Beheer REST API (indien token ingesteld)
            if (! empty(config('beheer.api_token'))) {
                try {
                    $api = app(BeheerApiService::class);
                    foreach ($activeCards as $card) {
                        $hash = $card->card_hash ?: BeheerApiService::hashCardUid($card->uid);
                        $hangaarId = (int) ($card->hangaar_user_id ?? $card->poef?->hangaar_user_id ?? $card->poef?->external_id);
                        if ($hash && $hangaarId) {
                            $api->linkCard($hash, $hangaarId, replace: true);
                            $pushed++;
                        }
                    }
                    $api->syncSnapshot();
                    $apiSuccess = true;
                } catch (\Throwable $apiErr) {
                    // API sync failure logged without blocking
                }
            }

            // 2. Directe synchronisatie met externe drankdb (enkel indien verbinding beschikbaar is)
            if ($this->isExternalDbAvailable()) {
                $this->ensureExternalCardsTable();
                $this->syncUsersFromExternal();

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
                        if (! $apiSuccess) {
                            $pushed++;
                        }
                    }
                }

                // Pull any external cards back into local kassa cards
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
                    }
                }
                $dbSuccess = true;
            }

            if ($apiSuccess && $dbSuccess) {
                $this->message = "Gesynchroniseerd met Beheer API & database! {$pushed} actieve kaarten bijgewerkt.";
            } elseif ($apiSuccess) {
                $this->message = "Gesynchroniseerd met Beheer API! {$pushed} actieve kaarten succesvol doorgestuurd.";
            } elseif ($dbSuccess) {
                $this->message = "Gesynchroniseerd met beheerdatabase! {$pushed} kaarten gepusht naar drank website.";
            } else {
                $totalLocal = $activeCards->count();
                $this->message = "{$totalLocal} koppelingen zijn lokaal actief opgeslagen. Tip: vul in .env de DB_EXTERNAL_PASSWORD of BEHEER_API_TOKEN in om online te synchroniseren.";
            }
        } catch (\Throwable $e) {
            $this->errorMessage = 'Synchronisatie fout: '.$e->getMessage();
        } finally {
            $this->isSyncing = false;
        }
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

    private function syncUsersFromExternal(): void
    {
        try {
            if (! $this->isExternalDbAvailable()) {
                return;
            }

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
            if (! $this->isExternalDbAvailable()) {
                return;
            }

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
            if (! $this->isExternalDbAvailable()) {
                return;
            }

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
            if (! $this->isExternalDbAvailable()) {
                return;
            }

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
            'bankkaart' => 'Bankkaart',
            'badge' => 'Badge',
            'nfc_kaart', 'kaart' => 'NFC Kaart',
            default => ucfirst(str_replace('_', ' ', $type)),
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

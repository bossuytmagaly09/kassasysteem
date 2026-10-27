<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BeheerApiService
{
    protected string $baseUrl;

    protected string $apiToken;

    protected string $cardHashSecret;

    protected string $deviceName;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('beheer.base_url', 'https://beheer.tjeute.be/api/kassa/v1'), '/');
        $this->apiToken = (string) config('beheer.api_token', '');
        $this->cardHashSecret = (string) config('beheer.card_hash_secret', '');
        $this->deviceName = (string) config('beheer.device_name', 'kassa-1');
        $this->timeout = (int) config('beheer.timeout', 10);
    }

    /**
     * Compute card hash according to Beheer API specification:
     * card_hash = lowercase_hex( HMAC_SHA256( key = CARD_HASH_SECRET, message = UID_in_hoofdletters_hex_zonder_scheidingstekens ) )
     */
    public static function hashCardUid(string $uid, ?string $secret = null): string
    {
        $secret ??= (string) config('beheer.card_hash_secret', '');
        $normalized = strtoupper((string) preg_replace('/[^A-Fa-f0-9]/', '', $uid));

        return hash_hmac('sha256', $normalized, $secret);
    }

    /**
     * Ping endpoint to check connectivity and token.
     */
    public function ping(): array
    {
        $response = $this->client()->get($this->baseUrl.'/ping');

        return $this->handleResponse($response);
    }

    /**
     * Fetch categories from Beheer if available.
     */
    public function fetchCategories(): array
    {
        $response = $this->client()->get($this->baseUrl.'/categories');

        return $this->handleResponse($response);
    }

    /**
     * Fetch open events and their items.
     */
    public function fetchOpenEvents(): array
    {
        $response = $this->client()->get($this->baseUrl.'/events/open');

        return $this->handleResponse($response);
    }

    /**
     * Fetch people with poef accounts.
     */
    public function fetchPeople(): array
    {
        $response = $this->client()->get($this->baseUrl.'/people');

        return $this->handleResponse($response);
    }

    /**
     * Fetch active card bindings.
     */
    public function fetchCards(): array
    {
        $response = $this->client()->get($this->baseUrl.'/cards');

        return $this->handleResponse($response);
    }

    /**
     * Link an NFC card hash to a person.
     */
    public function linkCard(string $cardHash, int $hangaarUserId, bool $replace = false): array
    {
        $response = $this->client()->post($this->baseUrl.'/cards', [
            'card_hash' => $cardHash,
            'hangaar_user_id' => $hangaarUserId,
            'replace' => $replace,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Unlink a card by its hash.
     */
    public function unlinkCard(string $cardHash): array
    {
        $response = $this->client()->delete($this->baseUrl.'/cards/'.urlencode($cardHash));

        return $this->handleResponse($response);
    }

    /**
     * Send a batch of sales (1 to 100) to Beheer.
     */
    public function sendSales(array $sales): array
    {
        $response = $this->client()->post($this->baseUrl.'/sales', [
            'sales' => $sales,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Void a previously transmitted sale.
     */
    public function voidSale(string $uuid, string $reason): array
    {
        $response = $this->client()->post($this->baseUrl.'/sales/'.urlencode($uuid).'/void', [
            'reason' => $reason,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Get live summary totals of an event.
     */
    public function fetchEventSummary(int $eventId): array
    {
        $response = $this->client()->get($this->baseUrl.'/events/'.urlencode((string) $eventId).'/summary');

        return $this->handleResponse($response);
    }

    /**
     * Synchronize the complete snapshot from Beheer to the local database.
     */
    public function syncSnapshot(): array
    {
        $logs = [];
        $categoriesMap = [];
        $syncedCategoriesCount = 0;

        // 1. Probeer categorieën op te halen via specifiek Beheer-endpoint indien beschikbaar
        try {
            $categoriesData = $this->fetchCategories();
            if (! empty($categoriesData['data']) && is_array($categoriesData['data'])) {
                foreach ($categoriesData['data'] as $catData) {
                    $cat = $this->syncCategoryRecord($catData);
                    if ($cat) {
                        $syncedCategoriesCount++;
                        if (isset($catData['id'])) {
                            $categoriesMap[(string) $catData['id']] = $cat;
                        }
                        if (isset($catData['slug'])) {
                            $categoriesMap[$catData['slug']] = $cat;
                        }
                        $categoriesMap[strtolower($cat->name)] = $cat;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Optioneel endpoint op Beheer API; foutmelding mag snapshot niet blokkeren
        }

        $eventsData = $this->fetchOpenEvents();

        // 2. Categorieën op snapshot-niveau synchroniseren indien aanwezig
        if (! empty($eventsData['categories']) && is_array($eventsData['categories'])) {
            foreach ($eventsData['categories'] as $catData) {
                $cat = $this->syncCategoryRecord($catData);
                if ($cat) {
                    $syncedCategoriesCount++;
                    if (isset($catData['id'])) {
                        $categoriesMap[(string) $catData['id']] = $cat;
                    }
                    if (isset($catData['slug'])) {
                        $categoriesMap[$catData['slug']] = $cat;
                    }
                    $categoriesMap[strtolower($cat->name)] = $cat;
                }
            }
        }

        if (! empty($eventsData['data'])) {
            foreach ($eventsData['data'] as $eventInfo) {
                // Event-specifieke categorieën synchroniseren indien aanwezig
                if (! empty($eventInfo['categories']) && is_array($eventInfo['categories'])) {
                    foreach ($eventInfo['categories'] as $catData) {
                        $cat = $this->syncCategoryRecord($catData);
                        if ($cat) {
                            $syncedCategoriesCount++;
                            if (isset($catData['id'])) {
                                $categoriesMap[(string) $catData['id']] = $cat;
                            }
                            if (isset($catData['slug'])) {
                                $categoriesMap[$catData['slug']] = $cat;
                            }
                            $categoriesMap[strtolower($cat->name)] = $cat;
                        }
                    }
                }

                $punchValue = isset($eventInfo['punch_value']) ? (float) $eventInfo['punch_value'] : null;
                $vakPriceCents = $punchValue !== null ? (int) round($punchValue * 100) : 100;

                $event = Event::updateOrCreate(
                    ['external_id' => $eventInfo['id']],
                    [
                        'name' => $eventInfo['name'],
                        'date' => $eventInfo['event_date'] ?? now()->toDateString(),
                        'punch_value' => $punchValue,
                        'vak_price' => $vakPriceCents,
                        'is_active' => true,
                    ]
                );

                $eventProductPivots = [];

                if (! empty($eventInfo['items'])) {
                    foreach ($eventInfo['items'] as $item) {
                        $kind = $item['kind'] ?? 'consumption';
                        $priceCents = (int) round(((float) ($item['price'] ?? 0)) * 100);

                        if ($kind === 'punch_card') {
                            $event->update([
                                'card_price' => $priceCents,
                            ]);
                        }

                        $existingProduct = Product::where('external_id', $item['id'])->first();
                        $category = $this->resolveItemCategory($item, $categoriesMap, $existingProduct);

                        $product = Product::updateOrCreate(
                            ['external_id' => $item['id']],
                            [
                                'category_id' => $category->id,
                                'name' => $item['name'],
                                'kind' => $kind,
                                'price' => $priceCents,
                                'punch_cost' => $item['punch_cost'] ?? null,
                                'punches' => $item['punches'] ?? null,
                                'sort' => $item['sort'] ?? 0,
                                'is_active' => true,
                            ]
                        );

                        $eventProductPivots[$product->id] = [
                            'price' => $priceCents,
                        ];
                    }

                    $event->products()->sync($eventProductPivots);
                }

                $logs[] = "Event '{$event->name}' en ".count($eventProductPivots).' items gesynchroniseerd.';
            }

            if ($syncedCategoriesCount > 0) {
                $logs[] = "{$syncedCategoriesCount} categorieën gesynchroniseerd vanuit beheer.";
            }
        } else {
            $logs[] = 'Geen open events gevonden op beheer.';
        }

        // Sync People
        $peopleData = $this->fetchPeople();
        if (! empty($peopleData['data'])) {
            $countPeople = 0;
            foreach ($peopleData['data'] as $person) {
                Poef::updateOrCreate(
                    ['hangaar_user_id' => $person['hangaar_user_id']],
                    [
                        'external_id' => $person['hangaar_user_id'],
                        'drank_user_id' => $person['drank_user_id'] ?? null,
                        'name' => $person['name'],
                        'poef_name' => $person['poef_name'] ?? $person['name'],
                        'has_poef' => (bool) ($person['has_poef'] ?? true),
                    ]
                );
                $countPeople++;
            }
            $logs[] = "{$countPeople} personen/poefs gesynchroniseerd.";
        }

        // Sync Cards
        $cardsData = $this->fetchCards();
        if (! empty($cardsData['data'])) {
            $countCards = 0;
            foreach ($cardsData['data'] as $cardInfo) {
                $poef = Poef::where('hangaar_user_id', $cardInfo['hangaar_user_id'])->first();

                Card::updateOrCreate(
                    ['card_hash' => $cardInfo['card_hash']],
                    [
                        'uid' => $cardInfo['uid'] ?? $cardInfo['card_hash'],
                        'hangaar_user_id' => $cardInfo['hangaar_user_id'],
                        'drank_user_id' => $cardInfo['drank_user_id'] ?? null,
                        'poef_id' => $poef?->id,
                        'label' => $cardInfo['name'] ?? null,
                        'status' => 'active',
                    ]
                );
                $countCards++;
            }
            $logs[] = "{$countCards} actieve kaartkoppelingen gesynchroniseerd.";
        }

        return [
            'success' => true,
            'logs' => $logs,
        ];
    }

    /**
     * Send all pending sales in batches of up to 100 to Beheer.
     */
    public function syncPendingSales(int $batchSize = 100): array
    {
        $pendingOrders = Order::with(['items.product', 'event'])
            ->where('beheer_status', 'pending')
            ->whereNotNull('uuid')
            ->limit($batchSize)
            ->get();

        if ($pendingOrders->isEmpty()) {
            return [
                'count' => 0,
                'message' => 'Geen verkopen in de wachtrij om door te sturen.',
            ];
        }

        $salesPayload = [];
        $orderMap = [];

        foreach ($pendingOrders as $order) {
            $lines = [];
            foreach ($order->items as $item) {
                $externalItemId = $item->product?->external_id;

                // Fallback for punch cards if not linked directly on order item
                if (! $externalItemId) {
                    if ($item->product?->kind === 'punch_card' || in_array($item->product_name, ['Consumptiekaart', 'Drankkaart'])) {
                        $punchCard = $order->event?->products()->where('products.kind', 'punch_card')->first()
                            ?? Product::where('kind', 'punch_card')->whereNotNull('external_id')->first();
                        $externalItemId = $punchCard?->external_id;
                    }
                }

                if ($externalItemId) {
                    $lines[] = [
                        'item_id' => (int) $externalItemId,
                        'quantity' => (int) $item->quantity,
                    ];
                }
            }

            if (empty($lines)) {
                Log::warning("Order {$order->uuid} heeft geen items met external_id en kan niet doorgestuurd worden naar Beheer.");
                $order->update([
                    'beheer_status' => 'rejected',
                    'beheer_error' => 'Geen items met external_id gevonden voor Beheer verkoop.',
                ]);

                continue;
            }

            // Map payment method to Beheer specification:
            // nfc_poef | punch_card | cash | bank_qr
            $beheerMethod = match ($order->payment_method) {
                'scanner' => 'nfc_poef',
                'drankkaart' => 'punch_card',
                'cash' => 'cash',
                'qr' => 'bank_qr',
                default => 'punch_card',
            };

            $saleData = [
                'uuid' => $order->uuid,
                'event_id' => (int) ($order->event?->external_id ?? $order->event_id),
                'payment_method' => $beheerMethod,
                'occurred_at' => $order->created_at->toIso8601String(),
                'device' => $order->device ?: $this->deviceName,
                'lines' => $lines,
            ];

            if ($beheerMethod === 'nfc_poef' && ! empty($order->card_hash)) {
                $saleData['card_hash'] = $order->card_hash;
            }

            $salesPayload[] = $saleData;
            $orderMap[$order->uuid] = $order;
        }

        try {
            $response = $this->sendSales($salesPayload);
            $syncedCount = 0;
            $rejectedCount = 0;
            $duplicateCount = 0;

            if (! empty($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $result) {
                    $uuid = $result['uuid'] ?? null;
                    $status = $result['status'] ?? null;
                    $saleId = $result['sale_id'] ?? null;

                    if ($uuid && isset($orderMap[$uuid])) {
                        /** @var Order $order */
                        $order = $orderMap[$uuid];

                        if ($status === 'created') {
                            $order->update([
                                'beheer_status' => 'synced',
                                'beheer_sale_id' => $saleId,
                                'synced_at' => now(),
                                'beheer_error' => null,
                            ]);
                            $syncedCount++;
                        } elseif ($status === 'duplicate') {
                            $order->update([
                                'beheer_status' => 'duplicate',
                                'beheer_sale_id' => $saleId,
                                'synced_at' => now(),
                                'beheer_error' => null,
                            ]);
                            $duplicateCount++;
                        } elseif ($status === 'rejected') {
                            $errorMessage = ($result['error'] ?? 'rejected').': '.($result['message'] ?? 'Verkoop geweigerd door beheer');
                            $order->update([
                                'beheer_status' => 'rejected',
                                'beheer_error' => $errorMessage,
                            ]);
                            $rejectedCount++;
                        }
                    }
                }
            }

            return [
                'count' => count($salesPayload),
                'synced' => $syncedCount,
                'duplicate' => $duplicateCount,
                'rejected' => $rejectedCount,
                'message' => "{$syncedCount} verkopen doorgestuurd, {$duplicateCount} duplicaten, {$rejectedCount} geweigerd.",
            ];
        } catch (\Throwable $e) {
            Log::warning('Beheer API sync error: '.$e->getMessage());

            return [
                'count' => 0,
                'error' => $e->getMessage(),
                'message' => 'Netwerkfout bij doorsturen naar beheer: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create or update a Category model from category data.
     */
    public function syncCategoryRecord(array|string $catData): ?Category
    {
        if (is_string($catData)) {
            $name = trim($catData);
            if ($name === '') {
                return null;
            }
            $slug = Str::slug($name);

            return Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }

        $name = trim((string) ($catData['name'] ?? $catData['title'] ?? ''));
        if ($name === '') {
            return null;
        }

        $slug = (string) ($catData['slug'] ?? Str::slug($name));
        $color = $catData['color'] ?? null;
        $isActive = (bool) ($catData['is_active'] ?? true);

        return Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'color' => $color,
                'is_active' => $isActive,
            ]
        );
    }

    /**
     * Resolve the proper Category for an item synced from Beheer.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, Category>  $categoriesMap
     */
    public function resolveItemCategory(array $item, array $categoriesMap = [], ?Product $existingProduct = null): Category
    {
        // 1. Array category definitie op het item
        if (! empty($item['category']) && is_array($item['category'])) {
            $cat = $this->syncCategoryRecord($item['category']);
            if ($cat) {
                return $cat;
            }
        }

        // 2. Categorie naam als string in item['category']
        if (! empty($item['category']) && is_string($item['category'])) {
            $cat = $this->syncCategoryRecord($item['category']);
            if ($cat) {
                return $cat;
            }
        }

        // 3. String in item['category_name'] of item['category_title']
        $categoryName = $item['category_name'] ?? $item['category_title'] ?? null;
        if (! empty($categoryName) && is_string($categoryName)) {
            $cat = $this->syncCategoryRecord($categoryName);
            if ($cat) {
                return $cat;
            }
        }

        // 4. Category ID of numerieke lookup
        $categoryId = $item['category_id'] ?? (is_numeric($item['category'] ?? null) ? $item['category'] : null);
        if ($categoryId !== null) {
            $key = (string) $categoryId;
            if (isset($categoriesMap[$key])) {
                return $categoriesMap[$key];
            }
            $existingCat = Category::find($categoryId);
            if ($existingCat) {
                return $existingCat;
            }
        }

        // 5. Category slug
        if (! empty($item['category_slug'])) {
            $slugKey = (string) $item['category_slug'];
            if (isset($categoriesMap[$slugKey])) {
                return $categoriesMap[$slugKey];
            }
            $existingCat = Category::where('slug', $slugKey)->first();
            if ($existingCat) {
                return $existingCat;
            }
        }

        // 6. Behoud bestaande categorie van lokaal product indien aanwezig
        if ($existingProduct && $existingProduct->category_id) {
            $existingCat = Category::find($existingProduct->category_id);
            if ($existingCat) {
                return $existingCat;
            }
        }

        // 7. Heuristische toewijzing o.b.v. productnaam en bestaande categorieën
        $itemNameLower = strtolower($item['name'] ?? '');
        $allCategories = Category::where('is_active', true)->get();

        foreach ($allCategories as $cat) {
            $catNameLower = strtolower($cat->name);
            if ($catNameLower === $itemNameLower || str_contains($itemNameLower, $catNameLower)) {
                return $cat;
            }
        }

        if (preg_match('/(bier|jupiler|stella|duvel|carlsberg|omer|vedett|kriek|leffe|tripel|blonde|blond|pils)/i', $itemNameLower)) {
            return $allCategories->firstWhere('slug', 'bieren')
                ?? Category::firstOrCreate(['slug' => 'bieren'], ['name' => 'Bieren', 'is_active' => true]);
        }

        if (preg_match('/(fris|cola|fanta|sprite|ice[\s-]?tea|fuze|water|chaudfontaine|tonic|spa|red[\s-]?bull|hell|rodeo)/i', $itemNameLower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['fris', 'frisdrank', 'frisdranken']))
                ?? Category::firstOrCreate(['slug' => 'frisdrank'], ['name' => 'Frisdrank', 'is_active' => true]);
        }

        if (preg_match('/(monster)/i', $itemNameLower)) {
            return $allCategories->firstWhere('slug', 'monster')
                ?? Category::firstOrCreate(['slug' => 'monster'], ['name' => 'Monster', 'is_active' => true]);
        }

        if (preg_match('/(eten|chips|pizza|snack|nootjes|pasta|croque|burger|hotdog|noodles)/i', $itemNameLower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['eten', 'snacks']))
                ?? Category::firstOrCreate(['slug' => 'eten'], ['name' => 'Eten', 'is_active' => true]);
        }

        if (preg_match('/(cocktail|gin|rum|vodka|wodka|whisky|whiskey|shot|aperitief|wijn)/i', $itemNameLower)) {
            return $allCategories->first(fn ($c) => in_array($c->slug, ['cocktails', 'sterke-dranken', 'wijn-en-bubbels']))
                ?? Category::firstOrCreate(['slug' => 'cocktails'], ['name' => 'Cocktails', 'is_active' => true]);
        }

        // 8. Standaard fallback
        return Category::firstOrCreate(
            ['slug' => 'dranken'],
            ['name' => 'Dranken', 'is_active' => true]
        );
    }

    protected function client()
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->withToken($this->apiToken);
    }

    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $body = $response->json() ?? [];
        $message = $body['message'] ?? $body['error'] ?? 'API aanvraag mislukt met HTTP '.$response->status();

        throw new \RuntimeException($message, $response->status());
    }
}

<?php

namespace Tests\Feature\Beheer;

use App\Models\Category;
use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use App\Models\Product;
use App\Services\BeheerApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeheerApiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_hash_uid_matches_partner_specification(): void
    {
        // Example from kasa-api.md:
        // UID 04:A2:3B:19 -> 04A23B19
        // card_hash = lowercase_hex( HMAC_SHA256( key = CARD_HASH_SECRET, message = UID_in_hoofdletters_hex_zonder_scheidingstekens ) )
        $secret = 'test-secret-key-123';
        $uidWithColons = '04:a2:3b:19';

        $expectedHash = hash_hmac('sha256', '04A23B19', $secret);

        $computedHash = BeheerApiService::hashCardUid($uidWithColons, $secret);

        $this->assertSame($expectedHash, $computedHash);
        $this->assertSame(64, strlen($computedHash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $computedHash);
    }

    public function test_sync_snapshot_populates_events_products_people_and_cards(): void
    {
        config(['beheer.api_token' => 'dummy-token']);

        Http::fake([
            '*/events/open' => Http::response([
                'data' => [
                    [
                        'id' => 12,
                        'name' => 'Verjaardagsfeest',
                        'event_date' => '2026-11-07',
                        'punch_value' => 1.5,
                        'items' => [
                            ['id' => 40, 'kind' => 'punch_card', 'name' => 'Consumptiekaart', 'price' => 15.0, 'punch_cost' => null, 'punches' => 10, 'sort' => -1],
                            ['id' => 41, 'kind' => 'consumption', 'name' => 'Cola', 'price' => 1.5, 'punch_cost' => 1, 'punches' => null, 'sort' => 0],
                            ['id' => 42, 'kind' => 'consumption', 'name' => 'Rum-cola', 'price' => 3.0, 'punch_cost' => 2, 'punches' => null, 'sort' => 1],
                        ],
                    ],
                ],
            ]),
            '*/people' => Http::response([
                'data' => [
                    ['hangaar_user_id' => 7, 'name' => 'Jonathan', 'drank_user_id' => 3, 'poef_name' => 'Jonathan / Febe', 'has_poef' => true],
                ],
            ]),
            '*/cards' => Http::response([
                'data' => [
                    ['card_hash' => '9f2c111122223333444455556666777788889999aaaabbbbccccddddeeeeffff', 'hangaar_user_id' => 7, 'drank_user_id' => 3, 'name' => 'Jonathan'],
                ],
            ]),
        ]);

        $service = app(BeheerApiService::class);
        $result = $service->syncSnapshot();

        $this->assertTrue($result['success']);

        // Assert event created
        $this->assertDatabaseHas('events', [
            'external_id' => 12,
            'name' => 'Verjaardagsfeest',
            'vak_price' => 150, // 1.5 euro = 150 cents
            'card_price' => 1500, // 15 euro = 1500 cents
        ]);

        // Assert items created
        $this->assertDatabaseHas('products', [
            'external_id' => 41,
            'name' => 'Cola',
            'price' => 150,
            'kind' => 'consumption',
            'punch_cost' => 1,
        ]);

        // Assert poef / person created
        $this->assertDatabaseHas('poefs', [
            'hangaar_user_id' => 7,
            'name' => 'Jonathan',
            'poef_name' => 'Jonathan / Febe',
        ]);

        // Assert card created with hash
        $this->assertDatabaseHas('cards', [
            'card_hash' => '9f2c111122223333444455556666777788889999aaaabbbbccccddddeeeeffff',
            'hangaar_user_id' => 7,
        ]);
    }

    public function test_sync_pending_sales_sends_batch_and_updates_status(): void
    {
        config(['beheer.api_token' => 'dummy-token']);

        $category = Category::create(['name' => 'Dranken', 'slug' => 'dranken']);
        $product = Product::create([
            'external_id' => 41,
            'category_id' => $category->id,
            'name' => 'Cola',
            'price' => 150,
        ]);

        $event = Event::factory()->active()->create(['external_id' => 12]);
        $order = Order::create([
            'uuid' => '6f1d3c1e-5a0b-4a39-9a0b-2f6a0f1a9c11',
            'event_id' => $event->id,
            'total_amount' => 300,
            'payment_method' => 'scanner',
            'card_hash' => 'dummy_hash_64_characters_long_abcdef1234567890abcdef1234567890abcd',
            'beheer_status' => 'pending',
            'device' => 'kassa-1',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Cola',
            'product_price' => 150,
            'quantity' => 2,
        ]);

        Http::fake([
            '*/sales' => Http::response([
                'data' => [
                    [
                        'uuid' => '6f1d3c1e-5a0b-4a39-9a0b-2f6a0f1a9c11',
                        'status' => 'created',
                        'sale_id' => 311,
                    ],
                ],
            ]),
        ]);

        $service = app(BeheerApiService::class);
        $result = $service->syncPendingSales();

        $this->assertSame(1, $result['synced']);

        $order->refresh();
        $this->assertSame('synced', $order->beheer_status);
        $this->assertSame(311, $order->beheer_sale_id);
        $this->assertNotNull($order->synced_at);
    }

    public function test_sync_pending_sales_transmits_punch_card_sale_with_item_id_and_cash_method(): void
    {
        config(['beheer.api_token' => 'dummy-token']);

        $category = Category::create(['name' => 'Dranken', 'slug' => 'dranken']);
        $punchCard = Product::create([
            'external_id' => 99,
            'category_id' => $category->id,
            'name' => 'Consumptiekaart',
            'kind' => 'punch_card',
            'price' => 2000,
        ]);

        $event = Event::factory()->active()->create(['external_id' => 12]);
        $order = Order::create([
            'uuid' => 'a1b2c3d4-e5f6-4a39-9a0b-2f6a0f1a9c22',
            'event_id' => $event->id,
            'total_amount' => 2000,
            'payment_method' => 'cash',
            'beheer_status' => 'pending',
            'device' => 'kassa-1',
        ]);

        $order->items()->create([
            'product_id' => $punchCard->id,
            'product_name' => 'Consumptiekaart',
            'product_price' => 2000,
            'quantity' => 1,
        ]);

        Http::fake([
            '*/sales' => function ($request) {
                $payload = $request->data()['sales'][0] ?? [];
                $this->assertSame('a1b2c3d4-e5f6-4a39-9a0b-2f6a0f1a9c22', $payload['uuid']);
                $this->assertSame('cash', $payload['payment_method']);
                $this->assertSame([['item_id' => 99, 'quantity' => 1]], $payload['lines']);

                return Http::response([
                    'data' => [
                        [
                            'uuid' => 'a1b2c3d4-e5f6-4a39-9a0b-2f6a0f1a9c22',
                            'status' => 'created',
                            'sale_id' => 777,
                        ],
                    ],
                ]);
            },
        ]);

        $service = app(BeheerApiService::class);
        $result = $service->syncPendingSales();

        $this->assertSame(1, $result['synced']);
        $order->refresh();
        $this->assertSame('synced', $order->beheer_status);
        $this->assertSame(777, $order->beheer_sale_id);
    }

    public function test_sync_snapshot_syncs_categories_from_items(): void
    {
        config(['beheer.api_token' => 'dummy-token']);

        Http::fake([
            '*/categories' => Http::response(['error' => 'not found'], 404),
            '*/events/open' => Http::response([
                'data' => [
                    [
                        'id' => 99,
                        'name' => 'Zomerfuif',
                        'event_date' => '2026-07-15',
                        'punch_value' => 2.0,
                        'items' => [
                            [
                                'id' => 101,
                                'kind' => 'consumption',
                                'name' => 'Jupiler',
                                'price' => 2.0,
                                'category' => 'Bieren',
                            ],
                            [
                                'id' => 102,
                                'kind' => 'consumption',
                                'name' => 'Mojito',
                                'price' => 6.0,
                                'category' => [
                                    'name' => 'Cocktails',
                                    'slug' => 'cocktails',
                                    'color' => '#10b981',
                                ],
                            ],
                            [
                                'id' => 103,
                                'kind' => 'consumption',
                                'name' => 'Chips Paprika',
                                'price' => 1.5,
                                'category_name' => 'Snacks',
                            ],
                        ],
                    ],
                ],
            ]),
            '*/people' => Http::response(['data' => []]),
            '*/cards' => Http::response(['data' => []]),
        ]);

        $service = app(BeheerApiService::class);
        $result = $service->syncSnapshot();

        $this->assertTrue($result['success']);

        // Assert categories are created
        $bierenCat = Category::where('slug', 'bieren')->first();
        $cocktailsCat = Category::where('slug', 'cocktails')->first();
        $snacksCat = Category::where('slug', 'snacks')->first();

        $this->assertNotNull($bierenCat);
        $this->assertSame('Bieren', $bierenCat->name);

        $this->assertNotNull($cocktailsCat);
        $this->assertSame('Cocktails', $cocktailsCat->name);
        $this->assertSame('#10b981', $cocktailsCat->color);

        $this->assertNotNull($snacksCat);
        $this->assertSame('Snacks', $snacksCat->name);

        // Assert items are assigned to their respective categories
        $jupiler = Product::where('external_id', 101)->first();
        $mojito = Product::where('external_id', 102)->first();
        $chips = Product::where('external_id', 103)->first();

        $this->assertSame($bierenCat->id, $jupiler->category_id);
        $this->assertSame($cocktailsCat->id, $mojito->category_id);
        $this->assertSame($snacksCat->id, $chips->category_id);
    }

    public function test_sync_snapshot_syncs_categories_from_dedicated_endpoint(): void
    {
        config(['beheer.api_token' => 'dummy-token']);

        Http::fake([
            '*/categories' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Speciaalbieren', 'slug' => 'speciaalbieren'],
                    ['id' => 2, 'name' => 'Sterke Dranken', 'slug' => 'sterke-dranken'],
                ],
            ]),
            '*/events/open' => Http::response([
                'data' => [
                    [
                        'id' => 88,
                        'name' => 'Festival',
                        'event_date' => '2026-08-20',
                        'items' => [
                            [
                                'id' => 201,
                                'kind' => 'consumption',
                                'name' => 'Duvel 666',
                                'price' => 4.0,
                                'category_id' => 1,
                            ],
                            [
                                'id' => 202,
                                'kind' => 'consumption',
                                'name' => 'Gin Tonic',
                                'price' => 7.0,
                                'category_id' => 2,
                            ],
                        ],
                    ],
                ],
            ]),
            '*/people' => Http::response(['data' => []]),
            '*/cards' => Http::response(['data' => []]),
        ]);

        $service = app(BeheerApiService::class);
        $result = $service->syncSnapshot();

        $this->assertTrue($result['success']);

        $speciaalCat = Category::where('slug', 'speciaalbieren')->first();
        $sterkCat = Category::where('slug', 'sterke-dranken')->first();

        $this->assertNotNull($speciaalCat);
        $this->assertNotNull($sterkCat);

        $duvel = Product::where('external_id', 201)->first();
        $gin = Product::where('external_id', 202)->first();

        $this->assertSame($speciaalCat->id, $duvel->category_id);
        $this->assertSame($sterkCat->id, $gin->category_id);
    }
}

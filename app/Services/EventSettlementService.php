<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Models\Poef;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EventSettlementService
{
    /**
     * Check if the external drankdb connection is active and reachable.
     */
    public function isExternalDbAvailable(): bool
    {
        try {
            DB::connection('external')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Close an event (local or imported) and settle poef balances to Beheer.
     *
     * @return array{success: bool, orders_count: int, poefs_count: int, total_cents: int, message: string}
     */
    public function closeAndSettle(Event $event): array
    {
        // 1. Mark event as inactive and closed
        $event->update([
            'is_active' => false,
            'is_closed' => true,
            'closed_at' => $event->closed_at ?: now(),
        ]);

        // 2. Retrieve all orders with a poef for this event that haven't been settled to Beheer yet
        $unsettledOrders = Order::with(['items.product', 'poef'])
            ->where('event_id', $event->id)
            ->whereNotNull('poef_id')
            ->whereNull('settled_at')
            ->get();

        $totalCents = (int) $unsettledOrders->sum('total_amount');
        $ordersCount = $unsettledOrders->count();
        $groupedByPoef = $unsettledOrders->groupBy('poef_id');
        $poefsCount = $groupedByPoef->count();

        $externalDbUpdated = false;

        // 3. Update balances in external database if reachable
        if ($this->isExternalDbAvailable() && $poefsCount > 0) {
            try {
                if (Schema::connection('external')->hasTable('users')) {
                    DB::connection('external')->transaction(function () use ($groupedByPoef, $event): void {
                        foreach ($groupedByPoef as $poefId => $orders) {
                            /** @var Poef|null $poef */
                            $poef = Poef::find($poefId);
                            $externalUserId = $poef?->external_id ?? $poef?->hangaar_user_id;

                            if (! $externalUserId) {
                                continue;
                            }

                            $user = DB::connection('external')->table('users')->where('id', $externalUserId)->first();
                            if (! $user) {
                                continue;
                            }

                            $poefTotalCents = (int) $orders->sum('total_amount');
                            $amountEuro = round($poefTotalCents / 100, 2);
                            $newBalance = round(((float) ($user->balance ?? 0)) + $amountEuro, 2);

                            // Update user balance
                            DB::connection('external')->table('users')
                                ->where('id', $user->id)
                                ->update([
                                    'balance' => $newBalance,
                                    'lifetime_spent' => round(((float) ($user->lifetime_spent ?? 0)) + $amountEuro, 2),
                                    'updated_at' => now(),
                                ]);

                            // Record balance adjustment
                            if (Schema::connection('external')->hasTable('balance_adjustments')) {
                                DB::connection('external')->table('balance_adjustments')->insert([
                                    'user_id' => $user->id,
                                    'amount' => $amountEuro,
                                    'note' => "Afsluiting event: {$event->name} ({$orders->count()} bestellingen)",
                                    'resulting_balance' => $newBalance,
                                    'ts' => now(),
                                ]);
                            }

                            // Record item transactions if table exists
                            if (Schema::connection('external')->hasTable('transactions') && Schema::connection('external')->hasTable('products')) {
                                foreach ($orders as $order) {
                                    foreach ($order->items as $item) {
                                        $extProd = DB::connection('external')->table('products')
                                            ->where('name', $item->product_name)
                                            ->first();

                                        $productId = $extProd ? $extProd->id : 1;

                                        DB::connection('external')->table('transactions')->insert([
                                            'user_id' => $user->id,
                                            'product_id' => $productId,
                                            'price' => round($item->product_price / 100, 2),
                                            'amount' => $item->quantity,
                                            'ts' => $order->created_at ?: now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    });

                    $externalDbUpdated = true;
                }
            } catch (\Throwable $e) {
                Log::error('Fout bij verwerken poefs naar externe database bij afsluiten event: '.$e->getMessage());
            }
        }

        // 4. Mark all unsettled orders as settled
        if ($ordersCount > 0) {
            Order::where('event_id', $event->id)
                ->whereNotNull('poef_id')
                ->whereNull('settled_at')
                ->update(['settled_at' => now()]);
        }

        // 5. Trigger Beheer REST API sync if configured
        $apiSynced = false;
        if (! empty(config('beheer.api_token'))) {
            try {
                app(BeheerApiService::class)->syncPendingSales();
                $apiSynced = true;
            } catch (\Throwable $apiErr) {
                Log::warning('Beheer API sync error bij afsluiten event: '.$apiErr->getMessage());
            }
        }

        // 6. Update event settlement status
        $event->update([
            'poef_settled' => true,
            'poef_settled_at' => now(),
        ]);

        $formattedTotal = number_format($totalCents / 100, 2, ',', '.');
        $msg = "Event '{$event->name}' is succesvol afgesloten.";
        if ($poefsCount > 0) {
            $msg .= " In totaal is € {$formattedTotal} aan poefbedragen over {$poefsCount} leden geregistreerd";
            if ($externalDbUpdated) {
                $msg .= ' en bijgeschreven op hun poef in de beheerdatabase.';
            } else {
                $msg .= ' (lokaal bewaard; push naar beheer zodra verbinding online is).';
            }
        } else {
            $msg .= ' Er waren geen openstaande poefbetalingen.';
        }

        return [
            'success' => true,
            'orders_count' => $ordersCount,
            'poefs_count' => $poefsCount,
            'total_cents' => $totalCents,
            'message' => $msg,
        ];
    }

    /**
     * Push all pending sales (across all events or for a specific event) to Beheer.
     *
     * @return array{count: int, message: string}
     */
    public function pushSalesToBeheer(?Event $event = null): array
    {
        $apiResult = ['count' => 0, 'message' => 'Geen API token geconfigureerd'];

        if (! empty(config('beheer.api_token'))) {
            try {
                $apiResult = app(BeheerApiService::class)->syncPendingSales();
            } catch (\Throwable $e) {
                $apiResult = ['count' => 0, 'message' => 'Fout bij API push: '.$e->getMessage()];
            }
        }

        // Also check if any closed event poefs need external DB sync
        $eventsToSync = $event ? collect([$event]) : Event::where('is_closed', true)->get();
        $dbPushed = 0;

        if ($this->isExternalDbAvailable()) {
            foreach ($eventsToSync as $evt) {
                $unsettledOrders = Order::with(['items.product', 'poef'])
                    ->where('event_id', $evt->id)
                    ->whereNotNull('poef_id')
                    ->whereNull('settled_at')
                    ->get();

                if ($unsettledOrders->isNotEmpty()) {
                    $res = $this->closeAndSettle($evt);
                    $dbPushed += $res['orders_count'];
                }
            }
        }

        $totalPushed = ($apiResult['count'] ?? 0) + $dbPushed;
        $message = $apiResult['message'] ?? 'Verkopen doorgestuurd naar beheer.';
        if ($dbPushed > 0) {
            $message .= " Tevens {$dbPushed} poefbestellingen bijgewerkt in de beheerdatabase.";
        }

        return [
            'count' => $totalPushed,
            'message' => $message,
        ];
    }

    /**
     * Reopen a closed event.
     */
    public function reopen(Event $event): void
    {
        $event->update([
            'is_closed' => false,
            'closed_at' => null,
            'is_active' => true,
        ]);
        $event->activate();
    }
}

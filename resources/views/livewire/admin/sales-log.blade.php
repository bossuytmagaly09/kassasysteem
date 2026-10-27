<div>
    @section('title', 'Verkopen & Bestellingen Log')

    @if($syncMessage)
        <div class="mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 font-semibold text-sm flex items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ $syncMessage }}</span>
            </div>
            <button type="button" wire:click="$set('syncMessage', null)" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>
    @endif

    @if($syncError)
        <div class="mb-6 p-4 rounded-2xl bg-red-500/15 border border-red-500/40 text-red-300 font-semibold text-sm flex items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $syncError }}</span>
            </div>
            <button type="button" wire:click="$set('syncError', null)" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>
    @endif

    <!-- Top Filter Bar -->
    <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-6 shadow-xl mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-black text-slate-100 tracking-tight flex items-center gap-3">
                    <span class="p-2 rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/40">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </span>
                    Verkoop- & Bestellingenoverzicht
                </h2>
                <p class="text-slate-400 text-sm mt-1">
                    Volledig realtime overzicht van alle verkochte dranken, drankkaarten en bestellingen per event en betaalmethode.
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Event Selector -->
                <div>
                    <label for="filter-event" class="sr-only">Filter op event</label>
                    <select id="filter-event" wire:model.live="selectedEventId" class="bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl px-4 py-2.5 font-bold focus:border-indigo-500 focus:outline-none">
                        <option value="">Alle Events (Totaal)</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->name }} {{ $event->is_active ? '★ (Actief)' : ($event->is_closed ? '🔒 (Afgesloten)' : '○ (Open)') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div>
                    <label for="filter-payment" class="sr-only">Betaalmethode</label>
                    <select id="filter-payment" wire:model.live="paymentMethodFilter" class="bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl px-4 py-2.5 font-bold focus:border-indigo-500 focus:outline-none">
                        <option value="">Alle Betaalmethodes</option>
                        <option value="cash">Contant (Cash)</option>
                        <option value="qr">QR Code (Payconiq)</option>
                        <option value="drankkaart">Drankkaart (Vakjes)</option>
                        <option value="scanner">NFC Scanner (Poef)</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.250ms="search"
                           placeholder="Zoek product, klant of ID…"
                           class="bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:border-indigo-500 focus:outline-none w-56">
                </div>
            </div>
        </div>

        @if($selectedEvent)
            <!-- Event Status & Actions Bar -->
            <div class="mt-4 pt-4 border-t border-slate-700/60 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm text-slate-300 font-bold">Event: <span class="text-white font-black">{{ $selectedEvent->name }}</span></span>
                    @if($selectedEvent->is_closed)
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/40 flex items-center gap-1.5">
                            🔒 Afgesloten op {{ $selectedEvent->closed_at?->format('d/m/Y H:i') ?? 'ongekend' }}
                        </span>
                        @if($selectedEvent->poef_settled)
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                ✓ Poefs Verwerkt in Beheer
                            </span>
                        @endif
                    @elseif($selectedEvent->is_active)
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Nu Actief op Kassa
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-700 text-slate-300 border border-slate-600">
                            Inactief / Open
                        </span>
                    @endif

                    @if($unsettledPoefCount > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                            Openstaande Poefs: € {{ number_format($unsettledPoefTotal / 100, 2, ',', '.') }} ({{ $unsettledPoefCount }}x)
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <!-- Push to Beheer button -->
                    <button type="button"
                            wire:click="pushPendingToBeheer"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-emerald-200" wire:loading.class="animate-spin" wire:target="pushPendingToBeheer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Pushen naar Beheerssite</span>
                        @if($pendingBeheerCount > 0)
                            <span class="px-2 py-0.5 text-[10px] bg-emerald-900 text-emerald-200 rounded-full font-black border border-emerald-400/40">{{ $pendingBeheerCount }}</span>
                        @endif
                    </button>

                    @if(! $selectedEvent->is_closed)
                        <button type="button"
                                wire:click="closeEvent({{ $selectedEvent->id }})"
                                wire:confirm="Weet je zeker dat je event '{{ $selectedEvent->name }}' wilt afsluiten? Alle openstaande poefbedragen van de leden worden hierbij definitief doorgestuurd en bijgeschreven op hun poef in de beheerdatabase."
                                class="px-4 py-2 bg-gradient-to-r from-amber-600 to-rose-600 hover:from-amber-500 hover:to-rose-500 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Event Afsluiten & Poefs</span>
                        </button>
                    @else
                        <button type="button"
                                wire:click="reopenEvent({{ $selectedEvent->id }})"
                                class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-600 font-bold text-xs rounded-xl transition-all">
                            Heropen Event
                        </button>
                    @endif
                </div>
            </div>
        @else
            <!-- Global Action Bar (When All Events selected) -->
            <div class="mt-4 pt-4 border-t border-slate-700/60 flex items-center justify-between">
                <span class="text-sm text-slate-400">Weergave over alle evenementen samen</span>
                <button type="button"
                        wire:click="pushPendingToBeheer"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4 text-emerald-200" wire:loading.class="animate-spin" wire:target="pushPendingToBeheer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Pushen naar Beheerssite</span>
                    @if($pendingBeheerCount > 0)
                        <span class="px-2 py-0.5 text-[10px] bg-emerald-900 text-emerald-200 rounded-full font-black border border-emerald-400/40">{{ $pendingBeheerCount }}</span>
                    @endif
                </button>
            </div>
        @endif

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mt-6">
            <!-- Total Revenue -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-emerald-500/30 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-emerald-400 font-bold block">Totale Omzet</span>
                <div class="text-2xl font-black text-white mt-1">€ {{ number_format($totalRevenue / 100, 2, ',', '.') }}</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $ordersCount }} bestellingen</span>
            </div>

            <!-- Drankkaarten Sold -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-fuchsia-500/30 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-fuchsia-400 font-bold block">Consumptiekaarten Verkocht</span>
                <div class="text-2xl font-black text-fuchsia-300 mt-1">{{ $drankkaartenSold }} stuks</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">€ {{ number_format($drankkaartenRevenue / 100, 2, ',', '.') }} omzet</span>
            </div>

            <!-- Cash Revenue -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-slate-700/50 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-emerald-400 font-bold block">💵 Contant (Cash)</span>
                <div class="text-2xl font-black text-emerald-300 mt-1">€ {{ number_format($cashRevenue / 100, 2, ',', '.') }}</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $cashCount }} transacties</span>
            </div>

            <!-- QR Code Revenue -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-cyan-500/30 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-cyan-400 font-bold block">📱 QR / Payconiq</span>
                <div class="text-2xl font-black text-cyan-300 mt-1">€ {{ number_format($qrRevenue / 100, 2, ',', '.') }}</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $qrCount }} transacties</span>
            </div>

            <!-- NFC Scanner / Poef -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-indigo-500/30 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-indigo-400 font-bold block">💳 Scanner / Poef</span>
                <div class="text-2xl font-black text-indigo-300 mt-1">€ {{ number_format($scannerRevenue / 100, 2, ',', '.') }}</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $scannerCount }} scans</span>
            </div>

            <!-- Drankkaart Punches -->
            <div class="bg-slate-900/70 rounded-2xl p-4 border border-purple-500/30 shadow-lg">
                <span class="text-xs uppercase tracking-wider text-purple-400 font-bold block">🎟️ Vakjes Doorgestreept</span>
                <div class="text-2xl font-black text-purple-300 mt-1">{{ $drankkaartVakjes }} vakjes</div>
                <span class="text-[11px] text-slate-400 mt-0.5 block">{{ $drankkaartCount }} consumptiekaart afrekeningen</span>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="flex items-center gap-3 mb-6">
        <button type="button"
                wire:click="setTab('items')"
                class="px-6 py-3 rounded-2xl font-bold text-base transition-all flex items-center gap-2 border {{ $activeTab === 'items' ? 'bg-indigo-600 text-white border-indigo-400 shadow-lg shadow-indigo-600/30' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:bg-slate-700' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <span>Verkoopoverzicht per Product & Consumptiekaart</span>
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'items' ? 'bg-indigo-900/80 text-indigo-200' : 'bg-slate-700 text-slate-400' }}">
                {{ count($productSales) }}
            </span>
        </button>

        <button type="button"
                wire:click="setTab('orders')"
                class="px-6 py-3 rounded-2xl font-bold text-base transition-all flex items-center gap-2 border {{ $activeTab === 'orders' ? 'bg-indigo-600 text-white border-indigo-400 shadow-lg shadow-indigo-600/30' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:bg-slate-700' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            <span>Chronologische Bestellingen Log</span>
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'orders' ? 'bg-indigo-900/80 text-indigo-200' : 'bg-slate-700 text-slate-400' }}">
                {{ $orders->total() }}
            </span>
        </button>

        <button type="button"
                wire:click="setTab('cashiers')"
                class="px-6 py-3 rounded-2xl font-bold text-base transition-all flex items-center gap-2 border {{ $activeTab === 'cashiers' ? 'bg-indigo-600 text-white border-indigo-400 shadow-lg shadow-indigo-600/30' : 'bg-slate-800/60 text-slate-300 border-slate-700/60 hover:bg-slate-700' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Kassier Logins & Sessies</span>
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'cashiers' ? 'bg-indigo-900/80 text-indigo-200' : 'bg-slate-700 text-slate-400' }}">
                {{ count($cashierSessions) }}
            </span>
        </button>
    </div>

    <!-- TAB 1: Product Sales Overview -->
    @if($activeTab === 'items')
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-100">Overzicht van Alles wat Verkocht is</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Totaal aantal verkocht en gegenereerde omzet per artikel (inclusief consumptiekaarten).</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-4">Product / Artikel</th>
                            <th class="px-6 py-4">Categorie</th>
                            <th class="px-6 py-4 text-right">Prijs per stuk</th>
                            <th class="px-6 py-4 text-right">Aantal Verkocht</th>
                            <th class="px-6 py-4 text-right">Totale Opbrengst</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40">
                        @forelse($productSales as $item)
                            <tr class="hover:bg-slate-800/60 transition-colors {{ in_array($item->product_name, ['Consumptiekaart', 'Drankkaart']) ? 'bg-fuchsia-950/20' : '' }}">
                                <td class="px-6 py-4 font-bold text-white flex items-center gap-3">
                                    @if(in_array($item->product_name, ['Consumptiekaart', 'Drankkaart']))
                                        <span class="w-8 h-8 rounded-xl bg-fuchsia-500/20 border border-fuchsia-500/40 flex items-center justify-center text-fuchsia-400 text-base">
                                            🎟️
                                        </span>
                                    @else
                                        <span class="w-8 h-8 rounded-xl bg-slate-700/60 border border-slate-600/60 flex items-center justify-center text-slate-300 text-base">
                                            📦
                                        </span>
                                    @endif
                                    <div>
                                        <span class="text-base text-slate-100">{{ $item->product_name === 'Drankkaart' ? 'Consumptiekaart' : $item->product_name }}</span>
                                        @if(in_array($item->product_name, ['Consumptiekaart', 'Drankkaart']))
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/40">Consumptiekaart</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-700/40 border border-slate-600/50 text-slate-300">
                                        {{ $item->category_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-200">
                                    € {{ number_format($item->product_price / 100, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-xl font-black text-indigo-300">{{ $item->total_quantity }}x</span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-lg text-emerald-400">
                                    € {{ number_format($item->total_amount / 100, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    Geen verkopen gevonden voor de geselecteerde filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 2: Orders Chronological Log -->
    @if($activeTab === 'orders')
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-700/50 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-slate-100">Gedetailleerde Bestellingen Log</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Elke bestelling op datum, tijd, kassa, kassier, betaalwijze, gekoppelde poef en inhoud.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="pushPendingToBeheer"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4 text-emerald-200" wire:loading.class="animate-spin" wire:target="pushPendingToBeheer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Pushen naar Beheerssite</span>
                        @if($pendingBeheerCount > 0)
                            <span class="px-2 py-0.5 text-[10px] bg-emerald-900 text-emerald-200 rounded-full font-black border border-emerald-400/40">{{ $pendingBeheerCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-4">Tijdstip</th>
                            <th class="px-6 py-4">Kassa / Kassier</th>
                            <th class="px-6 py-4">Event</th>
                            <th class="px-6 py-4">Betaalmethode</th>
                            <th class="px-6 py-4">Klant / Poef</th>
                            <th class="px-6 py-4">Inhoud Bestelling</th>
                            <th class="px-6 py-4 text-right">Vakjes</th>
                            <th class="px-6 py-4 text-right">Totaalbedrag</th>
                            <th class="px-6 py-4 text-center">Beheer Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-800/60 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    <div class="font-bold text-slate-200">{{ $order->created_at?->format('d/m/Y') }}</div>
                                    <div>{{ $order->created_at?->format('H:i:s') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs">
                                    <div class="font-bold text-slate-200 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                        {{ ucwords(str_replace('-', ' ', $order->device ?: 'kassa 1')) }}
                                    </div>
                                    <div class="text-slate-400 font-medium">{{ $order->cashier_name ?: 'Onbekend' }}</div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-300 whitespace-nowrap">
                                    {{ $order->event?->name ?? 'Onbekend' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($order->payment_method === 'cash')
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1.5 w-max">
                                            💵 Contant (Cash)
                                        </span>
                                    @elseif($order->payment_method === 'qr')
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 flex items-center gap-1.5 w-max">
                                            📱 QR Code
                                        </span>
                                    @elseif($order->payment_method === 'drankkaart')
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/40 flex items-center gap-1.5 w-max">
                                            🎟️ Consumptiekaart
                                        </span>
                                    @elseif($order->payment_method === 'scanner')
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 flex items-center gap-1.5 w-max">
                                            💳 Scanner (NFC)
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-700 text-slate-300">
                                            {{ $order->payment_method }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-200">
                                    @if($order->poef)
                                        <span class="text-indigo-300 font-bold flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            {{ $order->poef->name ?? $order->poef->poef_name }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 italic">Anoniem</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        @foreach($order->items as $item)
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $item->product_name === 'Drankkaart' ? 'bg-fuchsia-950/80 text-fuchsia-300 border border-fuchsia-500/40' : 'bg-slate-900/80 text-slate-300 border border-slate-700' }}">
                                                {{ $item->quantity }}x {{ $item->product_name }}
                                                <span class="text-slate-400 text-[11px]">(€ {{ number_format($item->product_price / 100, 2, ',', '.') }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if($order->vakjes)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/40">
                                            {{ $order->vakjes }} vakjes
                                        </span>
                                    @else
                                        <span class="text-slate-600">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-black text-base text-emerald-400 whitespace-nowrap">
                                    € {{ number_format($order->total_amount / 100, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xs">
                                    @if($order->settled_at)
                                        <span class="px-2.5 py-1 rounded-lg bg-purple-500/20 text-purple-300 border border-purple-500/40 font-bold" title="Afgesloten en poef verwerkt op {{ $order->settled_at }}">
                                            ✓ Poef Verwerkt
                                        </span>
                                    @elseif($order->beheer_status === 'synced')
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                                            ✓ Gesynchroniseerd
                                        </span>
                                    @elseif($order->beheer_status === 'duplicate')
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-700 text-slate-300 border border-slate-600 font-semibold">
                                            Reeds Verwerkt
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/40 font-semibold">
                                            Wachtrij
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                    Geen bestellingen gevonden voor de geselecteerde filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="p-6 border-t border-slate-700/50">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: Cashier Sessions & Logins -->
    @if($activeTab === 'cashiers')
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-700/50 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-100">Kassier Aanwezigheid & Sessielog</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Overzicht van wie op welk moment aan welke kassa stond ingelogd.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900/60 text-xs uppercase font-bold text-slate-400 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-4">Kassa</th>
                            <th class="px-6 py-4">Kassier</th>
                            <th class="px-6 py-4">Inlogmethode</th>
                            <th class="px-6 py-4">Ingelogd op</th>
                            <th class="px-6 py-4">Uitgelogd op</th>
                            <th class="px-6 py-4">Duur</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/40">
                        @forelse($cashierSessions as $sess)
                            <tr class="hover:bg-slate-800/60 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-200">
                                    <span class="px-3 py-1 rounded-xl bg-slate-900 border border-slate-700 text-xs text-indigo-300">
                                        Kassa {{ $sess->kassa_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-white text-base">
                                    {{ $sess->cashier_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs">
                                    @if($sess->auth_method === 'badge' || $sess->auth_method === 'scanner')
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-semibold">
                                            🏷️ Badge Scan
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 font-semibold">
                                            🔢 PIN Code
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    <div class="font-bold text-slate-200">{{ $sess->logged_in_at?->format('d/m/Y') }}</div>
                                    <div>{{ $sess->logged_in_at?->format('H:i:s') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-400 font-mono">
                                    @if($sess->logged_out_at)
                                        <div class="font-bold text-slate-200">{{ $sess->logged_out_at->format('d/m/Y') }}</div>
                                        <div>{{ $sess->logged_out_at->format('H:i:s') }}</div>
                                    @else
                                        <span class="text-emerald-400 font-bold italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-300 font-mono">
                                    @if($sess->logged_out_at)
                                        {{ $sess->logged_in_at?->diffForHumans($sess->logged_out_at, true) }}
                                    @else
                                        {{ $sess->logged_in_at?->diffForHumans(now(), true) }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if(! $sess->logged_out_at)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Actief
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                            Afgemeld
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    Nog geen kassier inlogsessies geregistreerd.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

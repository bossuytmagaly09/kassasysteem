<div class="flex-1 flex w-full h-full overflow-hidden p-4 gap-4">

    @if(! $event)
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-12 max-w-lg shadow-xl">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-amber-500/20 border border-amber-500/40 flex items-center justify-center">
                    <svg class="w-10 h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h2 class="text-3xl font-black text-slate-100 mb-3">Geen actief event</h2>
                <p class="text-slate-400 text-lg mb-6">Activeer eerst een event in het beheer om te kunnen verkopen.</p>
                <a href="{{ route('admin.events') }}" class="inline-block px-8 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition-colors">Naar events</a>
            </div>
        </div>
    @else
    <!-- Left Side: Categories and Products -->
    <div class="flex-1 min-w-0 flex flex-col bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden">

        <!-- Category Tabs & Quick Drankkaart -->
        <div class="flex p-4 gap-3 border-b border-slate-700/50 bg-slate-900/30 items-center justify-between overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden shrink-0">
            <div class="flex gap-3 overflow-x-auto [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                @foreach($categories as $category)
                    <button
                        type="button"
                        wire:key="category-{{ $category->id }}"
                        wire:click="setCategory({{ $category->id }})"
                        class="px-8 py-4 text-xl font-bold rounded-2xl whitespace-nowrap transition-all duration-200 border shrink-0
                        {{ $activeCategoryId == $category->id
                            ? 'bg-indigo-600 border-indigo-400 text-white shadow-[0_0_20px_-5px_rgba(99,102,241,0.5)]'
                            : 'bg-slate-800/50 border-slate-600/50 text-slate-300 hover:bg-slate-700 hover:text-white' }}">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            @if($event->payment_mode->allowsDrankkaart())
                <button
                    type="button"
                    id="btn-add-drankkaart"
                    wire:click="addDrankkaart"
                    class="shrink-0 px-6 py-4 text-lg font-bold rounded-2xl whitespace-nowrap transition-all duration-200 border bg-gradient-to-r from-fuchsia-600 to-purple-600 hover:from-fuchsia-500 hover:to-purple-500 border-fuchsia-400/50 text-white shadow-[0_0_20px_-5px_rgba(192,38,211,0.5)] flex items-center gap-2 active:scale-95">
                    <svg class="w-5 h-5 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    <span>+ Consumptiekaart (€ {{ number_format(($event->card_price ?? 2000) / 100, 2, ',', '.') }})</span>
                </button>
            @endif
        </div>

        <!-- Product Grid -->
        <div class="flex-1 p-6 overflow-y-auto [scrollbar-width:thin]">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 lg:gap-6">
                @forelse($products as $product)
                    <button
                        type="button"
                        wire:key="product-{{ $product->id }}"
                        wire:click="addToOrder({{ $product->id }})"
                        @disabled($product->sold_out)
                        class="group relative min-h-[140px] p-4 bg-slate-800/50 backdrop-blur-sm border border-slate-600/50 rounded-2xl flex flex-col items-center justify-center hover:bg-indigo-900/40 hover:border-indigo-500/50 active:bg-indigo-600/60 active:scale-95 transition-all duration-150 overflow-hidden shadow-lg disabled:grayscale disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-slate-800/50 disabled:hover:border-slate-600/50 disabled:active:scale-100">
                        @if($product->remaining !== null)
                            <span class="absolute top-2 right-3 text-xs font-bold pointer-events-none {{ $product->sold_out ? 'text-red-400' : ($product->remaining <= 10 ? 'text-amber-400' : 'text-slate-500') }}">
                                {{ $product->sold_out ? 'UITVERKOCHT' : $product->remaining.' over' }}
                            </span>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/0 to-indigo-500/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                        <span class="relative text-xl font-bold text-white mb-2 text-center group-hover:text-indigo-200 transition-colors pointer-events-none line-clamp-2 leading-tight">
                            {{ preg_replace('/\s*\(.*?\)/', '', $product->name) }}
                        </span>
                        <span class="relative px-4 py-1.5 bg-slate-900/60 rounded-full border border-slate-700 text-lg font-bold text-emerald-400 pointer-events-none">
                            € {{ number_format($product->pivot->price / 100, 2, ',', '.') }}
                        </span>
                    </button>
                @empty
                    <p class="col-span-full text-center text-slate-500 text-lg py-12">Geen producten in deze categorie.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Side: Order Summary -->
    <div class="w-96 shrink-0 flex flex-col bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden relative">

        <!-- Header -->
        <div class="p-6 bg-slate-900/30 border-b border-slate-700/50 flex justify-between items-center shrink-0">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Winkelmandje</span>
            </h2>
            <div class="flex gap-2 items-center">
                <button type="button" wire:click="clearOrder" class="p-2 bg-slate-800 text-slate-400 hover:text-red-400 rounded-full transition-colors" title="Winkelmandje leegmaken">
                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
                <span class="bg-indigo-500/20 text-indigo-300 py-1 px-3 rounded-full text-sm font-semibold border border-indigo-500/30">
                    {{ collect($orderItems)->sum('quantity') }} items
                </span>
            </div>
        </div>

        <!-- Order Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 [scrollbar-width:thin]">
            @forelse($orderItems as $index => $item)
                <button
                    type="button"
                    wire:key="order-item-{{ $item['product_id'] ?? 'kaart-'.$index }}"
                    wire:click="removeOne({{ $index }})"
                    class="w-full text-left relative flex justify-between items-center bg-slate-800/50 p-3.5 rounded-2xl border border-slate-700/60 hover:bg-red-900/40 hover:border-red-500/40 transition-colors group">
                    <div class="flex items-center gap-3 flex-1 min-w-0 pointer-events-none">
                        <div class="w-10 h-10 min-w-[2.5rem] min-h-[2.5rem] rounded-xl {{ ($item['is_drankkaart'] ?? false) ? 'bg-gradient-to-br from-fuchsia-600/30 to-purple-600/30 text-fuchsia-200 border-fuchsia-500/50' : 'bg-slate-700/60 text-slate-100 border-slate-600/70' }} flex items-center justify-center font-black text-sm shrink-0 border group-hover:bg-red-500/20 group-hover:text-red-400 group-hover:border-red-500/40 shadow-sm transition-colors">
                            {{ $item['quantity'] }}x
                        </div>
                        <div class="truncate pr-1">
                            <div class="font-bold text-white group-hover:text-red-200 transition-colors truncate text-base flex items-center gap-2">
                                @if($item['is_drankkaart'] ?? false)
                                    <span class="w-5 h-5 rounded-md bg-fuchsia-500/20 border border-fuchsia-500/30 flex items-center justify-center text-fuchsia-300 shrink-0" title="Consumptiekaart">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                    </span>
                                @endif
                                <span class="truncate">{{ preg_replace('/\s*\(.*?\)/', '', $item['name']) }}</span>
                            </div>
                            <div class="text-xs text-slate-400 whitespace-nowrap">€&nbsp;{{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.</div>
                        </div>
                    </div>
                    <div class="text-lg font-black text-emerald-400 shrink-0 pointer-events-none whitespace-nowrap">
                        €&nbsp;{{ number_format(($item['price'] * $item['quantity']) / 100, 2, ',', '.') }}
                    </div>
                </button>
            @empty
                <div class="h-full flex flex-col items-center justify-center text-slate-500 space-y-3 py-6">
                    <div class="w-20 h-20 rounded-full bg-slate-800 flex items-center justify-center border border-slate-700">
                        <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <p class="text-base font-medium">Nog geen producten gekozen</p>
                </div>
            @endforelse
        </div>

        <!-- Order Total & Actions -->
        <div class="p-6 bg-slate-900/40 border-t border-slate-700/50 space-y-4 shrink-0">
            <div class="flex justify-between items-center">
                <span class="text-slate-400 text-sm font-medium">Totaalbedrag</span>
                <span class="text-3xl font-black text-emerald-400">
                    € {{ number_format($total / 100, 2, ',', '.') }}
                </span>
            </div>

            @if($paymentError && ! $awaitingPayment && ! $showCashModal && ! $showDrankkaartModal && ! $showQrModal && ! $showDrankkaartPurchaseModal)
                <div class="p-3 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="truncate">{{ $paymentError }}</span>
                </div>
            @endif

            <div class="flex gap-3">
                @if($hasDrankkaart)
                <button
                    type="button"
                    id="btn-drankkaart"
                    wire:click="openDrankkaart"
                    @disabled(empty($orderItems))
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.98] text-white font-black text-xl shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-3 transition-all border border-emerald-400/30 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <svg class="w-6 h-6 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Afrekenen</span>
                </button>
                @else
                    @if($event->payment_mode->allowsDrankkaart())
                    <button
                        type="button"
                        id="btn-drankkaart"
                        wire:click="openDrankkaart"
                        @disabled(empty($orderItems))
                        class="flex-[1.3] min-w-0 py-4 px-3 text-base font-black text-white bg-gradient-to-r from-fuchsia-600 to-purple-600 hover:from-fuchsia-500 hover:to-purple-500 active:scale-[0.98] transition-all shadow-lg shadow-fuchsia-900/30 border border-fuchsia-400/40 flex justify-center items-center gap-2 rounded-2xl disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                        <svg class="w-5 h-5 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        <span class="truncate">Consumptiekaart</span>
                    </button>
                    @endif

                    @if($event->payment_mode->allowsScanner())
                    <button
                        type="button"
                        id="btn-afrekenen"
                        wire:click="startPayment"
                        @disabled(empty($orderItems))
                        class="flex-[0.7] min-w-0 py-4 px-3 text-base font-black text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.98] transition-all shadow-lg shadow-emerald-900/30 border border-emerald-400/40 flex justify-center items-center gap-2 rounded-2xl disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                        <svg class="w-5 h-5 pointer-events-none shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span class="truncate">Scanner</span>
                    </button>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Drankkaart Modal (Popup for both purchase and regular doorstrepen) -->
    @if($showDrankkaartModal || $showDrankkaartPurchaseModal || $showCashModal || $showQrModal)
        @if($hasDrankkaart)
            <!-- Drankkaart Purchase Modal (Cash or QR, compact landscape, zero scrolling) -->
            <div wire:key="drankkaart-purchase-modal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4 pt-16 sm:pt-20 overflow-y-auto"
                 style="padding-top: 5.5rem; padding-bottom: 2.5rem;"
                 x-data
                 @keydown.escape.window="$wire.closeDrankkaart()">
                <div class="bg-slate-900 border border-slate-700 p-4 sm:p-5 rounded-3xl shadow-2xl max-w-xl w-full relative flex flex-col my-auto"
                     style="margin-top: auto; margin-bottom: auto;">
                    
                    <!-- Top Bar: Title, QR/Cash Switcher & Close button (Always Prominently Visible) -->
                    <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-800 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-fuchsia-500/20 border border-fuchsia-500/40 flex items-center justify-center text-fuchsia-400 shrink-0">
                                <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-black text-white leading-tight">Consumptiekaart Afrekenen</h3>
                                <p class="text-xs text-slate-400 leading-tight">Kies Contant of QR Code om af te rekenen</p>
                            </div>
                        </div>

                        <!-- Payment Method Toggle (Cash vs QR) + Close Button -->
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="flex gap-1.5 p-1 bg-slate-950 rounded-xl border border-slate-700 shrink-0">
                                <button type="button"
                                        id="btn-cash"
                                        wire:click="setCardPaymentMethod('cash')"
                                        class="py-1.5 px-3 rounded-lg font-bold text-sm transition-all flex items-center gap-1.5 {{ $cardPaymentMethod === 'cash' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    <span>Contant (Cash)</span>
                                </button>
                                <button type="button"
                                        id="btn-qr"
                                        wire:click="setCardPaymentMethod('qr')"
                                        class="py-1.5 px-3 rounded-lg font-bold text-sm transition-all flex items-center gap-1.5 {{ $cardPaymentMethod === 'qr' ? 'bg-cyan-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    <span>QR Code</span>
                                </button>
                            </div>

                            <button type="button" id="btn-drankkaart-close" wire:click="closeDrankkaart" class="p-1 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 transition-colors ml-1" title="Sluiten">
                                <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    @if($cardPaymentMethod === 'cash')
                        <!-- Cash Payment: Clean 2 columns with prominent Keypad -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-3 mb-3 items-stretch">
                            
                            <!-- Left Column: Te betalen & Wisselgeld (Clean & uncluttered) -->
                            <div class="md:col-span-5 flex flex-col justify-between gap-3">
                                <!-- Te Betalen Card -->
                                <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700/80 flex flex-col justify-center">
                                    <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Te betalen voor consumptiekaart:</span>
                                    <div class="text-3xl sm:text-4xl font-black text-emerald-400 mt-1 whitespace-nowrap">
                                        € {{ number_format($cardTotal / 100, 2, ',', '.') }}
                                    </div>
                                </div>

                                <!-- Wisselgeld / Nog te ontvangen Card -->
                                @php
                                    $tenderedFloat = (float) str_replace(',', '.', $cashTendered);
                                    $tenderedCents = (int) round($tenderedFloat * 100);
                                    $changeCents = max(0, $tenderedCents - $cardTotal);
                                @endphp
                                @if($tenderedCents >= $cardTotal)
                                    <div class="p-4 rounded-2xl bg-emerald-500/15 border-2 border-emerald-500/40 flex flex-col justify-center shadow-md">
                                        <span class="text-xs uppercase tracking-wider font-bold text-emerald-300 block">Wisselgeld klant:</span>
                                        <div class="text-3xl sm:text-4xl font-black text-emerald-400 mt-1 whitespace-nowrap flex items-center tracking-tight">
                                            € {{ number_format($changeCents / 100, 2, ',', '.') }}
                                        </div>
                                    </div>
                                @else
                                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 text-slate-400 flex flex-col justify-center">
                                        <span class="text-xs uppercase tracking-wider font-bold text-slate-400 block">Nog te ontvangen:</span>
                                        <div class="text-3xl sm:text-4xl font-black text-amber-400 mt-1 whitespace-nowrap flex items-center">
                                            € {{ number_format(($cardTotal - $tenderedCents) / 100, 2, ',', '.') }}
                                        </div>
                                    </div>
                                @endif

                                <!-- Drinks Crossed off Notice (if any) -->
                                @if($drinksTotal > 0)
                                    <div class="p-2.5 rounded-xl bg-fuchsia-950/40 border border-fuchsia-500/40 text-fuchsia-300 text-xs sm:text-sm text-center font-bold">
                                        Direct doorstrepen: <strong class="text-white">{{ $drinksVakjes }} vakjes</strong>
                                    </div>
                                @endif

                                @if($paymentError)
                                    <p class="text-red-400 text-xs font-semibold text-center">{{ $paymentError }}</p>
                                @endif
                            </div>

                            <!-- Right Column: Touchscreen Ontvangen Bedrag + Large Keypad -->
                            <div class="md:col-span-7 flex flex-col bg-slate-950/80 p-3 sm:p-4 rounded-2xl border border-slate-800">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="text-xs sm:text-sm font-bold text-slate-300">Ontvangen bedrag (€):</label>
                                    <button type="button"
                                            wire:click="clearCashTendered"
                                            class="px-3 py-1 text-xs sm:text-sm font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 active:scale-95 rounded-lg border border-slate-600 transition-all">
                                        Wissen
                                    </button>
                                </div>
                                
                                <!-- Amount Display -->
                                <div class="bg-slate-900 border-2 border-slate-700 rounded-xl px-4 py-2.5 flex items-center justify-between shadow-inner mb-4 sm:mb-5">
                                    <span class="text-slate-400 font-bold text-xl sm:text-2xl">€</span>
                                    <div class="text-3xl sm:text-4xl font-black text-white font-mono tracking-wider text-right">
                                        {{ $cashTendered !== '' ? str_replace('.', ',', $cashTendered) : '0,00' }}
                                    </div>
                                </div>

                                <!-- Keypad 3x4 Grid with LARGE, touchscreen-friendly buttons -->
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" wire:click="appendCashDigit('7')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">7</button>
                                    <button type="button" wire:click="appendCashDigit('8')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">8</button>
                                    <button type="button" wire:click="appendCashDigit('9')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">9</button>
                                    <button type="button" wire:click="appendCashDigit('4')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">4</button>
                                    <button type="button" wire:click="appendCashDigit('5')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">5</button>
                                    <button type="button" wire:click="appendCashDigit('6')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">6</button>
                                    <button type="button" wire:click="appendCashDigit('1')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">1</button>
                                    <button type="button" wire:click="appendCashDigit('2')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">2</button>
                                    <button type="button" wire:click="appendCashDigit('3')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">3</button>
                                    <button type="button" wire:click="appendCashDigit(',')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-slate-300 rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">,</button>
                                    <button type="button" wire:click="appendCashDigit('0')" class="h-14 sm:h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl sm:text-3xl font-black text-white rounded-xl shadow transition-all active:scale-95 flex items-center justify-center">0</button>
                                    <button type="button" wire:click="backspaceCashTendered" class="h-14 sm:h-16 bg-slate-800 hover:bg-red-900 text-red-400 rounded-xl shadow transition-all active:scale-95 flex items-center justify-center font-black text-2xl sm:text-3xl" title="Backspace">⌫</button>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- QR Code / Payconiq / Bank App Flow -->
                        <div class="mt-3 mb-3 py-2">
                            <div class="bg-slate-800 border border-cyan-500/30 rounded-2xl p-5 text-center max-w-lg mx-auto">
                                <div class="w-12 h-12 rounded-xl bg-cyan-500/20 border border-cyan-500/40 flex items-center justify-center text-cyan-400 mx-auto mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                </div>
                                <h4 class="text-lg font-black text-cyan-200 mb-1">Betaling via Externe Telefoon / Payconiq</h4>
                                <p class="text-sm text-slate-300 mb-2">
                                    Vraag de klant om <strong class="text-cyan-300 text-base">€ {{ number_format($cardTotal / 100, 2, ',', '.') }}</strong> te betalen via de Payconiq of Bank app.
                                </p>
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/20 border border-cyan-500/30 text-xs text-cyan-300 font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                    Wordt geregistreerd als QR-betaling
                                </div>
                            </div>

                            @if($drinksTotal > 0)
                                <div class="mt-3 p-2 rounded-xl bg-fuchsia-950/40 border border-fuchsia-500/40 text-fuchsia-300 text-xs text-center font-bold max-w-lg mx-auto">
                                    Direct doorstrepen: <strong class="text-white">{{ $drinksVakjes }} vakjes</strong>
                                </div>
                            @endif

                            @if($paymentError)
                                <p class="mt-2 text-red-400 text-xs font-semibold text-center">{{ $paymentError }}</p>
                            @endif
                        </div>
                    @endif

                    <!-- Pinned Footer Action Buttons (ALWAYS at bottom across full width) -->
                    <div class="flex gap-3 pt-3 border-t border-slate-800 shrink-0">
                        <button type="button" wire:click="closeDrankkaart" class="w-1/3 py-3 text-base font-bold text-slate-300 border border-slate-600 rounded-xl hover:bg-slate-800 transition-all text-center">
                            Terug
                        </button>
                        <button type="button"
                                id="btn-drankkaart-klaar"
                                wire:click="completeDrankkaart"
                                class="w-2/3 py-3 px-3 text-base font-black text-white bg-gradient-to-r {{ $cardPaymentMethod === 'qr' ? 'from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500' : 'from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500' }} rounded-xl transition-all shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span>{{ $cardPaymentMethod === 'qr' ? 'Betaling Voltooid (QR)' : 'Bevestig Betaling' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Drankkaart Modal (Doorstrepen op bestaande kaart, compact) -->
            <div wire:key="drankkaart-modal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4"
                 x-data
                 @keydown.escape.window="$wire.closeDrankkaart()">
                <div class="bg-slate-900 border border-slate-700 p-5 md:p-6 rounded-2xl md:rounded-3xl shadow-2xl max-w-md w-full relative">
                    <button type="button" id="btn-drankkaart-close" wire:click="closeDrankkaart" class="absolute top-3 right-3 p-1.5 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Sluiten">
                        <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <h3 class="text-2xl font-black text-fuchsia-400 mb-1">Consumptiekaart Afrekenen</h3>
                    <p class="text-xs sm:text-sm text-slate-400 mb-4">Streep het aantal vakjes door op de consumptiekaart van de klant.</p>

                    <div class="bg-slate-800 rounded-xl p-3.5 mb-3.5">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-slate-300 text-sm">Totaalbedrag:</span>
                            <span class="text-xl font-bold text-white whitespace-nowrap shrink-0 flex items-center">
                                <span class="mr-1">€</span><span>{{ number_format($total / 100, 2, ',', '.') }}</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-700 pt-2">
                            <span class="text-slate-300 text-sm">Waarde 1 vakje:</span>
                            <span class="text-base font-bold text-fuchsia-300 whitespace-nowrap shrink-0 flex items-center">
                                <span class="mr-1">€</span><span>{{ number_format($event->vak_price / 100, 2, ',', '.') }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="text-center py-4 bg-fuchsia-900/20 border border-fuchsia-500/30 rounded-xl mb-4">
                        <p class="text-slate-400 text-sm mb-0.5">Doorstreep op de kaart:</p>
                        <div class="text-5xl font-black text-fuchsia-400 flex justify-center items-center gap-3">
                            <span>{{ $vakjes }}</span>
                            <span class="text-xl font-medium text-fuchsia-300">vakjes</span>
                        </div>
                    </div>

                    <div class="flex gap-2.5">
                        <button type="button" wire:click="closeDrankkaart" class="w-1/3 py-2.5 md:py-3 text-base font-bold text-slate-300 border border-slate-600 rounded-xl hover:bg-slate-800 transition-all">
                            Terug
                        </button>
                        <button type="button" id="btn-drankkaart-klaar" wire:click="completeDrankkaart" class="w-2/3 py-2.5 md:py-3 text-base md:text-lg font-bold text-white bg-fuchsia-600 rounded-xl hover:bg-fuchsia-500 active:bg-fuchsia-700 transition-all shadow-md">
                            Klaar
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Wait for Scan Modal -->
    @if($awaitingPayment)
        <div wire:key="payment-modal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4"
             x-data="{ code: '' }"
             x-init="$nextTick(() => $refs.scanInput.focus())"
             @click="$refs.scanInput.focus()"
             @keydown.escape.window="$wire.cancelPayment()">
            <div class="bg-slate-900 border border-indigo-500/50 p-8 md:p-12 rounded-3xl shadow-2xl max-w-lg w-full relative text-center">

                <div class="relative w-24 h-24 mx-auto mb-6">
                    <span class="absolute inset-0 rounded-full bg-indigo-500/30 animate-ping"></span>
                    <div class="relative w-24 h-24 rounded-full bg-indigo-500/20 flex items-center justify-center border-4 border-indigo-500/50">
                        <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    </div>
                </div>

                <h3 class="text-3xl font-black text-indigo-300 mb-2">Wachten op kaart…</h3>
                <p class="text-slate-400 text-base mb-6">Houd de kaart van de klant tegen de scanner.</p>

                <div class="text-5xl font-black text-white mb-6">
                    € {{ number_format($total / 100, 2, ',', '.') }}
                </div>

                <input
                    x-ref="scanInput"
                    x-model="code"
                    id="scan-input"
                    type="text"
                    autocomplete="off"
                    placeholder="Kaartnummer (scanner of typen + Enter)"
                    @keydown.enter.prevent="if (code.trim() !== '') { $wire.handleScan(code); code = ''; }"
                    class="w-full mb-4 bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-center text-lg text-white placeholder-slate-500 focus:outline-none focus:border-indigo-400">

                @if($paymentError)
                    <p class="mb-4 text-red-400 text-sm font-semibold">{{ $paymentError }}</p>
                @endif

                <div wire:loading wire:target="handleScan" class="mb-4 text-indigo-300 text-sm font-semibold">Betaling verwerken…</div>

                <button type="button" id="btn-annuleren" wire:click="cancelPayment" class="px-8 py-3 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-800 hover:text-white transition-all font-bold text-base">
                    Annuleren
                </button>
            </div>
        </div>
    @endif

    <!-- Payment Success Modal -->
    @if($paymentSuccess)
        <div wire:key="success-modal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-emerald-950/90 backdrop-blur-md cursor-pointer p-4"
             x-data
             x-init="setTimeout(() => $wire.closeSuccess(), 3000)"
             wire:click="closeSuccess">
            <div class="text-center p-8 max-w-lg">
                <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-emerald-500 flex items-center justify-center shadow-[0_0_60px_-10px_rgba(16,185,129,0.8)]"
                     x-data="{ shown: false }" x-init="requestAnimationFrame(() => shown = true)"
                     :class="shown ? 'scale-100 opacity-100' : 'scale-50 opacity-0'"
                     style="transition: transform 400ms cubic-bezier(.34,1.56,.64,1), opacity 300ms;">
                    <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-5xl font-black text-emerald-300 mb-3 drop-shadow-lg">Betaald!</h2>
                
                @if($lastPaymentMethod === 'cash')
                    <p class="text-2xl font-bold text-emerald-100 mb-1">Contant betaald</p>
                    @if($lastCashChange !== null && $lastCashChange > 0)
                        <p class="text-xl text-emerald-300 font-bold bg-emerald-900/60 py-2 px-4 rounded-xl border border-emerald-500/40 inline-block mt-2">
                            Wisselgeld: € {{ number_format($lastCashChange / 100, 2, ',', '.') }}
                        </p>
                    @endif
                @elseif($lastPaymentMethod === 'qr')
                    <p class="text-2xl font-bold text-cyan-200">Betaald via QR Code</p>
                @elseif($lastPaymentMethod === 'scanner')
                    @if($scannedPoefName)
                        <p class="text-2xl font-bold text-emerald-100">Geregistreerd op de poef van:</p>
                        <p class="text-3xl font-black text-indigo-300 mt-1">{{ $scannedPoefName }}</p>
                        @if($lastPoefTotalSpent !== null)
                            <div class="mt-3 inline-block bg-indigo-900/60 py-2 px-5 rounded-2xl border border-indigo-400/40 shadow-lg">
                                <span class="text-xs uppercase tracking-wider text-slate-300 font-bold block">Totaal vanavond</span>
                                <span class="text-2xl font-black text-emerald-300">€ {{ number_format($lastPoefTotalSpent / 100, 2, ',', '.') }}</span>
                            </div>
                        @endif
                    @else
                        <p class="text-xl text-emerald-200">Kaart gescand en geregistreerd.</p>
                    @endif
                @else
                    <p class="text-xl text-emerald-200">Bestelling succesvol geregistreerd.</p>
                @endif

                @if($lastDrinksVakjes !== null && $lastDrinksVakjes > 0)
                    <div class="mt-4 p-3 bg-fuchsia-950/80 border border-fuchsia-500/50 rounded-2xl text-fuchsia-200 inline-block">
                        <span class="text-xs uppercase tracking-wider text-fuchsia-300 font-bold block">Consumptiekaart:</span>
                        <strong class="text-white text-2xl font-black mt-0.5">{{ $lastDrinksVakjes }} vakjes</strong> doorgestreept
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

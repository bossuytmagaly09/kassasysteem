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
        <div class="flex p-4 gap-3 overflow-x-auto border-b border-slate-700/50 bg-slate-900/30 items-center justify-between">
            <div class="flex gap-3 overflow-x-auto">
                @foreach($categories as $category)
                    <button
                        type="button"
                        wire:key="category-{{ $category->id }}"
                        wire:click="setCategory({{ $category->id }})"
                        class="px-8 py-4 text-xl font-bold rounded-2xl whitespace-nowrap transition-all duration-200 border
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
                    <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    <span>+ Consumptiekaart Kopen (€ {{ number_format(($event->card_price ?? 2000) / 100, 2, ',', '.') }})</span>
                </button>
            @endif
        </div>

        <!-- Product Grid -->
        <div class="flex-1 p-6 overflow-y-auto">
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
                        <span class="relative text-2xl font-bold text-slate-200 text-center px-3 mb-2 group-hover:text-white pointer-events-none">{{ preg_replace('/\s*\(.*?\)/', '', $product->name) }}</span>
                        <span class="relative px-4 py-1 bg-slate-900/50 rounded-full border border-slate-700 text-lg font-semibold text-emerald-400 pointer-events-none">
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
    <div class="w-96 lg:w-[420px] 2xl:w-[460px] shrink-0 flex flex-col bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-2xl overflow-hidden relative">

        <!-- Header -->
        <div class="p-6 bg-slate-900/40 border-b border-slate-700/50 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-slate-100 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                Winkelmandje
            </h2>
            <div class="flex gap-2 items-center">
                <button type="button" wire:click="clearOrder" class="p-2 bg-slate-800 text-slate-400 hover:text-red-400 rounded-full transition-colors" title="Winkelmandje leegmaken">
                    <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
                <span class="bg-indigo-500/20 text-indigo-300 py-1 px-3 rounded-full text-sm font-bold border border-indigo-500/30">
                    {{ collect($orderItems)->sum('quantity') }} items
                </span>
            </div>
        </div>

        <!-- Order Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            @forelse($orderItems as $index => $item)
                <button
                    type="button"
                    wire:key="order-item-{{ $item['product_id'] ?? 'kaart-'.$index }}"
                    wire:click="removeOne({{ $index }})"
                    class="w-full text-left relative flex justify-between items-center bg-slate-800/80 p-4 rounded-2xl border border-slate-600/50 hover:bg-red-900/30 hover:border-red-500/30 transition-colors group">
                    <div class="flex items-center gap-4 flex-1 min-w-0 pointer-events-none">
                        <div class="shrink-0 w-10 h-10 rounded-full {{ ($item['is_drankkaart'] ?? false) ? 'bg-fuchsia-600/30 text-fuchsia-300' : 'bg-slate-700 text-slate-300' }} flex items-center justify-center font-bold group-hover:bg-red-500/20 group-hover:text-red-400">
                            {{ $item['quantity'] }}x
                        </div>
                        <div class="truncate pr-2">
                            <div class="text-lg font-bold text-slate-200 group-hover:text-red-100 truncate flex items-center gap-2">
                                @if($item['is_drankkaart'] ?? false)
                                    <span class="text-fuchsia-400">🎟️</span>
                                @endif
                                <span>{{ preg_replace('/\s*\(.*?\)/', '', $item['name']) }}</span>
                            </div>
                            <div class="text-slate-400 text-sm">€ {{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.</div>
                        </div>
                    </div>
                    <div class="text-xl font-bold text-emerald-400 group-hover:opacity-0 transition-opacity pointer-events-none">
                        € {{ number_format(($item['price'] * $item['quantity']) / 100, 2, ',', '.') }}
                    </div>
                    <div class="absolute right-4 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <svg class="w-7 h-7 text-red-400 drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                    </div>
                </button>
            @empty
                <div class="h-full flex flex-col items-center justify-center text-slate-500 space-y-4">
                    <div class="w-24 h-24 rounded-full bg-slate-800 flex items-center justify-center border border-slate-700">
                        <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <p class="text-lg font-medium">Nog geen producten gekozen</p>
                </div>
            @endforelse
        </div>

        <!-- Order Total & Actions -->
        <div class="p-6 bg-slate-900/60 border-t border-slate-700/50 backdrop-blur-md">
            <div class="flex justify-between items-center mb-4">
                <span class="text-xl text-slate-400 font-medium">Totaalbedrag</span>
                <span class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                    € {{ number_format($total / 100, 2, ',', '.') }}
                </span>
            </div>

            @if($paymentError && ! $awaitingPayment && ! $showCashModal && ! $showDrankkaartModal && ! $showQrModal && ! $showDrankkaartPurchaseModal)
                <div class="mb-4 p-2.5 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $paymentError }}</span>
                </div>
            @endif

            <div class="flex gap-3">
                @if($event->payment_mode->allowsDrankkaart())
                <button
                    type="button"
                    id="btn-drankkaart"
                    wire:click="openDrankkaart"
                    @disabled(empty($orderItems))
                    class="flex-1 py-4 text-xl font-black text-white bg-gradient-to-r from-fuchsia-600 to-purple-600 rounded-xl hover:from-fuchsia-500 hover:to-purple-500 active:scale-[0.98] transition-all shadow-[0_0_20px_-5px_rgba(192,38,211,0.5)] border border-fuchsia-400/50 flex justify-center items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    <span>Consumptiekaart</span>
                </button>
                @endif

                @if($event->payment_mode->allowsScanner())
                <button
                    type="button"
                    id="btn-afrekenen"
                    wire:click="startPayment"
                    @disabled(empty($orderItems))
                    class="flex-1 py-4 text-xl font-black text-white bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl hover:from-emerald-400 hover:to-teal-400 active:scale-[0.98] transition-all shadow-[0_0_20px_-5px_rgba(16,185,129,0.5)] border border-emerald-400/50 flex justify-center items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100">
                    <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span>Scanner</span>
                </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Drankkaart Modal (Popup for both purchase and regular doorstrepen) -->
    @if($showDrankkaartModal || $showDrankkaartPurchaseModal || $showCashModal || $showQrModal)
        @if($hasDrankkaart)
            <!-- Drankkaart Purchase Modal (Cash or QR) -->
            <div wire:key="drankkaart-purchase-modal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 backdrop-blur-sm p-4"
                 x-data
                 @keydown.escape.window="$wire.closeDrankkaart()">
                <div class="bg-slate-900 border border-indigo-500/40 p-8 rounded-3xl shadow-2xl max-w-lg w-full relative max-h-[90vh] overflow-y-auto">
                    <button type="button" id="btn-drankkaart-close" wire:click="closeDrankkaart" class="absolute top-4 right-4 p-2 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Sluiten">
                        <svg class="w-8 h-8 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-fuchsia-500/20 border border-fuchsia-500/40 flex items-center justify-center text-fuchsia-400">
                            <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-white">Consumptiekaart Afrekenen</h3>
                            <p class="text-xs text-slate-400">
                                {{ $drinksTotal > 0 ? 'Betaal de kaart contant of via QR en streep de consumpties af.' : 'Ontvang betaling voor de aankoop van de consumptiekaart.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Payment Method Toggle (Cash vs QR) -->
                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-950/70 border border-slate-700/60 rounded-2xl mb-5">
                        <button type="button"
                                id="btn-cash"
                                wire:click="setCardPaymentMethod('cash')"
                                class="py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 {{ $cardPaymentMethod === 'cash' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <span>Contant (Cash)</span>
                        </button>
                        <button type="button"
                                id="btn-qr"
                                wire:click="setCardPaymentMethod('qr')"
                                class="py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 {{ $cardPaymentMethod === 'qr' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            <span>QR Code</span>
                        </button>
                    </div>

                    <!-- Step 1: Pay the Drankkaart via Cash or QR -->
                    <div class="bg-slate-800/80 rounded-2xl p-5 mb-5 border border-slate-700">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-slate-400 text-sm font-medium">Te betalen voor consumptiekaart:</span>
                            <span class="text-3xl font-black text-emerald-400">€ {{ number_format($cardTotal / 100, 2, ',', '.') }}</span>
                        </div>

                        @if($cardPaymentMethod === 'cash')
                            <div class="mt-3 pt-3 border-t border-slate-700/80">
                                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Ontvangen bedrag (€):</label>
                                
                                <!-- Touchscreen Display Box for Amount -->
                                <div class="bg-slate-950 border-2 border-slate-700 focus-within:border-emerald-500 rounded-2xl p-3 flex items-center justify-between shadow-inner mb-3">
                                    <span class="text-slate-400 font-bold text-2xl pl-2">€</span>
                                    <div class="text-3xl font-black text-white font-mono tracking-wider text-right pr-2">
                                        {{ $cashTendered !== '' ? str_replace('.', ',', $cashTendered) : '0,00' }}
                                    </div>
                                    <button type="button"
                                            wire:click="clearCashTendered"
                                            class="px-3 py-1.5 text-xs font-bold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 active:scale-95 rounded-xl border border-slate-600 transition-all">
                                        Wissen
                                    </button>
                                </div>

                                <!-- Quick Bill Buttons (Touch friendly) -->
                                <div class="grid grid-cols-4 gap-2 mb-3">
                                    @php
                                        $cardEuro = $cardTotal / 100;
                                    @endphp
                                    <button type="button"
                                            wire:click="setCashTendered('{{ number_format($cardEuro, 2, '.', '') }}')"
                                            class="py-2.5 bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-500/40 text-xs font-bold text-indigo-200 rounded-xl transition-all active:scale-95">
                                        Pas (€ {{ number_format($cardEuro, 2, ',', '.') }})
                                    </button>
                                    <button type="button"
                                            wire:click="setCashTendered('20.00')"
                                            class="py-2.5 bg-slate-700/80 hover:bg-slate-600 border border-slate-600/60 text-sm font-bold text-slate-100 rounded-xl transition-all active:scale-95">
                                        € 20
                                    </button>
                                    <button type="button"
                                            wire:click="setCashTendered('50.00')"
                                            class="py-2.5 bg-slate-700/80 hover:bg-slate-600 border border-slate-600/60 text-sm font-bold text-slate-100 rounded-xl transition-all active:scale-95">
                                        € 50
                                    </button>
                                    <button type="button"
                                            wire:click="setCashTendered('100.00')"
                                            class="py-2.5 bg-slate-700/80 hover:bg-slate-600 border border-slate-600/60 text-sm font-bold text-slate-100 rounded-xl transition-all active:scale-95">
                                        € 100
                                    </button>
                                </div>

                                <!-- On-Screen Numeric Touch Keypad (No browser arrows, 100% touch friendly) -->
                                <div class="grid grid-cols-3 gap-2 bg-slate-900/70 p-2.5 rounded-2xl border border-slate-700/60">
                                    <button type="button" wire:click="appendCashDigit('7')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">7</button>
                                    <button type="button" wire:click="appendCashDigit('8')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">8</button>
                                    <button type="button" wire:click="appendCashDigit('9')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">9</button>
                                    <button type="button" wire:click="appendCashDigit('4')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">4</button>
                                    <button type="button" wire:click="appendCashDigit('5')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">5</button>
                                    <button type="button" wire:click="appendCashDigit('6')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">6</button>
                                    <button type="button" wire:click="appendCashDigit('1')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">1</button>
                                    <button type="button" wire:click="appendCashDigit('2')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">2</button>
                                    <button type="button" wire:click="appendCashDigit('3')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">3</button>
                                    <button type="button" wire:click="appendCashDigit(',')" class="py-3 bg-slate-800/80 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-slate-300 rounded-xl shadow transition-all active:scale-95">,</button>
                                    <button type="button" wire:click="appendCashDigit('0')" class="py-3 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-bold text-white rounded-xl shadow transition-all active:scale-95">0</button>
                                    <button type="button" wire:click="backspaceCashTendered" class="py-3 bg-slate-800/80 hover:bg-red-900/40 text-red-400 rounded-xl shadow transition-all active:scale-95 flex items-center justify-center font-bold text-xl" title="Wissen">
                                        ⌫
                                    </button>
                                </div>

                                @php
                                    $tenderedFloat = (float) str_replace(',', '.', $cashTendered);
                                    $tenderedCents = (int) round($tenderedFloat * 100);
                                    $changeCents = max(0, $tenderedCents - $cardTotal);
                                @endphp
                                @if($tenderedCents >= $cardTotal)
                                    <div class="mt-3 p-3.5 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex justify-between items-center shadow-lg">
                                        <span class="text-sm font-bold text-emerald-300">Wisselgeld voor klant:</span>
                                        <span class="text-2xl font-black text-emerald-400">€ {{ number_format($changeCents / 100, 2, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- QR Code / Payconiq / Bank App Flow -->
                            <div class="mt-3 pt-3 border-t border-slate-700/80 text-center">
                                <div class="bg-cyan-950/40 border border-cyan-500/30 rounded-2xl p-5 mb-3">
                                    <div class="w-16 h-16 rounded-2xl bg-cyan-500/20 border border-cyan-500/40 flex items-center justify-center text-cyan-400 mx-auto mb-3">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    </div>
                                    <p class="text-base font-bold text-cyan-200 mb-1">Betaling via Externe Telefoon / Payconiq</p>
                                    <p class="text-xs text-slate-300">
                                        Vraag de klant om <strong class="text-cyan-300 text-sm">€ {{ number_format($cardTotal / 100, 2, ',', '.') }}</strong> te betalen via de Payconiq of Bank app op de externe betaaltelefoon.
                                    </p>
                                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-900/50 border border-cyan-500/30 text-xs text-cyan-300 font-semibold">
                                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                        Wordt gelogd als QR-betaling in het beheer
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Step 2: Drinks to be crossed off on the new card (if any) -->
                    @if($drinksTotal > 0)
                        <div class="bg-fuchsia-950/40 border border-fuchsia-500/50 rounded-2xl p-5 mb-5 text-center shadow-lg">
                            <p class="text-fuchsia-300 text-sm font-bold mb-1">Direct doorstrepen op de nieuwe consumptiekaart:</p>
                            <div class="text-5xl font-black text-fuchsia-400 flex justify-center items-center gap-3 my-2">
                                <span>{{ $drinksVakjes }}</span>
                                <span class="text-2xl font-bold text-fuchsia-300">vakjes</span>
                            </div>
                            <p class="text-xs text-slate-300">
                                Voor consumpties t.w.v. <strong class="text-white">€ {{ number_format($drinksTotal / 100, 2, ',', '.') }}</strong>
                                (Waarde 1 vakje = € {{ number_format($event->vak_price / 100, 2, ',', '.') }})
                            </p>
                            <p class="text-[11px] text-fuchsia-300/80 mt-1 italic">
                                De drankjes worden direct afgerekend op de gekochte kaart en hoeven niet contant of via QR betaald te worden.
                            </p>
                        </div>
                    @else
                        <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-3 mb-5 text-center text-xs text-slate-400">
                            Geen consumpties in bestelling: de klant koopt enkel de consumptiekaart (geen vakjes doorstrepen).
                        </div>
                    @endif

                    @if($paymentError)
                        <p class="mb-4 text-red-400 text-sm font-semibold text-center">{{ $paymentError }}</p>
                    @endif

                    <div class="flex gap-3">
                        <button type="button" wire:click="closeDrankkaart" class="w-1/3 py-3.5 text-base font-bold text-slate-300 border border-slate-600 rounded-xl hover:bg-slate-800 transition-all">
                            Terug
                        </button>
                        <button type="button"
                                id="btn-drankkaart-klaar"
                                wire:click="completeDrankkaart"
                                class="w-2/3 py-3.5 text-lg font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-[0.98] rounded-xl transition-all shadow-[0_0_20px_-5px_rgba(16,185,129,0.5)] flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>{{ $cardPaymentMethod === 'qr' ? 'Betaling Ontvangen & Registreren als QR' : ($drinksTotal > 0 ? 'Betaling Klaar & Vakjes Doorgestreept' : 'Bevestig Betaling') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Drankkaart Modal (Doorstrepen op bestaande kaart) -->
            <div wire:key="drankkaart-modal"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
                 x-data
                 @keydown.escape.window="$wire.closeDrankkaart()">
                <div class="bg-slate-900 border border-slate-700 p-8 rounded-3xl shadow-2xl max-w-md w-full relative">
                    <button type="button" id="btn-drankkaart-close" wire:click="closeDrankkaart" class="absolute top-4 right-4 p-2 rounded-full text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Sluiten">
                        <svg class="w-8 h-8 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <h3 class="text-3xl font-black text-fuchsia-400 mb-2">Consumptiekaart Afrekenen</h3>
                    <p class="text-slate-400 mb-6">Streep het aantal vakjes door op de consumptiekaart van de klant.</p>

                    <div class="bg-slate-800 rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-slate-300 text-lg">Totaalbedrag:</span>
                            <span class="text-2xl font-bold text-white">€ {{ number_format($total / 100, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-slate-700 pt-4">
                            <span class="text-slate-300 text-lg">Waarde 1 vakje:</span>
                            <span class="text-xl font-bold text-fuchsia-300">€ {{ number_format($event->vak_price / 100, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="text-center py-6 bg-fuchsia-900/20 border border-fuchsia-500/30 rounded-2xl mb-6">
                        <p class="text-slate-400 text-lg mb-1">Doorstreep op de kaart:</p>
                        <div class="text-6xl font-black text-fuchsia-400 flex justify-center items-center gap-4">
                            <span>{{ $vakjes }}</span>
                            <span class="text-2xl font-medium text-fuchsia-300">vakjes</span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" wire:click="closeDrankkaart" class="w-1/3 py-4 text-lg font-bold text-slate-300 border border-slate-600 rounded-xl hover:bg-slate-800 transition-all">
                            Terug
                        </button>
                        <button type="button" id="btn-drankkaart-klaar" wire:click="completeDrankkaart" class="w-2/3 py-4 text-xl font-bold text-white bg-fuchsia-600 rounded-xl hover:bg-fuchsia-500 active:bg-fuchsia-700 transition-all shadow-[0_0_20px_-5px_rgba(192,38,211,0.5)]">
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
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
             x-data="{ code: '' }"
             x-init="$nextTick(() => $refs.scanInput.focus())"
             @click="$refs.scanInput.focus()"
             @keydown.escape.window="$wire.cancelPayment()">
            <div class="bg-slate-900 border border-indigo-500/50 p-10 rounded-3xl shadow-[0_0_40px_-10px_rgba(99,102,241,0.5)] max-w-lg w-full relative text-center">

                <div class="relative w-28 h-28 mx-auto mb-6">
                    <span class="absolute inset-0 rounded-full bg-indigo-500/30 animate-ping"></span>
                    <div class="relative w-28 h-28 rounded-full bg-indigo-500/20 flex items-center justify-center border-4 border-indigo-500/50">
                        <svg class="w-14 h-14 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    </div>
                </div>

                <h3 class="text-3xl font-black text-indigo-300 mb-2">Wachten op kaart…</h3>
                <p class="text-slate-400 text-lg mb-6">Houd de kaart van de klant tegen de scanner.</p>

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
                    <p class="mb-4 text-red-400 font-semibold">{{ $paymentError }}</p>
                @endif

                <div wire:loading wire:target="handleScan" class="mb-4 text-indigo-300 font-semibold">Betaling verwerken…</div>

                <button type="button" id="btn-annuleren" wire:click="cancelPayment" class="px-10 py-3 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-800 hover:text-white transition-all font-bold text-lg">
                    Annuleren
                </button>
            </div>
        </div>
    @endif

    <!-- Payment Success Modal -->
    @if($paymentSuccess)
        <div wire:key="success-modal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-emerald-950/90 backdrop-blur-md cursor-pointer"
             x-data
             x-init="setTimeout(() => $wire.closeSuccess(), 3000)"
             wire:click="closeSuccess">
            <div class="text-center p-8 max-w-lg">
                <div class="w-36 h-36 mx-auto mb-6 rounded-full bg-emerald-500 flex items-center justify-center shadow-[0_0_80px_-10px_rgba(16,185,129,0.9)]"
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
                                <span class="text-xs uppercase tracking-wider text-slate-300 font-bold block">Totaal uitgegeven vanavond</span>
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
                        <p class="text-xs uppercase tracking-wider text-fuchsia-300 font-bold">Consumptiekaart geactiveerd</p>
                        <p class="text-2xl font-black text-white mt-0.5">{{ $lastDrinksVakjes }} vakjes doorgestreept</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

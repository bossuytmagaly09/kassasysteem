<div wire:poll.750ms="syncState" 
     class="flex-1 flex flex-col h-full w-full max-w-[768px] mx-auto p-4 sm:p-5 md:p-6 justify-between overflow-hidden relative select-none"
     x-data="{
         init() {
             if (window.BroadcastChannel) {
                 const channel = new BroadcastChannel('kassa_display_channel_' + @js($kassaId));
                 channel.onmessage = (event) => {
                     if (event.data) {
                         $wire.syncState();
                     }
                 };
                 if (@js($kassaId) === '1') {
                     const legacyChannel = new BroadcastChannel('kassa_display_channel');
                     legacyChannel.onmessage = (event) => {
                         if (event.data) {
                             $wire.syncState();
                         }
                     };
                 }
             }
         }
     }">

    <!-- Top Header Bar (Logo, Event name, clock & vakwaarde pills) -->
    <header class="flex items-center justify-between pb-3.5 border-b border-slate-800/80 gap-3 shrink-0">
        <div class="flex items-center gap-3.5 min-w-0">
            <!-- Professioneel, modern kassa logo icoon -->
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-600 p-3 shadow-lg shadow-indigo-500/25 border border-indigo-400/40 flex items-center justify-center shrink-0 text-white overflow-hidden">
                <svg class="w-full h-full drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="11" rx="2" fill="currentColor" fill-opacity="0.2" />
                    <line x1="7" y1="7" x2="11" y2="7" />
                    <line x1="7" y1="10" x2="9" y2="10" />
                    <line x1="15" y1="7" x2="17" y2="7" />
                    <line x1="15" y1="10" x2="17" y2="10" />
                    <line x1="12" y1="14" x2="12" y2="17" />
                    <path d="M4 17h16a2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1a2 2 0 0 1 2-2z" fill="currentColor" fill-opacity="0.25" />
                    <circle cx="12" cy="19.5" r="0.75" fill="currentColor" />
                </svg>
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight truncate capitalize flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span class="truncate">{{ $eventName ?: 'Kassasysteem' }}</span>
                </h1>
            </div>
        </div>

        <!-- Clock & Vakwaarde pills -->
        <div class="flex items-center gap-2.5 shrink-0">
            @if($cardPrice > 0)
                <div class="px-3.5 py-1.5 rounded-xl bg-fuchsia-950/70 border border-fuchsia-500/50 backdrop-blur-md text-sm font-bold text-fuchsia-300 hidden sm:flex items-center gap-1.5 shrink-0 shadow-sm">
                    <span class="text-sm">🎟️</span>
                    <span>Consumptiekaart = € {{ number_format($cardPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            @if($vakPrice > 0)
                <div class="px-3.5 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 backdrop-blur-md text-sm font-semibold text-slate-200 flex items-center gap-1.5 shrink-0 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>1 vakje = € {{ number_format($vakPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50 text-sm font-mono font-bold text-slate-300 shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                 x-text="time">
            </div>
        </div>
    </header>

    <!-- ========================================================================= -->
    <!-- TOP SECTION: AANKOPEN & ACTIEVE BESTELLING / STATUS (Bovenkant van scherm)-->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col justify-center min-h-[380px] my-3 sm:my-4 overflow-hidden">

        <!-- STATE 1: IDLE / WELCOME SCREEN -->
        @if($state === 'idle' || (empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-indigo-500/15 border-2 border-indigo-500/30 flex items-center justify-center mb-4 shadow-2xl shadow-indigo-500/20">
                    <svg class="w-12 h-12 sm:w-14 sm:h-14 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-indigo-200 mb-2 tracking-tight">
                    Welkom bij {{ $eventName ?: 'ons event' }}!
                </h2>

                <p class="text-base sm:text-lg text-slate-300 font-medium max-w-xl mb-6">
                    Bestel gerust aan de bar. Uw bestelling en prijzen verschijnen hier live op het scherm.
                </p>

                <!-- Consumptiekaart Feature Card -->
                <div class="w-full max-w-lg p-5 sm:p-6 rounded-3xl bg-gradient-to-br from-fuchsia-950/70 via-purple-950/50 to-slate-900 border-2 border-fuchsia-500/40 backdrop-blur-md text-left flex items-start gap-4 shadow-xl shadow-fuchsia-950/40">
                    <div class="w-14 h-14 rounded-2xl bg-fuchsia-500/20 text-fuchsia-300 flex items-center justify-center shrink-0 border border-fuchsia-500/40">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <h4 class="text-white font-black text-lg sm:text-xl truncate">Consumptiekaart</h4>
                            <span class="shrink-0 px-3 py-1 rounded-full bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/50 text-sm font-black">
                                € {{ number_format($cardPrice / 100, 2, ',', '.') }}
                            </span>
                        </div>
                        <p class="text-slate-200 text-sm sm:text-base font-medium leading-relaxed">
                            Koop een volle kaart voor <strong class="text-fuchsia-300">€ {{ number_format($cardPrice / 100, 2, ',', '.') }}</strong>
                            ({{ $vakPrice > 0 ? (int) round($cardPrice / $vakPrice) : 20 }} vakjes). Eenvoudig en snel afrekenen per consumptie.
                        </p>
                    </div>
                </div>
            </div>

        <!-- STATE 2: ACTIVE ORDER (CART / AWAITING PAYMENT) -->
        @elseif($state === 'cart' || $state === 'awaiting_payment' || (!empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 flex flex-col justify-between gap-3 sm:gap-4 overflow-hidden">
                
                <!-- Items Box -->
                <div class="flex-1 flex flex-col bg-slate-900/70 border border-slate-800 rounded-3xl p-4 sm:p-5 backdrop-blur-xl shadow-xl overflow-hidden min-h-0">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 shrink-0">
                        <h3 class="text-sm sm:text-base font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2.5">
                            <span>Uw Bestelling</span>
                            <span class="text-xs px-3 py-1 rounded-full bg-indigo-500/25 border border-indigo-400/30 text-indigo-200 font-bold">
                                {{ count($items) }} {{ count($items) === 1 ? 'artikel' : 'artikelen' }}
                            </span>
                        </h3>
                        <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Prijs & Vakjes</span>
                    </div>

                    <!-- Items Scroll Area -->
                    <div class="flex-1 overflow-y-auto space-y-2.5 pr-1 [scrollbar-width:thin]">
                        @foreach($items as $item)
                            <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl bg-slate-800/70 border border-slate-700/70 shadow-sm">
                                <div class="flex items-center gap-3.5 min-w-0 flex-1 pr-2">
                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl {{ !empty($item['is_drankkaart']) ? 'bg-fuchsia-600/30 border-2 border-fuchsia-500/50 text-fuchsia-200' : 'bg-slate-700/80 border-2 border-slate-600 text-white' }} flex items-center justify-center text-lg sm:text-2xl font-black shrink-0 shadow-md">
                                        {{ $item['quantity'] }}x
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-lg sm:text-2xl font-black text-white tracking-tight flex items-center gap-2 truncate">
                                            @if(!empty($item['is_drankkaart']))
                                                <span class="text-fuchsia-400 text-base sm:text-lg">🎟️</span>
                                            @endif
                                            <span class="truncate">{{ $item['name'] }}</span>
                                        </h4>
                                        <div class="text-xs sm:text-sm font-semibold text-slate-400 mt-0.5">
                                            €&nbsp;{{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 ml-3">
                                    <div class="text-xl sm:text-2xl font-black text-white whitespace-nowrap font-mono">
                                        €&nbsp;{{ number_format($item['total'] / 100, 2, ',', '.') }}
                                    </div>
                                    @if(empty($item['is_drankkaart']) && !empty($item['vakjes']))
                                        <div class="text-xs sm:text-sm font-black text-fuchsia-300 whitespace-nowrap bg-fuchsia-950/70 border border-fuchsia-500/50 px-2.5 py-1 rounded-xl mt-1 inline-block">
                                            {{ $item['vakjes'] }} {{ $item['vakjes'] === 1 ? 'vakje' : 'vakjes' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Totals & Payment Prompt Bar -->
                <div class="p-4 sm:p-5 rounded-3xl bg-slate-900/90 border-2 border-slate-700/70 backdrop-blur-xl shadow-2xl shrink-0 flex items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Totaal te voldoen</span>
                        <div class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300 font-mono tracking-tight">
                            € {{ number_format($total / 100, 2, ',', '.') }}
                        </div>
                    </div>

                    @if($vakjes > 0)
                        <div class="p-3 sm:p-3.5 rounded-2xl bg-fuchsia-950/60 border border-fuchsia-500/50 text-right shadow-sm">
                            <span class="text-xs font-bold text-fuchsia-300 block mb-0.5">
                                @if(!empty($hasDrankkaart))
                                    Vakjes voor consumpties:
                                @else
                                    Of consumptiekaart:
                                @endif
                            </span>
                            <span class="text-2xl sm:text-3xl font-black text-fuchsia-300">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje' : 'vakjes' }}
                            </span>
                        </div>
                    @endif

                    @if($awaitingPayment)
                        <div class="px-4 py-2.5 rounded-2xl bg-indigo-500/25 border border-indigo-400/50 text-indigo-200 text-sm font-bold flex items-center gap-2 animate-pulse shadow-md">
                            <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                            <span>Houd kaart tegen scanner</span>
                        </div>
                    @endif
                </div>

            </div>

        <!-- STATE 3: PAYMENT SUCCESS (BEVESTIGING) -->
        @elseif($state === 'success')
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-emerald-500/20 border-4 border-emerald-400 flex items-center justify-center mb-3 shadow-[0_0_40px_rgba(16,185,129,0.4)]">
                    <svg class="w-12 h-12 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <h2 class="text-3xl sm:text-4xl font-black text-white mb-1 tracking-tight">
                    Bedankt voor uw bestelling!
                </h2>
                <p class="text-sm sm:text-base text-slate-300 mb-4">De betaling is succesvol verwerkt.</p>

                @if($lastPaymentMethod === 'scanner')
                    <div class="w-full max-w-lg p-5 sm:p-6 rounded-3xl bg-slate-900/95 border-2 border-indigo-500/60 shadow-2xl mb-2 text-center">
                        @if($scannedPoefName)
                            <span class="text-xs sm:text-sm text-indigo-300 font-bold uppercase tracking-wider block mb-1">Geboekt op poef van:</span>
                            <h3 class="text-2xl sm:text-3xl font-black text-white mb-1">{{ $scannedPoefName }}</h3>
                            @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                                <span class="text-sm text-indigo-300/80 font-medium block mb-3">(Gedeelde poef: {{ $scannedSharedPoefName }})</span>
                            @endif
                        @endif
                        <div class="pt-3 border-t border-indigo-500/30">
                            <span class="text-xs sm:text-sm font-semibold uppercase tracking-wider text-slate-300 block mb-1">Huidig saldo / Totaal uitgegeven vanavond:</span>
                            <span class="text-4xl sm:text-5xl font-black text-emerald-400 font-mono">€ {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @elseif($lastPaymentMethod === 'cash')
                    <div class="w-full max-w-lg p-4 sm:p-5 rounded-3xl bg-slate-900/90 border-2 border-emerald-500/50 text-center shadow-xl">
                        <span class="text-base font-bold text-emerald-300">Contant betaald</span>
                        @if($lastCashChange !== null && $lastCashChange > 0)
                            <div class="text-2xl sm:text-3xl font-black text-white mt-1">
                                Wisselgeld: € {{ number_format($lastCashChange / 100, 2, ',', '.') }}
                            </div>
                        @endif
                    </div>
                @elseif($lastPaymentMethod === 'qr')
                    <div class="w-full max-w-lg p-4 sm:p-5 rounded-3xl bg-slate-900/90 border-2 border-cyan-500/50 text-center shadow-xl">
                        <span class="text-base sm:text-lg font-bold text-cyan-300">Betaald via QR Code (Payconiq)</span>
                    </div>
                @elseif($lastPaymentMethod === 'drankkaart')
                    <div class="w-full max-w-lg p-4 sm:p-5 rounded-3xl bg-fuchsia-950/70 border-2 border-fuchsia-500/50 text-center shadow-xl">
                        <span class="text-base sm:text-lg font-bold text-fuchsia-300">Afgerekend met consumptiekaart</span>
                        @if($vakjes > 0)
                            <div class="text-2xl sm:text-3xl font-black text-white mt-1">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje doorgestreept' : 'vakjes doorgestreept' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        <!-- STATE 4: POEF SALDO CHECK -->
        @elseif($state === 'poef_info')
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-4">
                @if($scannedPoefName)
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-indigo-500/20 border-4 border-indigo-400 flex items-center justify-center mb-3 shadow-[0_0_40px_rgba(99,102,241,0.4)]">
                        <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>

                    <h2 class="text-3xl sm:text-4xl font-black text-white mb-1">Hallo, {{ $scannedPoefName }}!</h2>
                    @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                        <div class="text-sm text-indigo-300 font-semibold mb-2">Poef: {{ $scannedSharedPoefName }}</div>
                    @endif
                    <p class="text-sm text-slate-400 mb-4">Uw kaart is herkend op de kassa.</p>

                    <div class="w-full max-w-lg p-5 sm:p-6 rounded-3xl bg-slate-900/95 border-2 border-emerald-500/60 shadow-2xl text-center mb-2">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 block mb-1.5">Huidig saldo / Totaal uitgegeven vanavond:</span>
                        <div class="text-5xl sm:text-6xl font-black text-emerald-400 font-mono">
                            € {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}
                        </div>
                    </div>
                @else
                    <div class="w-16 h-16 rounded-2xl bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black text-white mb-1">Kaart Niet Gekoppeld</h2>
                    <p class="text-slate-400 text-sm mb-2">{{ $poefInfoError ?: 'Deze kaart is nog niet gekoppeld aan een poef.' }}</p>
                @endif
            </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- BOTTOM SECTION: DRANKEN- EN PRIJZENBORD (Onderkant van scherm, 768x1366) -->
    <!-- ========================================================================= -->
    <div class="h-[48%] min-h-[440px] flex flex-col bg-slate-900/90 border border-slate-800 rounded-3xl p-4 sm:p-5 backdrop-blur-xl shadow-2xl shrink-0 overflow-hidden mt-3">
        
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                <h3 class="text-base sm:text-lg font-black text-white uppercase tracking-wider">Dranken & Prijzenbord</h3>
            </div>
            <div class="text-sm sm:text-base text-slate-300 font-bold">
                1 vakje = <strong class="text-emerald-400 text-base sm:text-lg font-mono">€ {{ number_format($vakPrice / 100, 2, ',', '.') }}</strong>
            </div>
        </div>

        <!-- 2-Column Responsive Product Cards Grid (Grote vakken voor 768x1366 portrait display) -->
        <div class="flex-1 overflow-y-auto space-y-2.5 sm:space-y-3 pr-1 [scrollbar-width:thin]">
            <div class="grid grid-cols-2 gap-3 sm:gap-3.5">
                @forelse($eventProducts as $product)
                    @php
                        $vCost = $vakPrice > 0 ? ($product['price'] / $vakPrice) : 0;
                        $vText = (floor($vCost) == $vCost) 
                            ? ((int)$vCost . ' ' . ((int)$vCost === 1 ? 'vakje' : 'vakjes')) 
                            : (number_format($vCost, 1, ',', '.') . ' vakjes');
                    @endphp
                    <div class="flex items-center justify-between p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-slate-800/80 border border-slate-700/80 hover:border-slate-600 transition-all shadow-md">
                        <div class="min-w-0 pr-2 flex-1">
                            <div class="font-black text-white text-lg sm:text-2xl leading-tight truncate">{{ $product['name'] }}</div>
                            @if(!empty($product['category']))
                                <div class="text-xs sm:text-sm text-slate-400 font-medium truncate mt-1">{{ $product['category'] }}</div>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xl sm:text-2xl font-black text-emerald-400 font-mono tracking-tight">€ {{ number_format($product['price'] / 100, 2, ',', '.') }}</div>
                            <div class="text-xs sm:text-sm font-black text-fuchsia-300 bg-fuchsia-950/90 border border-fuchsia-500/60 px-3 py-1 rounded-xl inline-block mt-1 shadow-sm">{{ $vText }}</div>
                        </div>
                    </div>
                @empty
                    <!-- Fallback if no products yet -->
                    <div class="col-span-2 text-center py-8 text-slate-500 text-base">
                        Drankenlijst wordt geladen...
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bottom Status Bar -->
    <footer class="pt-3 pb-1 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500 font-medium shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span>Live verbonden</span>
        </div>
        <div>
            Klantenscherm &bull; {{ date('Y') }}
        </div>
    </footer>

</div>

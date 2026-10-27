<div wire:poll.750ms="syncState" 
     class="flex-1 flex flex-col h-full w-full max-w-[768px] mx-auto p-3 sm:p-4 md:p-5 justify-between overflow-hidden relative select-none"
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

    <!-- Top Header Bar (Un-squished crisp logo, Event name, clock & vakwaarde pills) -->
    <header class="flex items-center justify-between pb-3 border-b border-slate-800/80 gap-3 shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <!-- Professioneel, modern kassa logo icoon (zonder zwarte randen of vervorming) -->
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-600 p-2.5 shadow-lg shadow-indigo-500/25 border border-indigo-400/40 flex items-center justify-center shrink-0 text-white overflow-hidden">
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
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-300 block leading-none">
                        Kassa Klantenscherm @if($kassaId !== '1') • Kassa {{ $kassaId }} @endif
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight truncate capitalize">{{ $eventName ?: 'Kassasysteem' }}</h1>
            </div>
        </div>

        <!-- Clock & Vakwaarde pills -->
        <div class="flex items-center gap-2 shrink-0">
            @if($cardPrice > 0)
                <div class="px-2.5 py-1 rounded-xl bg-fuchsia-950/60 border border-fuchsia-500/50 backdrop-blur-md text-xs font-bold text-fuchsia-300 hidden sm:flex items-center gap-1.5 shrink-0">
                    <span class="text-xs">🎟️</span>
                    <span>Consumptiekaart = € {{ number_format($cardPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            @if($vakPrice > 0)
                <div class="px-2.5 py-1 rounded-xl bg-slate-800/60 border border-slate-700/60 backdrop-blur-md text-xs font-semibold text-slate-300 flex items-center gap-1.5 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>1 vakje = € {{ number_format($vakPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="px-2.5 py-1 rounded-xl bg-slate-800/40 border border-slate-700/40 text-xs font-mono font-bold text-slate-300 shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                 x-text="time">
            </div>
        </div>
    </header>

    <!-- ========================================================================= -->
    <!-- TOP SECTION: AANKOPEN & ACTIEVE BESTELLING / STATUS (Point 5: Bovenkant)  -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col justify-center min-h-[340px] my-2 sm:my-3 overflow-hidden">

        <!-- STATE 1: IDLE / WELCOME SCREEN -->
        @if($state === 'idle' || (empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-2">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center mb-2 sm:mb-3 shadow-xl shadow-indigo-500/10">
                    <svg class="w-8 h-8 sm:w-9 sm:h-9 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-2xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-indigo-200 mb-1 tracking-tight">
                    Welkom bij {{ $eventName ?: 'ons event' }}!
                </h2>

                <p class="text-sm sm:text-base text-slate-400 font-medium max-w-xl mb-3 sm:mb-4">
                    Bestel gerust aan de bar. Uw bestelling en prijzen verschijnen hier live op het scherm.
                </p>

                <!-- Consumptiekaart Feature Card -->
                <div class="w-full max-w-md p-3.5 sm:p-4 rounded-2xl bg-gradient-to-br from-fuchsia-950/60 to-purple-950/40 border border-fuchsia-500/40 backdrop-blur-md text-left flex items-start gap-3 shadow-lg shadow-fuchsia-950/30">
                    <div class="w-10 h-10 rounded-xl bg-fuchsia-500/20 text-fuchsia-300 flex items-center justify-center shrink-0 border border-fuchsia-500/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1.5 mb-1">
                            <h4 class="text-white font-black text-sm sm:text-base truncate">Consumptiekaart</h4>
                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/50 text-[11px] font-black">
                                € {{ number_format($cardPrice / 100, 2, ',', '.') }}
                            </span>
                        </div>
                        <p class="text-slate-300 text-xs font-medium leading-relaxed">
                            Koop een volle kaart voor <strong class="text-fuchsia-300">€ {{ number_format($cardPrice / 100, 2, ',', '.') }}</strong>
                            ({{ $vakPrice > 0 ? (int) round($cardPrice / $vakPrice) : 20 }} vakjes). Eenvoudig afrekenen per consumptie.
                        </p>
                    </div>
                </div>
            </div>

        <!-- STATE 2: ACTIVE ORDER (CART / AWAITING PAYMENT) -->
        @elseif($state === 'cart' || $state === 'awaiting_payment' || (!empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 flex flex-col justify-between gap-2.5 sm:gap-3 overflow-hidden">
                
                <!-- Items Box -->
                <div class="flex-1 flex flex-col bg-slate-900/60 border border-slate-800 rounded-2xl p-3 sm:p-4 backdrop-blur-xl shadow-xl overflow-hidden min-h-0">
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 shrink-0">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                            <span>Uw Bestelling</span>
                            <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 font-bold">
                                {{ count($items) }} {{ count($items) === 1 ? 'artikel' : 'artikelen' }}
                            </span>
                        </h3>
                        <span class="text-[10px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold">Prijs & Vakjes</span>
                    </div>

                    <!-- Items Scroll Area -->
                    <div class="flex-1 overflow-y-auto space-y-1.5 pr-1 [scrollbar-width:thin]">
                        @foreach($items as $item)
                            <div class="flex items-center justify-between p-2 rounded-xl bg-slate-800/40 border border-slate-700/50">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg {{ !empty($item['is_drankkaart']) ? 'bg-fuchsia-600/30 border border-fuchsia-500/40 text-fuchsia-200' : 'bg-slate-700/60 border border-slate-600 text-white' }} flex items-center justify-center text-xs sm:text-sm font-black shrink-0">
                                        {{ $item['quantity'] }}x
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm sm:text-base font-bold text-white tracking-tight flex items-center gap-1.5 truncate">
                                            @if(!empty($item['is_drankkaart']))
                                                <span class="text-fuchsia-400 text-xs">🎟️</span>
                                            @endif
                                            <span class="truncate">{{ $item['name'] }}</span>
                                        </h4>
                                        <div class="text-[10px] sm:text-xs text-slate-400">
                                            €&nbsp;{{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 ml-2">
                                    <div class="text-sm sm:text-base font-black text-white whitespace-nowrap">
                                        €&nbsp;{{ number_format($item['total'] / 100, 2, ',', '.') }}
                                    </div>
                                    @if(empty($item['is_drankkaart']) && !empty($item['vakjes']))
                                        <div class="text-[10px] sm:text-xs font-bold text-fuchsia-400 whitespace-nowrap">
                                            {{ $item['vakjes'] }} {{ $item['vakjes'] === 1 ? 'vakje' : 'vakjes' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Totals & Payment Prompt Bar -->
                <div class="p-3 sm:p-4 rounded-2xl bg-slate-900/80 border border-slate-700/60 backdrop-blur-xl shadow-xl shrink-0 flex items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Totaal te voldoen</span>
                        <div class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300 font-mono">
                            € {{ number_format($total / 100, 2, ',', '.') }}
                        </div>
                    </div>

                    @if($vakjes > 0)
                        <div class="p-2 sm:p-2.5 rounded-xl bg-fuchsia-950/50 border border-fuchsia-500/40 text-right">
                            <span class="text-[10px] font-bold text-fuchsia-300 block">Of consumptiekaart:</span>
                            <span class="text-xl sm:text-2xl font-black text-fuchsia-400">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje' : 'vakjes' }}
                            </span>
                        </div>
                    @endif

                    @if($awaitingPayment)
                        <div class="px-3 py-1.5 rounded-xl bg-indigo-500/20 border border-indigo-400/50 text-indigo-300 text-xs font-bold flex items-center gap-1.5 animate-pulse">
                            <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                            <span>Houd kaart tegen scanner</span>
                        </div>
                    @endif
                </div>

            </div>

        <!-- STATE 3: PAYMENT SUCCESS (BEVESTIGING) -->
        @elseif($state === 'success')
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-emerald-500/20 border-4 border-emerald-400 flex items-center justify-center mb-2 shadow-[0_0_30px_rgba(16,185,129,0.3)]">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <h2 class="text-2xl sm:text-3xl font-black text-white mb-0.5 tracking-tight">
                    Bedankt voor uw bestelling!
                </h2>
                <p class="text-xs sm:text-sm text-slate-400 mb-3">De betaling is succesvol verwerkt.</p>

                @if($lastPaymentMethod === 'scanner')
                    <div class="w-full max-w-md p-3.5 sm:p-4 rounded-2xl bg-slate-900/90 border-2 border-indigo-500/50 mb-2">
                        @if($scannedPoefName)
                            <span class="text-xs text-indigo-300 font-bold uppercase tracking-wider block mb-0.5">Geboekt op poef van:</span>
                            <h3 class="text-xl sm:text-2xl font-black text-white mb-0.5">{{ $scannedPoefName }}</h3>
                            @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                                <span class="text-xs text-indigo-300/80 font-medium block mb-2">(Gedeelde poef: {{ $scannedSharedPoefName }})</span>
                            @endif
                        @endif
                        <div class="pt-2 border-t border-indigo-500/30">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-300 block mb-0.5">Huidig saldo / Totaal uitgegeven vanavond:</span>
                            <span class="text-3xl sm:text-4xl font-black text-emerald-400 font-mono">€ {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @elseif($lastPaymentMethod === 'cash')
                    <div class="w-full max-w-md p-3 rounded-2xl bg-slate-900/80 border border-emerald-500/40 text-center">
                        <span class="text-sm font-bold text-emerald-300">Contant betaald</span>
                        @if($lastCashChange !== null && $lastCashChange > 0)
                            <div class="text-xl sm:text-2xl font-black text-white mt-0.5">
                                Wisselgeld: € {{ number_format($lastCashChange / 100, 2, ',', '.') }}
                            </div>
                        @endif
                    </div>
                @elseif($lastPaymentMethod === 'qr')
                    <div class="w-full max-w-md p-3 rounded-2xl bg-slate-900/80 border border-cyan-500/40 text-center">
                        <span class="text-sm font-bold text-cyan-300">Betaald via QR Code (Payconiq)</span>
                    </div>
                @elseif($lastPaymentMethod === 'drankkaart')
                    <div class="w-full max-w-md p-3 rounded-2xl bg-fuchsia-950/60 border border-fuchsia-500/40 text-center">
                        <span class="text-sm font-bold text-fuchsia-300">Afgerekend met consumptiekaart</span>
                        @if($vakjes > 0)
                            <div class="text-xl font-black text-white mt-0.5">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje doorgestreept' : 'vakjes doorgestreept' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        <!-- STATE 4: POEF SALDO CHECK -->
        @elseif($state === 'poef_info')
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-3">
                @if($scannedPoefName)
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-indigo-500/20 border-4 border-indigo-400 flex items-center justify-center mb-2 shadow-[0_0_40px_rgba(99,102,241,0.4)]">
                        <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-black text-white mb-0.5">Hallo, {{ $scannedPoefName }}!</h2>
                    @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                        <div class="text-xs text-indigo-300 font-semibold mb-1">Poef: {{ $scannedSharedPoefName }}</div>
                    @endif
                    <p class="text-xs text-slate-400 mb-2 sm:mb-3">Uw kaart is herkend op de kassa.</p>

                    <div class="w-full max-w-md p-4 rounded-2xl bg-slate-900/90 border-2 border-emerald-500/60 shadow-lg text-center mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block mb-1">Huidig saldo / Totaal uitgegeven vanavond:</span>
                        <div class="text-4xl sm:text-5xl font-black text-emerald-400 font-mono">
                            € {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}
                        </div>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 border-2 border-amber-400 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-xl font-black text-white mb-1">Kaart Niet Gekoppeld</h2>
                    <p class="text-slate-400 text-xs mb-2">{{ $poefInfoError ?: 'Deze kaart is nog niet gekoppeld aan een poef.' }}</p>
                @endif
            </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- BOTTOM SECTION: DRANKEN- EN PRIJZENBORD (Point 5: Onderkant permanent)     -->
    <!-- ========================================================================= -->
    <div class="h-[46%] min-h-[380px] flex flex-col bg-slate-900/80 border border-slate-800 rounded-3xl p-3 sm:p-4 backdrop-blur-xl shadow-2xl shrink-0 overflow-hidden">
        
        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <h3 class="text-xs sm:text-sm font-black text-white uppercase tracking-wider">Dranken & Prijzenbord</h3>
            </div>
            <div class="text-[10px] sm:text-xs text-slate-400 font-semibold">
                1 vakje = <strong class="text-emerald-400">€ {{ number_format($vakPrice / 100, 2, ',', '.') }}</strong>
            </div>
        </div>

        <!-- 2-Column Responsive Product Cards Grid -->
        <div class="flex-1 overflow-y-auto space-y-1.5 sm:space-y-2 pr-1 [scrollbar-width:thin]">
            <div class="grid grid-cols-2 gap-2">
                @forelse($eventProducts as $product)
                    @php
                        $vCost = $vakPrice > 0 ? ($product['price'] / $vakPrice) : 0;
                        $vText = (floor($vCost) == $vCost) 
                            ? ((int)$vCost . ' ' . ((int)$vCost === 1 ? 'vakje' : 'vakjes')) 
                            : (number_format($vCost, 1, ',', '.') . ' vakjes');
                    @endphp
                    <div class="flex items-center justify-between p-2 sm:p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/60 hover:border-slate-600 transition-all">
                        <div class="min-w-0 pr-1">
                            <div class="font-bold text-white text-xs sm:text-sm truncate">{{ $product['name'] }}</div>
                            <div class="text-[10px] text-slate-400 truncate">{{ $product['category'] }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs sm:text-sm font-black text-emerald-400">€ {{ number_format($product['price'] / 100, 2, ',', '.') }}</div>
                            <div class="text-[10px] font-bold text-fuchsia-300 bg-fuchsia-950/60 border border-fuchsia-500/40 px-1.5 py-0.5 rounded-md inline-block">{{ $vText }}</div>
                        </div>
                    </div>
                @empty
                    <!-- Fallback if no products yet -->
                    <div class="col-span-2 text-center py-6 text-slate-500 text-xs">
                        Drankenlijst wordt geladen...
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bottom Status Bar -->
    <footer class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 font-medium shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Verbonden met kassa</span>
        </div>
        <div>
            Kassa Systeem &copy; {{ date('Y') }} &bull; Klantendisplay
        </div>
    </footer>

</div>

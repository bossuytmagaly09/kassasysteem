<div wire:poll.750ms="syncState" 
     class="flex-1 flex flex-col h-full w-full p-3 sm:p-5 md:p-8 justify-between overflow-hidden relative select-none"
     x-data="{
         init() {
             if (window.BroadcastChannel) {
                 const channel = new BroadcastChannel('kassa_display_channel');
                 channel.onmessage = (event) => {
                     if (event.data) {
                         $wire.syncState();
                     }
                 };
             }
         }
     }">

    <!-- Top Header Bar (Responsive, zero overlapping, fits down to tablet) -->
    <header class="flex items-center justify-between pb-3 md:pb-4 border-b border-slate-800/80 gap-3 shrink-0">
        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-fuchsia-500 p-0.5 shadow-lg shadow-indigo-500/20 flex items-center justify-center shrink-0">
                <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
            </div>
            <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block leading-none mb-0.5">Kassa Klantenscherm</span>
                <h1 class="text-lg sm:text-xl md:text-2xl font-black text-white tracking-tight truncate">{{ $eventName ?: 'Kassasysteem' }}</h1>
            </div>
        </div>

        <!-- Clock & Vakwaarde pills (shrink-0, clean badges that never wrap onto text) -->
        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
            @if($cardPrice > 0)
                <div class="px-2.5 py-1 md:px-3 md:py-1.5 rounded-xl bg-fuchsia-950/60 border border-fuchsia-500/50 backdrop-blur-md text-xs md:text-sm font-bold text-fuchsia-300 flex items-center gap-1.5 shrink-0">
                    <span class="text-fuchsia-400 text-xs">🎟️</span>
                    <span>Consumptiekaart = € {{ number_format($cardPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            @if($vakPrice > 0)
                <div class="px-2.5 py-1 md:px-3 md:py-1.5 rounded-xl bg-slate-800/60 border border-slate-700/60 backdrop-blur-md text-xs md:text-sm font-semibold text-slate-300 flex items-center gap-1.5 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>1 vakje = € {{ number_format($vakPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="px-2.5 py-1 md:px-3 md:py-1.5 rounded-xl bg-slate-800/40 border border-slate-700/40 text-xs md:text-sm font-mono font-bold text-slate-300 shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                 x-text="time">
            </div>
        </div>
    </header>

    <!-- MAIN DYNAMIC CONTENT -->
    <div class="flex-1 flex flex-col justify-center my-3 md:my-5 overflow-hidden">

        <!-- ============================================================= -->
        <!-- STATE 1: IDLE / WELCOME SCREEN (Fits 100% on tablet & desktop) -->
        <!-- ============================================================= -->
        @if($state === 'idle' || (empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 flex flex-col items-center justify-center text-center max-w-4xl mx-auto px-4">
                <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center mb-3 md:mb-4 shadow-xl shadow-indigo-500/10">
                    <svg class="w-9 h-9 md:w-11 md:h-11 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-indigo-200 mb-2 tracking-tight">
                    Welkom bij {{ $eventName ?: 'ons event' }}!
                </h2>

                <p class="text-base md:text-xl text-slate-400 font-medium max-w-2xl mb-6 md:mb-8">
                    Bestel gerust aan de bar. Uw bestelling en prijzen verschijnen hier live op het scherm.
                </p>

                <!-- Information Pills / Features -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full max-w-3xl">
                    <!-- Consumptiekaart Box met vooraf getoonde kaartprijs! -->
                    <div class="p-3.5 md:p-4 rounded-xl md:rounded-2xl bg-gradient-to-br from-fuchsia-950/60 to-purple-950/40 border border-fuchsia-500/40 backdrop-blur-md text-left flex items-start gap-3 shadow-lg shadow-fuchsia-950/30">
                        <div class="w-10 h-10 rounded-xl bg-fuchsia-500/20 text-fuchsia-300 flex items-center justify-center shrink-0 border border-fuchsia-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1.5 mb-1">
                                <h4 class="text-white font-black text-sm md:text-base truncate">Consumptiekaart</h4>
                                <span class="shrink-0 px-2 py-0.5 rounded-full bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/50 text-[11px] font-black">
                                    € {{ number_format($cardPrice / 100, 2, ',', '.') }}
                                </span>
                            </div>
                            <p class="text-slate-300 text-xs font-medium leading-relaxed">
                                Koop een volle kaart voor <strong class="text-fuchsia-300">€ {{ number_format($cardPrice / 100, 2, ',', '.') }}</strong>
                                ({{ $vakPrice > 0 ? (int) round($cardPrice / $vakPrice) : 20 }} vakjes). Afrekenen per consumptie.
                            </p>
                        </div>
                    </div>

                    <div class="p-3.5 md:p-4 rounded-xl md:rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md text-left flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-500/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm md:text-base mb-1">NFC Kaart / Poef</h4>
                            <p class="text-slate-400 text-xs leading-relaxed">Scan uw kaart bij het afrekenen óf tussendoor om direct uw saldo te bekijken.</p>
                        </div>
                    </div>

                    <div class="p-3.5 md:p-4 rounded-xl md:rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md text-left flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm md:text-base mb-1">Cash & QR</h4>
                            <p class="text-slate-400 text-xs leading-relaxed">Koop consumptiekaarten eenvoudig contant of via Payconiq / QR op de betaaltelefoon.</p>
                        </div>
                    </div>
                </div>
            </div>

        <!-- ============================================================= -->
        <!-- STATE 2: ACTIVE ORDER (CART / AWAITING PAYMENT)               -->
        <!-- ============================================================= -->
        @elseif($state === 'cart' || $state === 'awaiting_payment' || (!empty($items) && $state !== 'success' && $state !== 'poef_info'))
            <div class="flex-1 grid grid-cols-12 gap-4 md:gap-6 h-full overflow-hidden">
                
                <!-- Left: Order Items List (7 cols) -->
                <div class="col-span-7 flex flex-col bg-slate-900/60 border border-slate-800 rounded-2xl md:rounded-3xl p-4 md:p-5 backdrop-blur-xl shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 shrink-0">
                        <h3 class="text-base md:text-lg font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                            <span>Uw Bestelling</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-bold">
                                {{ count($items) }} {{ count($items) === 1 ? 'artikel' : 'artikelen' }}
                            </span>
                        </h3>
                        <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Prijs & Vakjes</span>
                    </div>

                    <!-- Items Scroll Area -->
                    <div class="flex-1 overflow-y-auto space-y-2 pr-1 [scrollbar-width:thin]">
                        @foreach($items as $item)
                            <div class="flex items-center justify-between p-2.5 md:p-3 rounded-xl bg-slate-800/40 border border-slate-700/50 hover:border-slate-600 transition-all">
                                <div class="flex items-center gap-2.5 sm:gap-3">
                                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-700/60 border border-slate-600 flex items-center justify-center text-base sm:text-lg font-black text-white shadow-sm shrink-0">
                                        {{ $item['quantity'] }}x
                                    </div>
                                    <div>
                                        <h4 class="text-base sm:text-lg font-bold text-white tracking-tight flex items-center gap-1.5">
                                            <span>{{ $item['name'] }}</span>
                                            @if(!empty($item['is_drankkaart']))
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/30">Kaart</span>
                                            @endif
                                        </h4>
                                        <div class="text-xs text-slate-400 font-medium">
                                            € {{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="text-lg sm:text-xl font-black text-white tracking-tight">
                                        € {{ number_format($item['total'] / 100, 2, ',', '.') }}
                                    </div>
                                    @if(empty($item['is_drankkaart']) && !empty($item['vakjes']))
                                        <div class="text-xs font-bold text-fuchsia-400">
                                            {{ $item['vakjes'] }} {{ $item['vakjes'] === 1 ? 'vakje' : 'vakjes' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Totals & Payment Instructions (5 cols) -->
                <div class="col-span-5 flex flex-col justify-between gap-3 md:gap-4 overflow-hidden">

                    <!-- Total Card -->
                    <div class="p-4 sm:p-5 md:p-6 rounded-2xl md:rounded-3xl bg-slate-900/80 border border-slate-700/60 backdrop-blur-xl shadow-2xl shrink-0">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Totaal te voldoen</span>
                        
                        <div class="flex items-baseline justify-between mb-2">
                            <span class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                                € {{ number_format($total / 100, 2, ',', '.') }}
                            </span>
                        </div>

                        <!-- Vakjes Badge if applicable -->
                        @if($vakjes > 0)
                            <div class="p-2.5 sm:p-3 rounded-xl bg-fuchsia-950/40 border border-fuchsia-500/40 flex items-center justify-between">
                                <span class="text-xs sm:text-sm font-bold text-fuchsia-300">Of consumptiekaart:</span>
                                <span class="text-xl sm:text-2xl font-black text-fuchsia-400">
                                    {{ $vakjes }} {{ $vakjes === 1 ? 'vakje' : 'vakjes' }}
                                </span>
                            </div>
                        @endif

                        <!-- Consumptiekaart Purchase Breakdown if mixed -->
                        @if($hasDrankkaart)
                            <div class="mt-2.5 pt-2.5 border-t border-slate-800 space-y-1 text-xs">
                                <div class="flex justify-between text-slate-300">
                                    <span>Consumptiekaart aankoop:</span>
                                    <span class="font-bold text-fuchsia-300">€ {{ number_format($cardTotal / 100, 2, ',', '.') }}</span>
                                </div>
                                @if($drinksTotal > 0)
                                    <div class="flex justify-between text-slate-300">
                                        <span>Consumpties op nieuwe kaart:</span>
                                        <span class="font-bold text-emerald-300">{{ $drinksVakjes }} vakjes</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Payment Prompt Box -->
                    <div class="flex-1 p-4 sm:p-5 rounded-2xl md:rounded-3xl bg-slate-900/60 border border-indigo-500/30 flex flex-col items-center justify-center text-center shadow-xl relative overflow-hidden">
                        
                        @if($awaitingPayment)
                            <!-- Scanning NFC badge -->
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 mb-2 sm:mb-3">
                                <span class="absolute inset-0 rounded-full bg-indigo-500/30 animate-ping"></span>
                                <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-indigo-500/20 flex items-center justify-center border-4 border-indigo-400/60">
                                    <svg class="w-8 h-8 sm:w-10 sm:h-10 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                                </div>
                            </div>
                            <h3 class="text-lg sm:text-xl font-black text-indigo-300 mb-0.5">Houd uw kaart tegen de scanner</h3>
                            <p class="text-xs text-slate-400">Wordt automatisch geregistreerd op uw poef.</p>

                        @elseif($cardPaymentMethod === 'qr' && $hasDrankkaart)
                            <!-- QR Payment view on customer screen -->
                            <div class="bg-white p-2.5 rounded-xl max-w-[100px] mx-auto mb-2 shadow-lg">
                                <div class="w-18 h-18 border-2 border-slate-900 rounded-lg p-1 flex flex-col justify-between">
                                    <div class="flex justify-between">
                                        <div class="w-4 h-4 bg-slate-900 rounded-sm"></div>
                                        <div class="w-4 h-4 bg-slate-900 rounded-sm"></div>
                                    </div>
                                    <div class="text-slate-900 font-black text-[8px] tracking-wider text-center">PAYCONIQ</div>
                                    <div class="flex justify-between items-end">
                                        <div class="w-4 h-4 bg-slate-900 rounded-sm"></div>
                                        <div class="w-3 h-3 bg-slate-900 rounded-sm"></div>
                                    </div>
                                </div>
                            </div>
                            <h3 class="text-base sm:text-lg font-black text-cyan-300 mb-0.5">Scan met Payconiq of Bank App</h3>
                            <p class="text-xs text-slate-400">Te betalen voor consumptiekaart: <strong>€ {{ number_format($cardTotal / 100, 2, ',', '.') }}</strong></p>

                        @elseif($hasDrankkaart && $cardPaymentMethod === 'cash')
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <h3 class="text-lg sm:text-xl font-black text-emerald-300 mb-0.5">Contant afrekenen</h3>
                            <p class="text-xs text-slate-400">Betaal de consumptiekaart contant aan de bar medewerker.</p>

                        @else
                            <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center mb-2">
                                <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-slate-300 mb-0.5">Bestelling wordt klaargemaakt</h3>
                            <p class="text-xs text-slate-400">Meld aan de bar hoe u wenst af te rekenen.</p>
                        @endif

                    </div>

                </div>

            </div>

        <!-- ============================================================= -->
        <!-- STATE 3: ORDER COMPLETED / SUCCESS SCREEN                     -->
        <!-- ============================================================= -->
        @elseif($state === 'success')
            <div class="flex-1 flex flex-col items-center justify-center text-center max-w-2xl mx-auto px-4 py-4"
                 x-data="{ show: false }" x-init="requestAnimationFrame(() => show = true)">

                <!-- Animated Success Badge -->
                <div class="w-20 h-20 md:w-28 md:h-28 rounded-full bg-emerald-500/20 border-4 border-emerald-400 flex items-center justify-center mb-3 md:mb-4 shadow-[0_0_50px_-10px_rgba(16,185,129,0.5)] transition-all duration-500"
                     :class="show ? 'scale-100 opacity-100' : 'scale-50 opacity-0'">
                    <svg class="w-10 h-10 md:w-14 md:h-14 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h2 class="text-3xl md:text-5xl font-black text-white mb-1 md:mb-2 tracking-tight">
                    Bedankt voor uw bestelling!
                </h2>

                <p class="text-base md:text-lg text-slate-300 mb-4 md:mb-6">
                    Bestelling succesvol afgerond.
                </p>

                <!-- POEF KOPPELING SALDO OVERZICHT -->
                @if($scannedPoefName || $poefTotalSpent !== null)
                    <div class="w-full p-4 md:p-6 rounded-2xl bg-indigo-950/60 border-2 border-indigo-500/50 shadow-2xl backdrop-blur-xl mb-4 text-center transition-all duration-300">
                        <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 text-[11px] font-bold uppercase tracking-wider mb-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>Geregistreerd op Poef</span>
                        </div>

                        @if($scannedPoefName)
                            <h3 class="text-2xl md:text-3xl font-black text-white mb-2 tracking-tight">
                                {{ $scannedPoefName }}
                            </h3>
                        @endif

                        <div class="pt-2 border-t border-indigo-500/30">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-300 block mb-0.5">
                                Huidig saldo / Totaal uitgegeven vanavond:
                            </span>
                            <span class="text-3xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                                € {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                @elseif($lastPaymentMethod === 'cash')
                    <div class="w-full p-4 md:p-5 rounded-2xl bg-slate-900/80 border border-emerald-500/40 mb-4">
                        <span class="text-base md:text-lg font-bold text-emerald-300">Contant betaald</span>
                        @if($lastCashChange !== null && $lastCashChange > 0)
                            <div class="mt-1 text-xl md:text-2xl font-black text-white">
                                Wisselgeld: € {{ number_format($lastCashChange / 100, 2, ',', '.') }}
                            </div>
                        @endif
                    </div>

                @elseif($lastPaymentMethod === 'qr')
                    <div class="w-full p-4 md:p-5 rounded-2xl bg-slate-900/80 border border-cyan-500/40 mb-4">
                        <span class="text-lg md:text-xl font-bold text-cyan-300">Betaald via QR Code (Payconiq)</span>
                    </div>

                @elseif($lastPaymentMethod === 'drankkaart')
                    <div class="w-full p-4 md:p-5 rounded-2xl bg-fuchsia-950/60 border border-fuchsia-500/40 mb-4">
                        <span class="text-lg md:text-xl font-bold text-fuchsia-300">Afgerekend met consumptiekaart</span>
                        @if($vakjes > 0)
                            <div class="mt-1 text-xl md:text-2xl font-black text-white">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje doorgestreept' : 'vakjes doorgestreept' }}
                            </div>
                        @endif
                    </div>
                @endif

                @if($lastDrinksVakjes !== null && $lastDrinksVakjes > 0)
                    <div class="px-4 py-2 rounded-xl bg-fuchsia-900/40 border border-fuchsia-500/30 text-fuchsia-200 text-xs md:text-sm font-bold">
                        Nieuwe consumptiekaart geactiveerd: {{ $lastDrinksVakjes }} vakjes direct verrekend.
                    </div>
                @endif

            </div>

        <!-- ============================================================= -->
        <!-- STATE 4: POEF SALDO CHECK (Gescand zonder transactie)          -->
        <!-- ============================================================= -->
        @elseif($state === 'poef_info')
            <div class="flex-1 flex flex-col items-center justify-center text-center max-w-2xl mx-auto px-4 py-4"
                 x-data="{ show: false }" x-init="requestAnimationFrame(() => show = true)">

                @if($scannedPoefName)
                    <!-- Poef Profile Icon -->
                    <div class="w-18 h-18 md:w-24 md:h-24 rounded-2xl md:rounded-3xl bg-indigo-500/20 border-4 border-indigo-400 flex items-center justify-center mb-3 md:mb-4 shadow-[0_0_50px_-10px_rgba(99,102,241,0.5)] transition-all duration-500"
                         :class="show ? 'scale-100 opacity-100' : 'scale-50 opacity-0'">
                        <svg class="w-9 h-9 md:w-12 md:h-12 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-300 text-xs font-bold uppercase tracking-wider mb-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        <span>Poef Saldo Overzicht</span>
                    </div>

                    <h2 class="text-3xl md:text-5xl font-black text-white mb-1 md:mb-2 tracking-tight">
                        Hallo, {{ $scannedPoefName }}!
                    </h2>

                    <p class="text-xs sm:text-sm text-slate-400 mb-4 md:mb-6">
                        Uw kaart is herkend op de kassa.
                    </p>

                    <div class="w-full p-5 md:p-6 rounded-2xl md:rounded-3xl bg-slate-900/90 border-2 border-emerald-500/60 shadow-[0_0_40px_-10px_rgba(16,185,129,0.3)] backdrop-blur-xl mb-4 text-center">
                        <span class="text-xs md:text-sm font-bold uppercase tracking-wider text-slate-300 block mb-1">
                            Huidig saldo / Totaal uitgegeven vanavond:
                        </span>
                        <div class="text-5xl md:text-7xl font-black text-emerald-400 font-mono tracking-tight drop-shadow-[0_0_30px_rgba(52,211,153,0.5)]">
                            € {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-500">
                        Dit overzicht verdwijnt automatisch na enkele seconden.
                    </p>
                @else
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-amber-500/20 border-4 border-amber-400 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 md:w-10 md:h-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>

                    <h2 class="text-2xl md:text-3xl font-black text-white mb-1 md:mb-2">Kaart Niet Gekoppeld</h2>
                    <p class="text-slate-400 text-sm mb-4">{{ $poefInfoError ?: 'Deze kaart is nog niet gekoppeld aan een poef.' }}</p>
                    <p class="text-xs text-slate-500">Vraag aan de bar medewerker om uw kaart te koppelen.</p>
                @endif
            </div>
        @endif

    </div>

    <!-- Bottom Status Bar -->
    <footer class="pt-2 md:pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] md:text-xs text-slate-500 font-medium shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Verbonden met kassa</span>
        </div>
        <div>
            Kassa Systeem &copy; {{ date('Y') }} &bull; Klantendisplay
        </div>
    </footer>

</div>

<div wire:poll.750ms="syncState" 
     class="flex-1 flex flex-col h-full w-full max-w-[768px] mx-auto p-4 sm:p-6 md:p-8 justify-between overflow-hidden relative select-none"
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
    <header class="flex items-center justify-between pb-4 border-b border-slate-800/80 gap-3 shrink-0">
        <div class="flex items-center gap-3 min-w-0">
            <!-- Professioneel, modern kassa logo icoon -->
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-600 p-2.5 shadow-lg shadow-indigo-500/25 border border-indigo-400/40 flex items-center justify-center shrink-0 text-white overflow-hidden">
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
                <h1 class="text-lg sm:text-xl font-black text-white tracking-tight capitalize flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span class="truncate">{{ $eventName ?: 'Kassasysteem' }}</span>
                </h1>
            </div>
        </div>

        <!-- Clock & Vakwaarde pills -->
        <div class="flex items-center gap-2 shrink-0">
            @if($cardPrice > 0)
                <div class="px-3 py-1.5 rounded-xl bg-fuchsia-950/70 border border-fuchsia-500/50 backdrop-blur-md text-xs sm:text-sm font-bold text-fuchsia-300 hidden md:flex items-center gap-1.5 shrink-0 shadow-sm">
                    <span class="text-xs">🎟️</span>
                    <span>Kaart: € {{ number_format($cardPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            @if($vakPrice > 0)
                <div class="px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 backdrop-blur-md text-xs sm:text-sm font-semibold text-slate-200 flex items-center gap-1.5 shrink-0 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>1 vakje = € {{ number_format($vakPrice / 100, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="px-3 py-1.5 rounded-xl bg-slate-800/50 border border-slate-700/50 text-xs sm:text-sm font-mono font-bold text-slate-300 shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                 x-text="time">
            </div>
        </div>
    </header>

    <!-- ========================================================================= -->
    <!-- MAIN SECTION: VOLLEDIGE RUIMTE VOOR BESTELLING OF STATUS                  -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col justify-between my-4 min-h-0 relative">

        <!-- STATE 1: PAYMENT SUCCESS (BEVESTIGING) -->
        @if($state === 'success')
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-6 my-auto">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-emerald-500/20 border-4 border-emerald-400 flex items-center justify-center mb-4 shadow-[0_0_50px_rgba(16,185,129,0.4)]">
                    <svg class="w-14 h-14 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>

                <h2 class="text-3xl sm:text-5xl font-black text-white mb-2 tracking-tight">
                    Bedankt voor uw bestelling!
                </h2>
                <p class="text-base sm:text-lg text-slate-300 mb-6">De betaling is succesvol verwerkt.</p>

                @if($lastPaymentMethod === 'scanner')
                    <div class="w-full max-w-xl p-6 sm:p-8 rounded-3xl bg-slate-900/95 border-2 border-indigo-500/60 shadow-2xl mb-2 text-center">
                        @if($scannedPoefName)
                            <span class="text-xs sm:text-sm text-indigo-300 font-bold uppercase tracking-wider block mb-1">Geboekt op poef van:</span>
                            <h3 class="text-3xl sm:text-4xl font-black text-white mb-1">{{ $scannedPoefName }}</h3>
                            @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                                <span class="text-base text-indigo-300/80 font-medium block mb-4">(Gedeelde poef: {{ $scannedSharedPoefName }})</span>
                            @else
                                <div class="mb-3"></div>
                            @endif
                        @endif
                        <div class="pt-4 border-t border-indigo-500/30">
                            <span class="text-xs sm:text-sm font-semibold uppercase tracking-wider text-slate-300 block mb-1.5">Huidig saldo / Totaal uitgegeven vanavond:</span>
                            <span class="text-5xl sm:text-6xl font-black text-emerald-400 font-mono">€ {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @elseif($lastPaymentMethod === 'cash')
                    <div class="w-full max-w-xl p-6 sm:p-8 rounded-3xl bg-slate-900/90 border-2 border-emerald-500/50 text-center shadow-xl">
                        <span class="text-lg font-bold text-emerald-300">Contant betaald</span>
                        @if($lastCashChange !== null && $lastCashChange > 0)
                            <div class="text-3xl sm:text-4xl font-black text-white mt-2">
                                Wisselgeld: € {{ number_format($lastCashChange / 100, 2, ',', '.') }}
                            </div>
                        @endif
                    </div>
                @elseif($lastPaymentMethod === 'qr')
                    <div class="w-full max-w-xl p-6 sm:p-8 rounded-3xl bg-slate-900/90 border-2 border-cyan-500/50 text-center shadow-xl">
                        <span class="text-xl sm:text-2xl font-bold text-cyan-300">Betaald via QR Code (Payconiq)</span>
                    </div>
                @elseif($lastPaymentMethod === 'drankkaart')
                    <div class="w-full max-w-xl p-6 sm:p-8 rounded-3xl bg-fuchsia-950/70 border-2 border-fuchsia-500/50 text-center shadow-xl">
                        <span class="text-xl sm:text-2xl font-bold text-fuchsia-300">Afgerekend met consumptiekaart</span>
                        @if($vakjes > 0)
                            <div class="text-3xl sm:text-4xl font-black text-white mt-2">
                                {{ $vakjes }} {{ $vakjes === 1 ? 'vakje doorgestreept' : 'vakjes doorgestreept' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        <!-- STATE 2: ACTIVE ORDER (CART / AWAITING PAYMENT) - VOLLEDIGE RUIMTE ZONDER SCROLLEN -->
        @elseif(!empty($items))
            <div class="flex-1 flex flex-col justify-between gap-4">
                
                <!-- Items Container (Bouwt netjes naar beneden toe, geen scrollbar) -->
                <div class="flex-1 flex flex-col">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800 shrink-0">
                        <h3 class="text-base sm:text-lg font-black text-white uppercase tracking-wider flex items-center gap-3">
                            <span>Uw Bestelling</span>
                            <span class="text-xs sm:text-sm px-3.5 py-1 rounded-full bg-indigo-500/25 border border-indigo-400/30 text-indigo-200 font-bold">
                                {{ count($items) }} {{ count($items) === 1 ? 'artikel' : 'artikelen' }}
                            </span>
                        </h3>
                        <span class="text-xs sm:text-sm text-slate-400 uppercase tracking-wider font-semibold">Prijs & Vakjes</span>
                    </div>

                    <!-- Items List (Geen scrollen, bouwt naar beneden toe) -->
                    <div class="space-y-3 sm:space-y-3.5">
                        @foreach($items as $item)
                            <div class="flex items-center justify-between p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-slate-900/85 border border-slate-800 shadow-md">
                                <div class="flex items-center gap-4 min-w-0 flex-1 pr-3">
                                    <div class="w-13 h-13 sm:w-16 sm:h-16 rounded-2xl {{ !empty($item['is_drankkaart']) ? 'bg-fuchsia-600/30 border-2 border-fuchsia-500/50 text-fuchsia-200' : 'bg-slate-800 border-2 border-slate-700 text-white' }} flex items-center justify-center text-xl sm:text-2xl font-black shrink-0 shadow-md">
                                        {{ $item['quantity'] }}x
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2 truncate">
                                            @if(!empty($item['is_drankkaart']))
                                                <span class="text-fuchsia-400 text-xl">🎟️</span>
                                            @endif
                                            <span class="truncate">{{ $item['name'] }}</span>
                                        </h4>
                                        <div class="text-sm font-semibold text-slate-400 mt-1">
                                            €&nbsp;{{ number_format($item['price'] / 100, 2, ',', '.') }} p.s.
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 ml-3">
                                    <div class="text-2xl sm:text-3xl font-black text-white whitespace-nowrap font-mono">
                                        €&nbsp;{{ number_format($item['total'] / 100, 2, ',', '.') }}
                                    </div>
                                    @if(empty($item['is_drankkaart']) && !empty($item['vakjes']))
                                        <div class="text-sm sm:text-base font-black text-fuchsia-300 whitespace-nowrap bg-fuchsia-950/80 border border-fuchsia-500/60 px-3 py-1 rounded-xl mt-1.5 inline-block shadow-sm">
                                            {{ $item['vakjes'] }} {{ $item['vakjes'] === 1 ? 'vakje' : 'vakjes' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Totals & Payment Prompt Bar (Onderaan de bestelling behouden) -->
                <div class="p-5 sm:p-6 rounded-3xl bg-slate-900/95 border-2 border-slate-700/80 backdrop-blur-xl shadow-2xl shrink-0 flex items-center justify-between gap-4 mt-auto">
                    <div>
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-400 block mb-1">Totaal te voldoen</span>
                        <div class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300 font-mono tracking-tight">
                            € {{ number_format($total / 100, 2, ',', '.') }}
                        </div>
                    </div>

                    @if($vakjes > 0)
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-fuchsia-950/70 border border-fuchsia-500/60 text-right shadow-sm">
                            <span class="text-xs sm:text-sm font-bold text-fuchsia-300 block mb-0.5">
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
                        <div class="px-5 py-3 rounded-2xl bg-indigo-500/25 border border-indigo-400/50 text-indigo-200 text-sm sm:text-base font-bold flex items-center gap-2.5 animate-pulse shadow-md">
                            <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                            <span>Houd kaart tegen scanner</span>
                        </div>
                    @endif
                </div>

            </div>

        <!-- STATE 3: IDLE / WELCOME SCREEN (RUSTSTAND) -->
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center px-4 py-8 my-auto">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-3xl bg-indigo-500/15 border-2 border-indigo-500/30 flex items-center justify-center mb-6 shadow-2xl shadow-indigo-500/20">
                    <svg class="w-14 h-14 sm:w-16 sm:h-16 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-indigo-200 mb-3 tracking-tight">
                    Welkom bij {{ $eventName ?: 'ons event' }}!
                </h2>

                <p class="text-lg sm:text-xl text-slate-300 font-medium max-w-xl mb-8">
                    Bestel gerust aan de bar. Uw bestelling en prijzen verschijnen hier live op het scherm.
                </p>

                <!-- Consumptiekaart Feature Card -->
                <div class="w-full max-w-xl p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-fuchsia-950/70 via-purple-950/50 to-slate-900 border-2 border-fuchsia-500/40 backdrop-blur-md text-left flex items-start gap-5 shadow-2xl shadow-fuchsia-950/40">
                    <div class="w-16 h-16 rounded-2xl bg-fuchsia-500/20 text-fuchsia-300 flex items-center justify-center shrink-0 border border-fuchsia-500/40">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h4 class="text-white font-black text-xl sm:text-2xl truncate">Consumptiekaart</h4>
                            <span class="shrink-0 px-3.5 py-1 rounded-full bg-fuchsia-500/30 text-fuchsia-200 border border-fuchsia-400/50 text-sm font-black">
                                € {{ number_format($cardPrice / 100, 2, ',', '.') }}
                            </span>
                        </div>
                        <p class="text-slate-200 text-base sm:text-lg font-medium leading-relaxed">
                            Koop een volle kaart voor <strong class="text-fuchsia-300">€ {{ number_format($cardPrice / 100, 2, ',', '.') }}</strong>
                            ({{ $vakPrice > 0 ? (int) round($cardPrice / $vakPrice) : 20 }} vakjes). Eenvoudig en snel afrekenen per consumptie.
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- POEF SALDO CHECK MODAL OVERLAY POPUP (Bestelling blijft op achtergrond)   -->
    <!-- Geen avatar icoontje, sluit automatisch na 5 seconden                     -->
    <!-- ========================================================================= -->
    @if($showPoefModal)
        <div wire:key="poef-modal-overlay"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4"
             x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => { show = false; $wire.closePoefModal(); }, 5000)">
            <div class="w-full max-w-lg p-6 sm:p-8 rounded-3xl bg-slate-900 border-2 border-indigo-500/70 shadow-[0_0_50px_rgba(99,102,241,0.3)] text-center relative">
                @if($scannedPoefName)
                    <h2 class="text-3xl sm:text-4xl font-black text-white mb-1.5">
                        Hallo, {{ $scannedPoefName }}!
                    </h2>
                    @if(!empty($scannedSharedPoefName) && $scannedSharedPoefName !== $scannedPoefName)
                        <div class="text-sm sm:text-base text-indigo-300 font-semibold mb-4">
                            Poef: {{ $scannedSharedPoefName }}
                        </div>
                    @else
                        <div class="mb-4"></div>
                    @endif

                    <div class="w-full p-6 sm:p-7 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-inner mb-4">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 block mb-2">
                            Huidig saldo / Totaal uitgegeven vanavond:
                        </span>
                        <div class="text-5xl sm:text-6xl font-black text-emerald-400 font-mono tracking-tight">
                            € {{ number_format(($poefTotalSpent ?? 0) / 100, 2, ',', '.') }}
                        </div>
                    </div>

                    <p class="text-xs text-slate-400 font-medium">
                        Uw bestelling blijft op de achtergrond bewaard &bull; Sluit automatisch
                    </p>
                @else
                    <h2 class="text-2xl sm:text-3xl font-black text-amber-400 mb-2">
                        Kaart Niet Gekoppeld
                    </h2>
                    <p class="text-slate-300 text-sm sm:text-base mb-4">
                        {{ $poefInfoError ?: 'Deze kaart is nog niet gekoppeld aan een poef.' }}
                    </p>
                    <p class="text-xs text-slate-400">
                        Sluit automatisch over enkele seconden...
                    </p>
                @endif
            </div>
        </div>
    @endif

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

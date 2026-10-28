<div wire:poll.5s="loadEventData"
     class="flex-1 flex flex-col h-full w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 justify-between overflow-hidden relative select-none">

    <!-- Top Header Bar -->
    <header class="flex flex-wrap items-center justify-between pb-4 sm:pb-6 border-b border-slate-800/80 gap-3 shrink-0">
        <div class="flex items-center gap-3.5 min-w-0">
            <a href="{{ route('dashboard') }}" 
               class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white text-xs sm:text-sm font-bold transition-all flex items-center gap-2 shadow-sm"
               title="Terug naar Hoofdmenu">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Hoofdmenu</span>
            </a>

            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs sm:text-sm font-black uppercase tracking-wider text-emerald-400 block leading-none">
                        Live Prijskaart
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight truncate capitalize">
                    {{ $eventName ?: 'Kassasysteem' }}
                </h1>
            </div>
        </div>

        <!-- Clock & Price Indicators -->
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
            @if($cardPrice > 0)
                <div class="px-4 py-2 rounded-2xl bg-fuchsia-950/70 border border-fuchsia-500/50 backdrop-blur-md text-xs sm:text-sm font-bold text-fuchsia-300 flex items-center gap-2 shadow-sm">
                    <span class="text-base">🎟️</span>
                    <span>Consumptiekaart = <strong class="text-white text-sm sm:text-base">€ {{ number_format($cardPrice / 100, 2, ',', '.') }}</strong></span>
                </div>
            @endif
            @if($vakPrice > 0)
                <div class="px-4 py-2 rounded-2xl bg-slate-800/80 border border-slate-700/60 backdrop-blur-md text-xs sm:text-sm font-semibold text-slate-200 flex items-center gap-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>1 vakje = <strong class="text-emerald-400 font-mono text-sm sm:text-base">€ {{ number_format($vakPrice / 100, 2, ',', '.') }}</strong></span>
                </div>
            @endif
            <div class="px-3.5 py-2 rounded-2xl bg-slate-800/50 border border-slate-700/50 text-xs sm:text-sm font-mono font-bold text-slate-300 shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }), 1000)"
                 x-text="time">
            </div>
        </div>
    </header>

    <!-- Main Content Area: Spacious Price Grid -->
    <main class="flex-1 my-4 sm:my-6 overflow-y-auto [scrollbar-width:thin] pr-1">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4 lg:gap-5">
            @forelse($eventProducts as $product)
                <div class="flex items-center justify-between p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-slate-900/85 border border-slate-800 hover:border-slate-700 backdrop-blur-xl shadow-lg transition-all {{ $product['sold_out'] ? 'opacity-50 grayscale' : '' }}">
                    <div class="min-w-0 pr-3 flex-1">
                        <div class="font-black text-white text-lg sm:text-2xl leading-snug truncate">
                            {{ $product['name'] }}
                        </div>
                        @if($product['sold_out'])
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-lg bg-red-500/20 text-red-400 text-xs font-bold border border-red-500/30 uppercase tracking-wider">
                                Uitverkocht
                            </span>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono tracking-tight">
                            € {{ number_format($product['price'] / 100, 2, ',', '.') }}
                        </div>
                        <div class="text-xs sm:text-sm font-black text-fuchsia-300 bg-fuchsia-950/80 border border-fuchsia-500/60 px-3 py-1 rounded-xl inline-block mt-1 shadow-sm">
                            {{ $product['vakjes_text'] }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-20 text-slate-500 text-base sm:text-lg">
                    Geen dranken of producten beschikbaar voor dit evenement.
                </div>
            @endforelse
        </div>
    </main>

    <!-- Bottom Status Bar -->
    <footer class="pt-3 pb-1 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500 font-medium shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Live prijzenbord &bull; Prijzen worden automatisch bijgewerkt</span>
        </div>
        <div>
            Kassa Systeem &copy; {{ date('Y') }}
        </div>
    </footer>

</div>

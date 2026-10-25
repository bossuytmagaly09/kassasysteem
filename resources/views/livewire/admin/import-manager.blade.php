<div>
    @section('title', 'Beheer Koppeling & Import')

    <div class="max-w-5xl space-y-8">

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Producten / Items</span>
                <span class="text-3xl font-black text-white">{{ $productCount }}</span>
            </div>
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Personen met Poef</span>
                <span class="text-3xl font-black text-indigo-400">{{ $peopleCount }}</span>
            </div>
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Gekoppelde Kaarten</span>
                <span class="text-3xl font-black text-fuchsia-400">{{ $cardsCount }}</span>
            </div>
            <div class="p-5 rounded-2xl bg-slate-900/60 border {{ $pendingSalesCount > 0 ? 'border-amber-500/50 bg-amber-950/20' : 'border-slate-800' }}">
                <span class="text-xs font-bold uppercase tracking-wider {{ $pendingSalesCount > 0 ? 'text-amber-400' : 'text-slate-400' }} block mb-1">Wachtrij Verkopen</span>
                <span class="text-3xl font-black {{ $pendingSalesCount > 0 ? 'text-amber-300' : 'text-emerald-400' }}">{{ $pendingSalesCount }}</span>
            </div>
        </div>

        <!-- Beheer Hub Card -->
        <div class="bg-indigo-900/20 border border-indigo-500/30 rounded-3xl p-8 backdrop-blur-xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl font-black text-white tracking-tight mb-1">Kassa ↔ Beheer Koppeling</h3>
                    <p class="text-sm text-indigo-200/70">
                        Synchroniseert met <code class="text-indigo-300 font-mono bg-indigo-950/80 px-2 py-0.5 rounded">{{ config('beheer.base_url') }}</code>
                    </p>
                </div>

                <!-- Ping / Test button -->
                <button wire:click="checkConnection" 
                        class="px-5 py-2.5 rounded-xl border border-indigo-400/30 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-200 text-sm font-bold flex items-center gap-2 transition-all shrink-0">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    <span>Verbinding Testen (Ping)</span>
                </button>
            </div>

            @if($pingStatus)
                <div class="p-4 rounded-xl mb-6 text-sm font-mono {{ str_contains($pingStatus, 'Fout') ? 'bg-red-950/50 border border-red-500/40 text-red-300' : 'bg-emerald-950/50 border border-emerald-500/40 text-emerald-300' }}">
                    {{ $pingStatus }}
                </div>
            @endif

            <div class="flex flex-wrap gap-4">
                <!-- Sync Snapshot Button -->
                <button wire:click="sync"
                        class="flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-fuchsia-600 hover:from-indigo-500 hover:to-fuchsia-500 text-white px-8 py-4 rounded-2xl font-black text-lg transition-all shadow-xl shadow-indigo-600/30 disabled:opacity-50"
                        wire:loading.attr="disabled"
                        wire:target="sync">
                    <svg wire:loading.remove wire:target="sync" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <svg wire:loading wire:target="sync" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Snapshot Ophalen (Events, Items & Kaarten)</span>
                </button>

                <!-- Push Pending Sales Button -->
                @if($pendingSalesCount > 0)
                    <button wire:click="pushSales"
                            class="flex items-center gap-3 bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-4 rounded-2xl font-black text-lg transition-all shadow-xl shadow-emerald-600/30 disabled:opacity-50"
                            wire:loading.attr="disabled"
                            wire:target="pushSales">
                        <svg wire:loading.remove wire:target="pushSales" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <svg wire:loading wire:target="pushSales" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>{{ $pendingSalesCount }} Verkopen Nu Doorsturen</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Logboek -->
        @if(count($logs) > 0)
            <div class="bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
                <div class="bg-slate-900/80 px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                    <span class="text-slate-300 font-mono text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Synchronisatie Logboek
                    </span>
                    <span class="text-slate-500 text-xs font-mono">{{ count($logs) }} regels</span>
                </div>
                <div class="p-6 font-mono text-xs space-y-2 max-h-80 overflow-y-auto">
                    @foreach(array_reverse($logs) as $log)
                        <div class="{{ str_contains($log, 'FOUT') || str_contains($log, '✗') ? 'text-red-400' : 'text-emerald-400' }}">
                            {{ $log }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

<div>
    @section('title', 'Events Import')

    <div class="max-w-4xl">
        <div class="bg-indigo-900/30 border border-indigo-500/30 rounded-2xl p-6 mb-8">
            <h3 class="text-xl font-bold text-indigo-300 mb-2">Importeer events & producten</h3>
            <p class="text-indigo-200/70 mb-6">Met deze knop haal je events, producten en prijzen op uit het externe beheerspaneel. Je kan een event ook zelf aanmaken via 'Events & Voorraad'.</p>
            
            <button wire:click="sync" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all shadow-lg shadow-indigo-500/30 w-full md:w-auto justify-center" wire:loading.class="opacity-50 cursor-not-allowed">
                <svg wire:loading.remove wire:target="sync" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <svg wire:loading wire:target="sync" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Synchronisatie Starten</span>
            </button>
        </div>

        @if(count($logs) > 0)
        <div class="bg-slate-950 border border-slate-800 rounded-2xl overflow-hidden shadow-inner">
            <div class="bg-slate-900 px-6 py-3 border-b border-slate-800 flex items-center justify-between">
                <span class="text-slate-400 font-mono text-sm font-bold">Import Logboek</span>
            </div>
            <div class="p-6 font-mono text-sm space-y-2 h-64 overflow-y-auto">
                @foreach($logs as $log)
                    <div class="{{ str_contains($log, 'FOUT') ? 'text-red-400' : 'text-emerald-400' }}">
                        {{ $log }}
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

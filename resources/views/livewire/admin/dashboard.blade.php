<div>
    @section('title', 'Dashboard')
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Kaart -->
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 shadow-xl">
            <h3 class="text-slate-400 font-medium mb-2">Omzet Drankkaart Vandaag</h3>
            <p class="text-4xl font-black text-emerald-400">€ {{ number_format($revenueKaart / 100, 2, ',', '.') }}</p>
        </div>

        <!-- Revenue Bende -->
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 shadow-xl">
            <h3 class="text-slate-400 font-medium mb-2">Omzet Bende Vandaag (Scanner)</h3>
            <p class="text-4xl font-black text-indigo-400">€ {{ number_format($revenueBende / 100, 2, ',', '.') }}</p>
        </div>

        <!-- Total Revenue -->
        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 shadow-xl">
            <h3 class="text-slate-400 font-medium mb-2">Totaal Omzet Vandaag</h3>
            <p class="text-4xl font-black text-fuchsia-400">€ {{ number_format($totalRevenue / 100, 2, ',', '.') }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <h2 class="text-xl font-bold text-slate-200 mb-4">Snelle Acties</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.sales') }}" class="bg-indigo-600/20 hover:bg-indigo-600/40 border border-indigo-500/30 rounded-xl p-4 transition-all group">
            <svg class="w-8 h-8 text-indigo-400 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <h4 class="text-indigo-300 font-semibold text-lg">Verkopen & Logs</h4>
            <p class="text-indigo-400/70 text-sm mt-1">Overzicht verkochte dranken & orders</p>
        </a>
        <a href="{{ route('admin.events') }}" class="bg-fuchsia-600/20 hover:bg-fuchsia-600/40 border border-fuchsia-500/30 rounded-xl p-4 transition-all group">
            <svg class="w-8 h-8 text-fuchsia-400 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <h4 class="text-fuchsia-300 font-semibold text-lg">Events & Voorraad</h4>
            <p class="text-fuchsia-400/70 text-sm mt-1">Event opzetten en prijzen beheren</p>
        </a>
        <a href="{{ route('admin.import') }}" class="bg-cyan-600/20 hover:bg-cyan-600/40 border border-cyan-500/30 rounded-xl p-4 transition-all group">
            <svg class="w-8 h-8 text-cyan-400 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            <h4 class="text-cyan-300 font-semibold text-lg">Event Importeren</h4>
            <p class="text-cyan-400/70 text-sm mt-1">Prijzen ophalen van dranksite</p>
        </a>
        <a href="{{ route('pos.index') }}" class="bg-emerald-600/20 hover:bg-emerald-600/40 border border-emerald-500/30 rounded-xl p-4 transition-all group">
            <svg class="w-8 h-8 text-emerald-400 mb-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            <h4 class="text-emerald-300 font-semibold text-lg">Open Kassa</h4>
            <p class="text-emerald-400/70 text-sm mt-1">Ga naar de kassa terminal</p>
        </a>
    </div>
</div>

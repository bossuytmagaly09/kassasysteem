<div class="min-h-screen bg-slate-900 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-indigo-950 to-slate-900 flex flex-col justify-center items-center p-4 select-none relative overflow-hidden"
     x-data="{
         handleKey(e) {
             if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;
             if (e.key >= '0' && e.key <= '9') {
                 $wire.appendDigit(e.key);
             } else if (e.key === 'Enter') {
                 $wire.submit();
             } else if (e.key === 'Backspace') {
                 $wire.backspace();
             } else if (e.key === 'Escape' || e.key === 'Delete') {
                 $wire.clear();
             }
         }
     }"
     @keydown.window="handleKey($event)">

    <!-- Ambient glow -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 pointer-events-none"></div>

    <div class="w-full max-w-sm bg-slate-900/90 backdrop-blur-xl border-2 border-indigo-500/40 p-6 sm:p-8 rounded-3xl shadow-2xl text-center relative z-10">
        <!-- Shield Icon -->
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-fuchsia-600 p-3.5 shadow-xl shadow-indigo-500/25 border border-indigo-400/40 flex items-center justify-center text-white mx-auto mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <h2 class="text-2xl font-black text-white mb-1">Beheerpaneel Beveiligd</h2>
        <p class="text-xs text-slate-400 mb-4">Voer de beheer-PIN (standaard <strong>1989</strong>) in of scan badge.</p>

        <!-- Badge Scan / Type Input -->
        <div class="w-full mb-3 text-left">
            <input type="text"
                   wire:keydown.enter="unlockWithBadgeOrInput($event.target.value)"
                   placeholder="Scan badge of typ code + Enter"
                   class="w-full bg-slate-800 border border-slate-700 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 text-center">
        </div>

        <!-- PIN Dots Display -->
        <div class="flex justify-center items-center gap-3 mb-4 py-2 px-6 rounded-2xl bg-slate-950/70 border border-slate-800">
            @for($i = 0; $i < 4; $i++)
                <div class="w-4 h-4 rounded-full border-2 transition-all duration-200 {{ strlen($pin) > $i ? 'bg-indigo-500 border-indigo-400 scale-110 shadow-lg shadow-indigo-500/50' : 'bg-slate-800 border-slate-700' }}"></div>
            @endfor
        </div>

        @if($error)
            <div class="p-2.5 mb-3 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs font-bold animate-pulse">
                {{ $error }}
            </div>
        @endif

        <!-- Numpad 3x4 Grid -->
        <div class="grid grid-cols-3 gap-2 w-full mb-4">
            @foreach(['1','2','3','4','5','6','7','8','9'] as $digit)
                <button type="button"
                        wire:click="appendDigit('{{ $digit }}')"
                        class="h-12 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-black text-white rounded-xl shadow transition-all active:scale-95 border border-slate-700 flex items-center justify-center">
                    {{ $digit }}
                </button>
            @endforeach
            <button type="button"
                    wire:click="clear"
                    class="h-12 bg-slate-800 hover:bg-red-950 text-red-400 font-black rounded-xl text-sm transition-all active:scale-95 border border-slate-700 flex items-center justify-center">
                C
            </button>
            <button type="button"
                    wire:click="appendDigit('0')"
                    class="h-12 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-xl font-black text-white rounded-xl shadow transition-all active:scale-95 border border-slate-700 flex items-center justify-center">
                0
            </button>
            <button type="button"
                    wire:click="submit"
                    class="h-12 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-black rounded-xl text-sm flex items-center justify-center shadow-lg active:scale-95 transition-all">
                OK
            </button>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
            <button type="button"
                    wire:click="quickUnlock"
                    class="text-indigo-400 hover:text-indigo-300 font-bold underline">
                Snel openen (1989)
            </button>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white transition-colors font-semibold flex items-center gap-1">
                <span>Terug naar Menu</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</div>

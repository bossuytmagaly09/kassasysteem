<div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center p-4">
    <div class="w-full max-w-sm bg-slate-900 border border-slate-800 p-8 rounded-3xl shadow-2xl text-center">
        <!-- Shield Icon -->
        <div class="w-16 h-16 rounded-2xl bg-indigo-500/20 border border-indigo-400/40 flex items-center justify-center text-indigo-400 mx-auto mb-4 shadow-lg shadow-indigo-950/50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>

        <h2 class="text-2xl font-black text-white mb-1">Beheer Beveiligd</h2>
        <p class="text-sm text-slate-400 mb-6">Voer de 4-cijferige beheer-PIN in om door te gaan.</p>

        <!-- PIN Dots Display -->
        <div class="flex justify-center items-center gap-4 mb-6">
            @for($i = 0; $i < 4; $i++)
                <div class="w-5 h-5 rounded-full border-2 transition-all duration-200 {{ strlen($pin) > $i ? 'bg-indigo-500 border-indigo-400 scale-110 shadow-lg shadow-indigo-500/50' : 'bg-slate-800 border-slate-700' }}"></div>
            @endfor
        </div>

        @if($error)
            <div class="p-3 mb-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-300 text-xs font-bold animate-pulse">
                {{ $error }}
            </div>
        @endif

        <!-- Numpad 3x4 Grid -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            @foreach(['1','2','3','4','5','6','7','8','9'] as $digit)
                <button type="button"
                        wire:click="appendDigit('{{ $digit }}')"
                        class="h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl font-black text-white rounded-2xl shadow transition-all active:scale-95 border border-slate-700 flex items-center justify-center">
                    {{ $digit }}
                </button>
            @endforeach
            <button type="button"
                    wire:click="clear"
                    class="h-16 bg-slate-800/60 hover:bg-slate-700 active:bg-slate-600 text-sm font-bold text-slate-400 rounded-2xl transition-all active:scale-95 border border-slate-800 flex items-center justify-center">
                Wissen
            </button>
            <button type="button"
                    wire:click="appendDigit('0')"
                    class="h-16 bg-slate-800 hover:bg-slate-700 active:bg-indigo-600 text-2xl font-black text-white rounded-2xl shadow transition-all active:scale-95 border border-slate-700 flex items-center justify-center">
                0
            </button>
            <button type="button"
                    wire:click="backspace"
                    class="h-16 bg-slate-800/60 hover:bg-red-950 text-red-400 rounded-2xl transition-all active:scale-95 border border-slate-800 flex items-center justify-center font-black text-2xl"
                    title="Backspace">
                ⌫
            </button>
        </div>

        <!-- Back to POS button -->
        <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 text-xs text-slate-400 hover:text-white transition-colors font-semibold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Terug naar Kassa Terminal</span>
        </a>
    </div>
</div>

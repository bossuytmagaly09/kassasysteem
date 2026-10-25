<div class="flex w-full h-full p-3 sm:p-4 md:p-6 items-center justify-center overflow-hidden select-none">
    
    <div class="max-w-2xl w-full bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl md:rounded-3xl shadow-2xl flex flex-col relative overflow-hidden my-auto">
        
        <!-- Glow effects -->
        <div class="absolute top-[-50%] left-[-50%] w-[200%] h-[200%] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>

        <!-- Header -->
        <div class="bg-indigo-600/80 backdrop-blur-md p-4 sm:p-6 md:p-8 text-center text-white border-b border-indigo-500/50 relative z-10 shrink-0">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black mb-1 md:mb-2 tracking-tight drop-shadow-md">Scanner Test Scherm</h1>
            <p class="text-indigo-200 text-sm sm:text-base md:text-lg font-light">Controleer of de NFC scanner werkt</p>
        </div>


        <div class="p-6 sm:p-8 md:p-10 flex flex-col items-center relative z-10">
            
            @if(!$lastScannedUid)
            <!-- Waiting state -->
            <div class="relative w-36 h-36 sm:w-44 sm:h-44 flex items-center justify-center mb-6">
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full animate-ping opacity-75"></div>
                <div class="absolute inset-3 bg-indigo-500/20 rounded-full animate-ping opacity-75" style="animation-delay: 0.5s"></div>
                <div class="w-32 h-32 sm:w-36 sm:h-36 bg-slate-800 rounded-full flex items-center justify-center shadow-inner border border-indigo-500/30 z-10">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-xl sm:text-2xl text-slate-400 font-medium tracking-wide">Wachten op test-scan...</p>
            @else
            <!-- Card Details -->
            <div class="w-full space-y-4 md:space-y-6 animate-fade-in-up">
                
                @if($message)
                <div class="text-center">
                    <div class="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-2 rounded-full text-base sm:text-lg font-bold shadow-[0_0_15px_-3px_rgba(16,185,129,0.2)]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ $message }}</span>
                    </div>
                </div>
                @endif
                
                <div class="bg-slate-900/50 p-6 md:p-8 rounded-2xl border border-slate-700/50 shadow-inner flex flex-col items-center">
                    <p class="text-slate-400 text-xs sm:text-sm uppercase tracking-widest font-semibold mb-1">Uitgelezen UID:</p>
                    <div class="mt-2 text-center">
                        <p class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300 font-mono">
                            {{ $lastScannedUid }}
                        </p>
                    </div>
                </div>
                
                <div class="pt-2">
                    <button wire:click="resetScanner" class="w-full mx-auto py-3.5 md:py-4 bg-indigo-600 text-white text-lg sm:text-xl font-bold rounded-xl md:rounded-2xl hover:bg-indigo-500 active:scale-95 transition-all shadow-lg border border-indigo-400/50">
                        Opnieuw Testen
                    </button>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<style>
@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fade-in-up 0.4s ease-out forwards;
}
</style>

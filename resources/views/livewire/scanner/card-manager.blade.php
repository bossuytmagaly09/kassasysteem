<div class="flex w-full h-full p-6 items-center justify-center overflow-hidden">
    
    <div class="max-w-3xl w-full bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-2xl flex flex-col relative overflow-hidden">
        
        <!-- Glow effects -->
        <div class="absolute top-[-50%] left-[-50%] w-[200%] h-[200%] bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>

        <!-- Header -->
        <div class="bg-indigo-600/80 backdrop-blur-md p-10 text-center text-white border-b border-indigo-500/50 relative z-10">
            <h1 class="text-5xl font-black mb-4 tracking-tight drop-shadow-md">Scanner Test Scherm</h1>
            <p class="text-indigo-200 text-2xl font-light">Controleer of de NFC scanner werkt</p>
        </div>


        <div class="p-16 flex flex-col items-center relative z-10">
            
            @if(!$lastScannedUid)
            <!-- Waiting state -->
            <div class="relative w-56 h-56 flex items-center justify-center mb-10">
                <div class="absolute inset-0 bg-indigo-500/20 rounded-full animate-ping opacity-75"></div>
                <div class="absolute inset-4 bg-indigo-500/20 rounded-full animate-ping opacity-75" style="animation-delay: 0.5s"></div>
                <div class="w-48 h-48 bg-slate-800 rounded-full flex items-center justify-center shadow-inner border border-indigo-500/30 z-10">
                    <svg class="w-24 h-24 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-3xl text-slate-400 font-medium tracking-wide">Wachten op test-scan...</p>
            @else
            <!-- Card Details -->
            <div class="w-full space-y-8 animate-fade-in-up">
                
                @if($message)
                <div class="text-center">
                    <div class="inline-flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-6 py-3 rounded-full text-xl font-bold shadow-[0_0_15px_-3px_rgba(16,185,129,0.2)]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ $message }}
                    </div>
                </div>
                @endif
                
                <div class="bg-slate-900/50 p-10 rounded-3xl border border-slate-700/50 shadow-inner flex flex-col items-center">
                    <p class="text-slate-400 text-lg uppercase tracking-widest font-semibold mb-3">Uitgelezen UID:</p>
                    <div class="mt-4 text-center">
                        <p class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300 font-mono">
                            {{ $lastScannedUid }}
                        </p>
                    </div>
                </div>
                
                <div class="pt-6">
                    <button wire:click="resetScanner" class="w-full mx-auto py-5 bg-indigo-600 text-white text-2xl font-bold rounded-2xl hover:bg-indigo-500 active:scale-95 transition-all shadow-[0_0_30px_-5px_rgba(99,102,241,0.5)] border border-indigo-400/50">
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

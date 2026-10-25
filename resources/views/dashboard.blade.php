<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kassa Systeem - Hoofdmenu</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased min-h-screen bg-slate-900 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-3 sm:p-5 md:p-6 text-slate-100 select-none">
        
        <!-- Decorative background elements -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse pointer-events-none"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-fuchsia-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse pointer-events-none" style="animation-delay: 2s;"></div>

        <div class="relative max-w-7xl w-full mx-auto z-10 my-auto">
            <div class="text-center mb-6 sm:mb-8 md:mb-12">
                <div class="inline-block p-1 px-3 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs sm:text-sm font-semibold tracking-wider uppercase mb-2 sm:mb-3">
                    Beheersysteem
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-fuchsia-300 mb-2 sm:mb-4 drop-shadow-sm">Event POS</h1>
                <p class="text-sm sm:text-base md:text-xl text-slate-400 font-light max-w-2xl mx-auto">Kies een module om te starten. Snelle verwerking, real-time inzicht en volledige controle.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                
                <!-- Kassa Module -->
                <a href="{{ route('pos.index') }}" class="group relative block p-4 sm:p-6 md:p-8 bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl md:rounded-3xl overflow-hidden hover:bg-slate-800/80 hover:border-indigo-500/50 transition-all duration-300 hover:-translate-y-1 md:hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(99,102,241,0.3)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <div class="relative flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-indigo-500/20 rounded-xl md:rounded-2xl mb-3 sm:mb-5 md:mb-6 mx-auto group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 border border-indigo-500/30 shadow-inner">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-indigo-400 group-hover:text-indigo-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="relative text-base sm:text-lg md:text-2xl font-bold text-slate-100 mb-1 sm:mb-2 text-center group-hover:text-indigo-200 transition-colors">Kassa Systeem</h3>
                    <p class="relative text-slate-400 text-center text-xs sm:text-sm leading-relaxed group-hover:text-slate-300 transition-colors line-clamp-2 sm:line-clamp-none">Point of Sale interface voor touchscreens.</p>
                </a>

                <!-- Klantenscherm Module (2de scherm) -->
                <a href="{{ route('pos.display') }}" target="_blank" class="group relative block p-4 sm:p-6 md:p-8 bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl md:rounded-3xl overflow-hidden hover:bg-slate-800/80 hover:border-cyan-500/50 transition-all duration-300 hover:-translate-y-1 md:hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(6,182,212,0.3)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-cyan-500/20 rounded-xl md:rounded-2xl mb-3 sm:mb-5 md:mb-6 mx-auto group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300 border border-cyan-500/30 shadow-inner">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-cyan-400 group-hover:text-cyan-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="relative text-base sm:text-lg md:text-2xl font-bold text-slate-100 mb-1 sm:mb-2 text-center group-hover:text-cyan-200 transition-colors">Klantenscherm</h3>
                    <p class="relative text-slate-400 text-center text-xs sm:text-sm leading-relaxed group-hover:text-slate-300 transition-colors line-clamp-2 sm:line-clamp-none">2de scherm met live bestellingen en saldo.</p>
                </a>

                <!-- Scanner Module -->
                <a href="{{ route('scanner.index') }}" class="group relative block p-4 sm:p-6 md:p-8 bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl md:rounded-3xl overflow-hidden hover:bg-slate-800/80 hover:border-emerald-500/50 transition-all duration-300 hover:-translate-y-1 md:hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(16,185,129,0.3)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-emerald-500/20 rounded-xl md:rounded-2xl mb-3 sm:mb-5 md:mb-6 mx-auto group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 border border-emerald-500/30 shadow-inner">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-emerald-400 group-hover:text-emerald-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <h3 class="relative text-base sm:text-lg md:text-2xl font-bold text-slate-100 mb-1 sm:mb-2 text-center group-hover:text-emerald-200 transition-colors">Kaart Scanner</h3>
                    <p class="relative text-slate-400 text-center text-xs sm:text-sm leading-relaxed group-hover:text-slate-300 transition-colors line-clamp-2 sm:line-clamp-none">Test en scan NFC badges en kaarten.</p>
                </a>

                <!-- Beheer Module -->
                <a href="{{ route('admin.dashboard') }}" class="group relative block p-4 sm:p-6 md:p-8 bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl md:rounded-3xl overflow-hidden hover:bg-slate-800/80 hover:border-amber-500/50 transition-all duration-300 hover:-translate-y-1 md:hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(245,158,11,0.3)]">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-amber-500/20 rounded-xl md:rounded-2xl mb-3 sm:mb-5 md:mb-6 mx-auto group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300 border border-amber-500/30 shadow-inner">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 text-amber-400 group-hover:text-amber-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="relative text-base sm:text-lg md:text-2xl font-bold text-slate-100 mb-1 sm:mb-2 text-center group-hover:text-amber-200 transition-colors">Beheerpaneel</h3>
                    <p class="relative text-slate-400 text-center text-xs sm:text-sm leading-relaxed group-hover:text-slate-300 transition-colors line-clamp-2 sm:line-clamp-none">Importeer producten en bekijk logs.</p>
                </a>

            </div>

            @auth
            <div class="mt-6 sm:mt-8 md:mt-10 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 sm:px-6 sm:py-2.5 rounded-full bg-slate-800/80 border border-slate-700/50 text-xs sm:text-sm text-slate-400 hover:text-slate-200 hover:bg-slate-700 transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Uitloggen ({{ Auth::user()->name }})
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </body>
</html>

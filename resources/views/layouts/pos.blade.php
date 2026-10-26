<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Kassa Systeem') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased text-slate-100 bg-slate-900 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-indigo-950 to-slate-900 h-screen max-h-screen w-screen max-w-screen flex flex-col overflow-hidden select-none">
        
        <!-- Ambient Background -->
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-[128px] opacity-20 pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-fuchsia-500 rounded-full mix-blend-multiply filter blur-[128px] opacity-20 pointer-events-none"></div>

        <!-- Top Navigation Bar (Responsive, in-flow centered between buttons and clock) -->
        <div x-data="nfcScanner()" class="h-16 border-b border-slate-700/50 bg-slate-900/50 backdrop-blur-xl flex items-center justify-between px-6 z-40 relative shadow-sm gap-4 shrink-0">
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 bg-slate-800/50 hover:bg-slate-700/50 border border-slate-600/50 rounded-xl transition-all text-slate-300 hover:text-white shrink-0" title="Hoofdmenu">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="font-semibold text-sm tracking-wide hidden sm:inline">Hoofdmenu</span>
                </a>
                
                <!-- NFC Scanner Button -->
                <button @click="connectNfc()" 
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-xl flex items-center gap-2 transition-all border shadow-sm shrink-0"
                        :class="status === 'ready' ? 'bg-emerald-500/20 border-emerald-500/50 text-emerald-400' : (status === 'connected' ? 'bg-amber-500/20 border-amber-500/50 text-amber-400' : (status === 'error' ? 'bg-red-500/20 border-red-500/50 text-red-400' : 'bg-slate-700/50 border-slate-600 text-slate-300 hover:bg-slate-600'))">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="status === 'ready' ? 'bg-emerald-400 animate-pulse' : (status === 'connected' ? 'bg-amber-400' : 'bg-slate-500')"></span>
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    <span x-text="statusText"></span>
                </button>

                <!-- Koppel Poef Button -->
                <button @click="$dispatch('open-koppel-poef')"
                        type="button"
                        class="px-4 py-2 text-sm font-semibold rounded-xl flex items-center gap-2 transition-all border shadow-sm bg-indigo-600/20 border-indigo-500/40 text-indigo-300 hover:bg-indigo-600/40 hover:text-white shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Koppel Poef</span>
                </button>
            </div>
            
            <!-- In-flow Centered Event Name in Navbar (Right in the middle between buttons and clock) -->
            @php
                $activeEvent = \App\Models\Event::active();
            @endphp
            <div class="flex-1 min-w-0 flex items-center justify-center text-center px-4">
                @if($activeEvent)
                    <span class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-200 to-fuchsia-300 tracking-wide drop-shadow-sm truncate" title="{{ $activeEvent->name }}">
                        {{ $activeEvent->name }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/40 text-amber-300 text-sm font-bold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                        <span>Geen actief event</span>
                    </span>
                @endif
            </div>
            
            <div class="flex items-center gap-2 text-slate-400 font-medium text-sm shrink-0"
                 x-data="{ time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }"
                 x-init="setInterval(() => time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), 1000)">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span x-text="time">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        <main class="flex-1 relative z-10 flex overflow-hidden">
            {{ $slot }}
            <livewire:pos.koppel-poef />
        </main>
        <script type="module">
        import { NfcReader } from '/js/nfc-reader.js?v={{ filemtime(public_path('js/nfc-reader.js')) }}';

        // 1.2s cooldown en 1.2s aanwezigheidsdetectie voor snelle reactietijd en onmiddellijk afrekenen
        const SAME_CARD_COOLDOWN_MS = 1200;
        const CARD_HOLDING_TIMEOUT_MS = 1200;
        let lastHandledUid = null;
        let lastHandledTime = 0;
        let lastSeenTime = 0;

        function resetNfcCooldown() {
            lastHandledUid = null;
            lastHandledTime = 0;
            lastSeenTime = 0;
            if (window.__nfcReader) {
                window.__nfcReader.resetCooldown();
            }
        }
        window.__nfcResetCooldown = resetNfcCooldown;

        function dispatchCardScanned(rawUid) {
            const clean = String(rawUid || '').trim();
            if (!clean || clean.length < 3) return;

            const now = Date.now();

            // Als dezelfde kaart continue op de lezer ligt (detectiepuls):
            if (lastHandledUid === clean) {
                const timeSinceLastSeen = now - lastSeenTime;
                lastSeenTime = now;

                if (timeSinceLastSeen < CARD_HOLDING_TIMEOUT_MS) {
                    return;
                }

                if (now - lastHandledTime < SAME_CARD_COOLDOWN_MS) {
                    return;
                }
            }

            // Snelle ruis-debounce (< 300ms)
            if (now - lastHandledTime < 300) {
                return;
            }

            lastHandledUid = clean;
            lastHandledTime = now;
            lastSeenTime = now;

            Livewire.dispatch('card-scanned', { cardUid: clean, uid: clean });
        }

        const savedBaud = parseInt(localStorage.getItem('nfc_baud_rate') || '115200', 10);
        if (!window.__nfcReader && NfcReader.supported) {
            window.__nfcReader = new NfcReader({ baudRate: savedBaud, scanCooldown: SAME_CARD_COOLDOWN_MS, holdingTimeout: CARD_HOLDING_TIMEOUT_MS });
        } else if (window.__nfcReader) {
            window.__nfcReader.scanCooldown = SAME_CARD_COOLDOWN_MS;
            window.__nfcReader.holdingTimeout = CARD_HOLDING_TIMEOUT_MS;
        }

        // Feedback signaal bij fouten (de Arduino hardware piept zelf al bij contact)
        if (!window.__nfcBeepListenerAttached) {
            window.__nfcBeepListenerAttached = true;
            
            window.addEventListener('nfc-beep', (e) => {
                if (window.__nfcReader) {
                    const success = (e.detail && typeof e.detail === 'object') 
                        ? (e.detail.success ?? (e.detail[0]?.success ?? true)) 
                        : true;
                    // De Arduino hardware piept zelf direct bij contact met de kaart.
                    // Stuur ENKEL bij een fout ('E') een extra pieptoon om dubbele piepjes te voorkomen.
                    if (!success) {
                        window.__nfcReader.beep(false);
                    }
                }
            });

            // Start afrekenen via scanner: reset direct cooldown zodat de kaart onmiddellijk reageert
            window.addEventListener('nfc-awaiting-payment', () => {
                resetNfcCooldown();
            });

            window.addEventListener('nfc-cancel-payment', () => {
                resetNfcCooldown();
            });
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('nfcScanner', () => ({
                status: 'disconnected',
                statusText: 'Scanner Verbinden',
                reader: window.__nfcReader,
                
                init() {
                    if (!NfcReader.supported) {
                        this.statusText = 'Niet ondersteund';
                        return;
                    }
                    
                    this.reader = window.__nfcReader;
                    
                    const updateStatus = (state) => {
                        this.status = state;
                        if (state === 'connected') this.statusText = 'Scanner opstarten...';
                        else if (state === 'ready') this.statusText = 'Scanner Verbonden';
                        else if (state === 'error') this.statusText = 'Fout!';
                        else this.statusText = 'Scanner Verbinden';
                    };

                    this.reader.addEventListener('status', (e) => {
                        updateStatus(e.detail.state);
                    });
                    
                    if (!this.reader._cardListenerAttached) {
                        this.reader._cardListenerAttached = true;
                        this.reader.addEventListener('card', (e) => {
                            dispatchCardScanned(e.detail.uid);
                        });
                    }
                    
                    this.reader.autoConnect().then(connected => {
                        if (connected) {
                            updateStatus('ready');
                        }
                    }).catch(() => {});
                },
                
                connectNfc() {
                    if (this.reader && this.status !== 'ready') {
                        this.reader.connect().then(() => {
                            this.status = 'ready';
                            this.statusText = 'Scanner Verbonden';
                        }).catch(err => {
                            console.error(err);
                            alert('Kon niet verbinden met de scanner: ' + err.message);
                        });
                    }
                }
            }));
        });

        // Global keyboard wedge scanner listener
        let keyBuffer = '';
        let lastKeyTime = 0;
        window.addEventListener('keydown', (e) => {
            const now = Date.now();
            const target = e.target;
            const isInput = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);
            
            if (isInput && target.id !== 'scan-input') {
                return;
            }

            if (e.key === 'Enter') {
                const scanned = keyBuffer.trim();
                if (scanned.length >= 4 && (now - lastKeyTime < 400)) {
                    dispatchCardScanned(scanned);
                    keyBuffer = '';
                    e.preventDefault();
                    return;
                }
                keyBuffer = '';
                return;
            }

            if (e.key.length === 1) {
                if (now - lastKeyTime > 150) {
                    keyBuffer = '';
                }
                keyBuffer += e.key;
                lastKeyTime = now;
            }
        });

        // Broadcast cart and display state to 2nd screen (customer display)
        if (window.BroadcastChannel) {
            const displayChannel = new BroadcastChannel('kassa_display_channel');
            window.addEventListener('pos-display-updated', (event) => {
                displayChannel.postMessage(event.detail || {});
            });
        }
        </script>
        @livewireScripts
    </body>
</html>

<div>
    @if($isOpen)
        <div wire:key="koppel-poef-modal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 backdrop-blur-md p-4 sm:p-6"
             x-data
             @keydown.escape.window="$wire.close()">
            <div class="bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden relative">

                <!-- Header -->
                <div class="p-6 bg-slate-900/90 border-b border-slate-700/60 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center text-indigo-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-100">Koppel Poef & Identificatie</h2>
                            <p class="text-xs sm:text-sm text-slate-400">Koppel een bankkaart, badge of nfc kaart aan een lid van het drankplatform.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Push / Sync Button -->
                        <button type="button"
                                wire:click="pushAndSync"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded-xl text-slate-200 text-sm font-semibold flex items-center gap-2 transition-all">
                            <svg class="w-4 h-4 text-emerald-400" wire:loading.class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Push naar Beheer</span>
                        </button>

                        <!-- Close Modal Button -->
                        <button type="button"
                                wire:click="close"
                                class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Messages / Notifications -->
                @if($message)
                    <div class="mx-6 mt-4 p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ $message }}</span>
                    </div>
                @endif

                @if($errorMessage)
                    <div class="mx-6 mt-4 p-3 rounded-xl bg-red-500/15 border border-red-500/40 text-red-300 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <!-- Navigation Tabs -->
                <div class="flex border-b border-slate-700/60 bg-slate-900/40 px-6 pt-3 gap-2">
                    <button type="button"
                            wire:click="setTab('koppel')"
                            class="px-5 py-3 font-bold text-sm rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $activeTab === 'koppel' ? 'border-indigo-500 text-indigo-400 bg-slate-800/60' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        <span>Nieuwe Koppeling Maken</span>
                    </button>
                    <button type="button"
                            wire:click="setTab('overzicht')"
                            class="px-5 py-3 font-bold text-sm rounded-t-xl transition-all border-b-2 flex items-center gap-2 {{ $activeTab === 'overzicht' ? 'border-indigo-500 text-indigo-400 bg-slate-800/60' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        <span>Overzicht Gekoppeld ({{ $this->linkedCards->count() }})</span>
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    @if($activeTab === 'koppel')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Left: Select Member (Poef) -->
                            <div class="flex flex-col bg-slate-800/50 rounded-2xl border border-slate-700/60 p-4">
                                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-indigo-600/30 text-indigo-300 inline-flex items-center justify-center text-xs">1</span>
                                    Selecteer Lid (Poef)
                                </h3>

                                <div class="relative mb-3">
                                    <input type="text"
                                           wire:model.live.debounce.250ms="search"
                                           placeholder="Zoek op naam..."
                                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                                </div>

                                <div class="flex-1 max-h-[320px] overflow-y-auto space-y-1.5 pr-1">
                                    @forelse($this->poefs as $poef)
                                        <button type="button"
                                                wire:key="poef-option-{{ $poef->id }}"
                                                wire:click="selectPoef({{ $poef->id }})"
                                                class="w-full text-left p-3 rounded-xl border transition-all flex items-center justify-between {{ $selectedPoefId === $poef->id ? 'bg-indigo-600/30 border-indigo-500 text-white shadow-sm' : 'bg-slate-900/60 border-slate-800 text-slate-300 hover:bg-slate-700/40 hover:border-slate-700' }}">
                                            <div class="font-bold truncate">{{ $poef->name }}</div>
                                            <div class="flex items-center gap-2">
                                                @if($poef->discount_percentage > 0)
                                                    <span class="text-xs bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-full font-bold">
                                                        -{{ $poef->discount_percentage }}%
                                                    </span>
                                                @endif
                                                @if($selectedPoefId === $poef->id)
                                                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                @endif
                                            </div>
                                        </button>
                                    @empty
                                        <p class="text-slate-500 text-center py-8 text-sm">Geen leden gevonden.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Right: Medium & UID Details -->
                            <div class="flex flex-col bg-slate-800/50 rounded-2xl border border-slate-700/60 p-4 space-y-4">
                                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-indigo-600/30 text-indigo-300 inline-flex items-center justify-center text-xs">2</span>
                                    Kaart / Badge Gegevens
                                </h3>

                                <!-- Gekozen Lid Weergave -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Geselecteerd Lid:</label>
                                    @if($selectedPoefId)
                                        @php $currentPoef = \App\Models\Poef::find($selectedPoefId); @endphp
                                        <div class="p-3 bg-indigo-950/40 border border-indigo-500/40 rounded-xl text-indigo-200 font-bold flex items-center justify-between">
                                            <span>{{ $currentPoef?->name }}</span>
                                            <span class="text-xs text-indigo-400">ID: {{ $currentPoef?->external_id }}</span>
                                        </div>
                                    @else
                                        <div class="p-3 bg-slate-900/60 border border-slate-700/60 rounded-xl text-slate-500 text-sm">
                                            Kies links een lid uit de lijst
                                        </div>
                                    @endif
                                </div>

                                <!-- UID / Serienummer -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Serienummer / UID:</label>
                                    <div class="relative">
                                        <input type="text"
                                               wire:model="cardUid"
                                               placeholder="Scan tegen scanner of typ UID..."
                                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs flex items-center gap-1">
                                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                            <span>Scanner actief</span>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">Houd badge, bankkaart of tag tegen de scanner; het nummer verschijnt vanzelf.</p>
                                </div>

                                <!-- Type Medium: Bankkaart, Badge of NFC Kaart -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Type Medium:</label>
                                    <div class="grid grid-cols-3 gap-2.5">
                                        @foreach([
                                            'bankkaart' => ['label' => 'Bankkaart', 'icon' => '💳'],
                                            'badge' => ['label' => 'Badge', 'icon' => '🏷️'],
                                            'nfc_kaart' => ['label' => 'NFC Kaart', 'icon' => '⚡'],
                                        ] as $type => $info)
                                            <label class="cursor-pointer select-none">
                                                <input type="radio" wire:model="cardType" value="{{ $type }}" class="peer sr-only">
                                                <div class="p-3 text-center text-sm font-bold rounded-xl border border-slate-700/80 bg-slate-900/90 text-slate-300 peer-checked:bg-indigo-600/30 peer-checked:border-indigo-500 peer-checked:text-white peer-checked:shadow-md transition-all flex flex-col items-center justify-center gap-1 hover:border-slate-600">
                                                    <span class="text-xl">{{ $info['icon'] }}</span>
                                                    <span class="tracking-wide">{{ $info['label'] }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Actieknop -->
                                <button type="button"
                                        wire:click="linkCard"
                                        class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                    <span>Koppelen & Opslaan</span>
                                </button>
                            </div>
                        </div>

                    @else
                        <!-- Tab 2: Overzicht Gekoppelde Media -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between text-xs text-slate-400 bg-slate-800/40 p-3 rounded-xl border border-slate-700/50">
                                <span>Alle koppelingen worden automatisch bewaard en gesynchroniseerd met de drank beheerwebsite.</span>
                                <span class="font-bold text-slate-300">Totaal actief: {{ $this->linkedCards->count() }}</span>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-slate-700/60 bg-slate-800/30">
                                <table class="w-full text-left text-sm text-slate-300">
                                    <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700/60">
                                        <tr>
                                            <th class="p-3.5">Lid / Poef</th>
                                            <th class="p-3.5">Medium</th>
                                            <th class="p-3.5">Serienummer (UID)</th>
                                            <th class="p-3.5">Gekoppeld Op</th>
                                            <th class="p-3.5 text-right">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-700/40">
                                        @forelse($this->linkedCards as $card)
                                            <tr wire:key="linked-card-{{ $card->id }}" class="hover:bg-slate-800/40 transition-colors">
                                                <td class="p-3.5 font-bold text-white">
                                                    {{ $card->poef?->name ?? 'Onbekend lid' }}
                                                </td>
                                                <td class="p-3.5">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                                        {{ $card->card_type === 'badge' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : ($card->card_type === 'bankkaart' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30') }}">
                                                        @if($card->card_type === 'bankkaart')
                                                            💳 Bankkaart
                                                        @elseif($card->card_type === 'badge')
                                                            🏷️ Badge
                                                        @else
                                                            ⚡ NFC Kaart
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="p-3.5 font-mono text-xs text-indigo-300">
                                                    {{ $card->uid }}
                                                </td>
                                                <td class="p-3.5 text-slate-500 text-xs">
                                                    {{ $card->created_at?->format('d-m-Y H:i') ?? '-' }}
                                                </td>
                                                <td class="p-3.5 text-right">
                                                    <button type="button"
                                                            wire:click="unlinkCard({{ $card->id }})"
                                                            wire:confirm="Weet je zeker dat je deze kaart wilt ontkoppelen?"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                                            title="Ontkoppelen">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="p-8 text-center text-slate-500 text-sm">
                                                    Nog geen kaarten of badges gekoppeld.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="p-4 bg-slate-900/90 border-t border-slate-700/60 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Kassa Systeem Poef Synchronisatie</span>
                    <button type="button"
                            wire:click="close"
                            class="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-sm rounded-xl transition-colors">
                        Sluiten
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

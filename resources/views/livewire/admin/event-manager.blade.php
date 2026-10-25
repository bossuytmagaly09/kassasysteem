<div>
    @section('title', 'Events & Voorraad')

    @php
        $input = 'w-full bg-slate-900/90 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-all';
        $label = 'block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-6">

        <!-- Left: Event list -->
        <aside class="space-y-3">
            <button type="button" id="btn-new-event" wire:click="createEvent"
                class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white px-6 py-3.5 rounded-2xl font-bold transition-all shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nieuw Event Aanmaken
            </button>

            <div class="space-y-2 mt-4">
                <div class="px-2 text-xs font-bold uppercase tracking-wider text-slate-500">Alle Events</div>

                @forelse($events as $event)
                    <button type="button" wire:key="event-{{ $event->id }}" wire:click="selectEvent({{ $event->id }})"
                        class="w-full text-left p-4 rounded-2xl border transition-all {{ $selectedEvent?->id === $event->id ? 'bg-indigo-600/20 border-indigo-500/60 shadow-[0_0_25px_-5px_rgba(99,102,241,0.4)]' : 'bg-slate-800/40 border-slate-700/50 hover:bg-slate-800/80 hover:border-slate-600' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-slate-100 truncate text-base">{{ $event->name }}</span>
                            @if($event->is_active)
                                <span class="shrink-0 text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> KASSA
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-2">
                            <span>{{ $event->date?->format('d/m/Y') ?? 'Geen datum' }}</span>
                            <span>•</span>
                            <span>{{ $event->products_count ?? $event->products->count() }} items</span>
                        </div>
                        <div class="text-xs text-indigo-300/80 mt-1.5 flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-indigo-400"></span>
                            {{ $event->payment_mode->label() }}
                        </div>
                    </button>
                @empty
                    <div class="text-slate-500 text-center py-8 bg-slate-800/20 border border-slate-700/40 rounded-2xl p-4">
                        <p class="text-sm font-medium">Nog geen events aangemaakt.</p>
                        <p class="text-xs text-slate-600 mt-1">Klik op 'Nieuw Event Aanmaken' om te beginnen.</p>
                    </div>
                @endforelse
            </div>
        </aside>

        <!-- Right: Event detail & Products -->
        <section>
            @if($selectedEvent)
                <!-- Event Header Card -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-6 shadow-xl mb-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h2 class="text-3xl font-black text-slate-100 tracking-tight">{{ $selectedEvent->name }}</h2>
                                @if($selectedEvent->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/50 flex items-center gap-1.5 uppercase tracking-wider">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Nu actief op kassa
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-700/50 text-slate-400 border border-slate-600/50 uppercase tracking-wider">
                                        Inactief
                                    </span>
                                @endif
                            </div>
                            <p class="text-slate-400 text-sm mt-1">
                                {{ $selectedEvent->date?->translatedFormat('l j F Y') ?? 'Geen datum ingesteld' }}
                            </p>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex flex-wrap items-center gap-2">
                            @if($selectedEvent->is_active)
                                <button type="button" wire:click="deactivateEvent({{ $selectedEvent->id }})"
                                    class="px-4 py-2.5 rounded-xl border border-slate-600 text-slate-300 hover:bg-slate-700/60 font-semibold text-sm transition-all">
                                    Deactiveren
                                </button>
                            @else
                                <button type="button" wire:click="activateEvent({{ $selectedEvent->id }})"
                                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-500/25 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Activeren op Kassa
                                </button>
                            @endif

                            @if($otherEvents->isNotEmpty())
                                <button type="button" wire:click="$set('showCopyModal', true)"
                                    class="px-4 py-2.5 rounded-xl bg-slate-700/80 hover:bg-slate-700 text-indigo-300 border border-indigo-500/30 font-semibold text-sm transition-all flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    Kopieer van vorig event
                                </button>
                            @endif

                            <button type="button" wire:click="editEvent({{ $selectedEvent->id }})"
                                class="px-4 py-2.5 rounded-xl bg-slate-700/80 hover:bg-slate-700 text-slate-200 font-semibold text-sm transition-all">
                                Instellingen
                            </button>

                            <button type="button" wire:click="deleteEvent({{ $selectedEvent->id }})"
                                wire:confirm="Weet je zeker dat je event '{{ $selectedEvent->name }}' wilt verwijderen?"
                                class="p-2.5 rounded-xl text-red-400 hover:bg-red-500/10 font-semibold text-sm transition-all" title="Verwijderen">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Event quick stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div class="bg-slate-900/60 rounded-2xl p-4 border border-slate-700/50">
                            <span class="text-xs uppercase tracking-wider text-slate-500 font-bold">Betaalmethode</span>
                            <div class="text-base font-bold text-slate-100 mt-1 truncate">{{ $selectedEvent->payment_mode->label() }}</div>
                        </div>
                        <div class="bg-slate-900/60 rounded-2xl p-4 border border-slate-700/50">
                            <span class="text-xs uppercase tracking-wider text-slate-500 font-bold">Consumptiekaart / Vakje</span>
                            <div class="text-lg font-black text-fuchsia-400 mt-1">
                                € {{ number_format(($selectedEvent->card_price ?? 2000) / 100, 2, ',', '.') }}
                                <span class="text-xs font-semibold text-slate-400">/ € {{ number_format($selectedEvent->vak_price / 100, 2, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="bg-slate-900/60 rounded-2xl p-4 border border-slate-700/50">
                            <span class="text-xs uppercase tracking-wider text-slate-500 font-bold">Producten op kassa</span>
                            <div class="text-xl font-black text-indigo-400 mt-1">{{ $selectedEvent->products->count() }} items</div>
                        </div>
                        <div class="bg-slate-900/60 rounded-2xl p-4 border border-slate-700/50">
                            <span class="text-xs uppercase tracking-wider text-slate-500 font-bold">Event Omzet</span>
                            <div class="text-xl font-black text-emerald-400 mt-1">€ {{ number_format($revenue / 100, 2, ',', '.') }}</div>
                        </div>
                    </div>

                    @if($selectedEvent->payment_mode->allowsDrankkaart())
                        <!-- Drankkaart & Vakjes Prijzen Direct Aanpassen -->
                        <div class="mt-6 p-5 rounded-2xl bg-gradient-to-r from-fuchsia-950/40 via-purple-950/30 to-slate-900/60 border border-fuchsia-500/40 shadow-lg">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-fuchsia-500/20 border border-fuchsia-500/40 flex items-center justify-center text-fuchsia-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-bold text-white">Consumptiekaart & Vakjes Prijzen Aanpassen</h4>
                                        <p class="text-xs text-slate-400">Pas direct de verkoopprijs van een consumptiekaart en de waarde per vakje aan voor dit event.</p>
                                    </div>
                                </div>
                                <button type="button"
                                        id="btn-update-prices"
                                        wire:click="updateEventPrices"
                                        class="px-5 py-2.5 bg-fuchsia-600 hover:bg-fuchsia-500 active:scale-95 text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-fuchsia-600/30 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Prijzen Opslaan</span>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="quick-card-price" class="block text-xs font-bold text-slate-300 mb-1">Prijs 1 Consumptiekaart (€)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm pointer-events-none">€</span>
                                        <input id="quick-card-price" type="number" step="0.50" min="0.50" wire:model="cardPrice" class="w-full bg-slate-900 border border-slate-700 focus:border-fuchsia-500 rounded-xl pl-8 pr-3 py-2 text-white font-bold">
                                    </div>
                                    @error('cardPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="quick-vak-price" class="block text-xs font-bold text-slate-300 mb-1">Waarde per Vakje (€)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm pointer-events-none">€</span>
                                        <input id="quick-vak-price" type="number" step="0.05" min="0.01" wire:model="vakPrice" class="w-full bg-slate-900 border border-slate-700 focus:border-fuchsia-500 rounded-xl pl-8 pr-3 py-2 text-white font-bold">
                                    </div>
                                    @error('vakPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="bg-slate-900/80 rounded-xl p-3 border border-slate-800 flex flex-col justify-center">
                                    <span class="text-xs text-slate-400 font-medium">Berekend aantal vakjes:</span>
                                    <span class="text-xl font-black text-fuchsia-300">
                                        {{ ((float) str_replace(',', '.', $vakPrice) > 0) ? (int) round(((float) str_replace(',', '.', $cardPrice)) / ((float) str_replace(',', '.', $vakPrice))) : 0 }} vakjes per kaart
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Notification Banners -->
                @if($eventJustCreated)
                    <div class="bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 p-4 rounded-2xl mb-6 flex items-start gap-3 shadow-lg">
                        <svg class="w-6 h-6 shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="font-bold text-base text-white">Event '{{ $selectedEvent->name }}' succesvol aangemaakt!</p>
                            <p class="text-sm text-emerald-200/90 mt-0.5">Vul hieronder je ingekochte dranken (per fles, blik of vat) en eten in. Het systeem berekent automatisch de glazen en de voorraad voor je kassa.</p>
                        </div>
                    </div>
                @endif

                @if($feedbackMessage)
                    <div class="bg-indigo-500/15 border border-indigo-500/40 text-indigo-200 p-4 rounded-2xl mb-6 flex items-center justify-between gap-3 shadow-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="font-semibold text-sm">{{ $feedbackMessage }}</span>
                        </div>
                        <button type="button" wire:click="$set('feedbackMessage', '')" class="text-slate-400 hover:text-white text-xs font-bold">Sluiten</button>
                    </div>
                @endif

                <!-- ADD PRODUCT CARD (Intuitive Aankoop -> Verkoop flow) -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-indigo-500/40 rounded-3xl p-6 shadow-2xl mb-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-100 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Drank of Eten toevoegen aan dit Event
                            </h3>
                            <p class="text-sm text-slate-400 mt-0.5">
                                Geef aan wat je hebt aangekocht (fles, blik, vat of stuk) en hoe het op de kassa wordt verkocht (bv. per glas).
                            </p>
                        </div>

                        <!-- Mode tabs -->
                        <div class="flex rounded-xl bg-slate-900/80 p-1 border border-slate-700/60">
                            <button type="button" wire:click="setAddMode('new')"
                                class="px-4 py-2 rounded-lg text-sm font-bold transition-all {{ $addMode === 'new' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                                + Zelf Invoeren (Aankoop & Verkoop)
                            </button>
                            <button type="button" wire:click="setAddMode('existing')"
                                class="px-4 py-2 rounded-lg text-sm font-bold transition-all {{ $addMode === 'existing' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white' }}">
                                Uit Bestaande Lijst
                            </button>
                        </div>
                    </div>

                    @if($addMode === 'new')
                        <div class="space-y-5">

                            <!-- STAP 1: NAAM EN CATEGORIE -->
                            <div class="bg-slate-900/60 rounded-2xl p-5 border border-slate-700/60">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 font-bold text-xs flex items-center justify-center">1</span>
                                    <h4 class="text-sm font-bold uppercase tracking-wider text-indigo-300">Product & Categorie</h4>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                            Naam van de drank of het eten
                                        </label>
                                        <input type="text"
                                            id="product-name-input"
                                            wire:model.live="productName"
                                            placeholder="bv. Cola, Witte Wijn, Stella, Cava, Hotdog..."
                                            class="{{ $input }} text-base font-bold">
                                        <span class="text-xs text-slate-500 mt-1.5 block">Verschijnt als knop op de kassa</span>
                                        @error('productName') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1.5 h-5">
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-400">Categorie op de kassa</label>
                                            <button type="button" wire:click="toggleNewCategory" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                                                {{ $showNewCategoryInput ? '← Kies bestaande' : '+ Nieuwe categorie' }}
                                            </button>
                                        </div>

                                        @if($showNewCategoryInput)
                                            <input type="text" wire:model="newCategoryName" placeholder="bv. Cocktails, Bieren, Snacks..." class="{{ $input }} text-base font-medium">
                                            <span class="text-xs text-slate-500 mt-1.5 block">Nieuwe categorie wordt direct opgeslagen</span>
                                            @error('newCategoryName') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        @else
                                            <select wire:model="categoryId" class="{{ $input }} text-base font-medium">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-xs text-slate-500 mt-1.5 block">Kies het tabblad op de kassa</span>
                                            @error('categoryId') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- STAP 2: WAT HEB JE AANGEKOCHT? -->
                            <div class="bg-slate-900/60 rounded-2xl p-5 border border-slate-700/60">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 font-bold text-xs flex items-center justify-center">2</span>
                                        <h4 class="text-sm font-bold uppercase tracking-wider text-indigo-300">Inkoop & Verpakking</h4>
                                    </div>
                                    <span class="text-xs text-slate-400">Hoe koop je dit in bij de leverancier?</span>
                                </div>

                                <!-- 4 Verpakking buttons -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <!-- Fles -->
                                    <button type="button" wire:click="setPurchaseUnit('fles')"
                                        class="p-4 rounded-xl border text-left transition-all flex flex-col justify-between h-24 {{ $purchaseUnit === 'fles' ? 'bg-indigo-600/25 border-indigo-400 text-white shadow-[0_0_20px_-3px_rgba(99,102,241,0.45)] ring-1 ring-indigo-400/50' : 'bg-slate-800/60 border-slate-700/80 text-slate-300 hover:bg-slate-800 hover:border-slate-600' }}">
                                        <div class="text-2xl leading-none">🍾</div>
                                        <div>
                                            <div class="font-bold text-sm">Fles</div>
                                            <div class="text-[11px] text-slate-400 truncate">Wijn, drank of sterk</div>
                                        </div>
                                    </button>

                                    <!-- Blik -->
                                    <button type="button" wire:click="setPurchaseUnit('blik')"
                                        class="p-4 rounded-xl border text-left transition-all flex flex-col justify-between h-24 {{ $purchaseUnit === 'blik' ? 'bg-indigo-600/25 border-indigo-400 text-white shadow-[0_0_20px_-3px_rgba(99,102,241,0.45)] ring-1 ring-indigo-400/50' : 'bg-slate-800/60 border-slate-700/80 text-slate-300 hover:bg-slate-800 hover:border-slate-600' }}">
                                        <div class="text-2xl leading-none">🥫</div>
                                        <div>
                                            <div class="font-bold text-sm">Blik</div>
                                            <div class="text-[11px] text-slate-400 truncate">Frisdrank of bier</div>
                                        </div>
                                    </button>

                                    <!-- Vat -->
                                    <button type="button" wire:click="setPurchaseUnit('vat')"
                                        class="p-4 rounded-xl border text-left transition-all flex flex-col justify-between h-24 {{ $purchaseUnit === 'vat' ? 'bg-indigo-600/25 border-indigo-400 text-white shadow-[0_0_20px_-3px_rgba(99,102,241,0.45)] ring-1 ring-indigo-400/50' : 'bg-slate-800/60 border-slate-700/80 text-slate-300 hover:bg-slate-800 hover:border-slate-600' }}">
                                        <div class="text-2xl leading-none">🛢️</div>
                                        <div>
                                            <div class="font-bold text-sm">Vat / Fust</div>
                                            <div class="text-[11px] text-slate-400 truncate">Fust / tapbier</div>
                                        </div>
                                    </button>

                                    <!-- Stuk / Portie -->
                                    <button type="button" wire:click="setPurchaseUnit('stuk')"
                                        class="p-4 rounded-xl border text-left transition-all flex flex-col justify-between h-24 {{ $purchaseUnit === 'stuk' ? 'bg-indigo-600/25 border-indigo-400 text-white shadow-[0_0_20px_-3px_rgba(99,102,241,0.45)] ring-1 ring-indigo-400/50' : 'bg-slate-800/60 border-slate-700/80 text-slate-300 hover:bg-slate-800 hover:border-slate-600' }}">
                                        <div class="text-2xl leading-none">🥪</div>
                                        <div>
                                            <div class="font-bold text-sm">Stuk / Portie</div>
                                            <div class="text-[11px] text-slate-400 truncate">Snack, portie, cocktail</div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Dynamische velden op basis van verpakking -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 pt-5 border-t border-slate-800 items-start">
                                    @if(in_array($purchaseUnit, ['fles', 'blik', 'vat']))
                                        <!-- Inhoud per eenheid (liter / cl vrij aanpasbaar) -->
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Inhoud per {{ $purchaseUnit }}
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" step="0.01" min="0" wire:model.live="packageVolume"
                                                    placeholder="{{ $purchaseUnit === 'fles' ? '1.5' : ($purchaseUnit === 'vat' ? '50' : '33') }}"
                                                    class="{{ $input }} font-bold text-slate-100 flex-1">
                                                <select wire:model.live="packageVolumeUnit" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-3 text-slate-200 font-bold text-sm focus:border-indigo-500 focus:outline-none">
                                                    <option value="L">Liter (L)</option>
                                                    <option value="cl">cl</option>
                                                </select>
                                            </div>

                                            <!-- Snelkeuze knoppen voor inhoud -->
                                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                <span class="text-[11px] text-slate-500 font-medium mr-0.5">Snel:</span>
                                                @if($purchaseUnit === 'fles')
                                                    <button type="button" wire:click="setVolumePreset('0.75', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '0.75' && $packageVolumeUnit === 'L' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">0.75L</button>
                                                    <button type="button" wire:click="setVolumePreset('1', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '1' && $packageVolumeUnit === 'L' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">1L</button>
                                                    <button type="button" wire:click="setVolumePreset('1.5', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '1.5' && $packageVolumeUnit === 'L' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">1.5L</button>
                                                    <button type="button" wire:click="setVolumePreset('2', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '2' && $packageVolumeUnit === 'L' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">2L</button>
                                                    <button type="button" wire:click="setVolumePreset('70', 'cl')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '70' && $packageVolumeUnit === 'cl' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">70cl</button>
                                                @elseif($purchaseUnit === 'vat')
                                                    <button type="button" wire:click="setVolumePreset('20', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '20' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">20L</button>
                                                    <button type="button" wire:click="setVolumePreset('30', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '30' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">30L</button>
                                                    <button type="button" wire:click="setVolumePreset('50', 'L')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '50' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">50L</button>
                                                @elseif($purchaseUnit === 'blik')
                                                    <button type="button" wire:click="setVolumePreset('25', 'cl')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '25' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">25cl</button>
                                                    <button type="button" wire:click="setVolumePreset('33', 'cl')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '33' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">33cl</button>
                                                    <button type="button" wire:click="setVolumePreset('50', 'cl')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $packageVolume == '50' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">50cl</button>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Aantal ingekocht -->
                                    <div class="{{ $purchaseUnit === 'stuk' ? 'md:col-span-1' : '' }}">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                            Aantal {{ $purchaseUnit === 'fles' ? 'flessen' : ($purchaseUnit === 'blik' ? 'blikken' : ($purchaseUnit === 'vat' ? 'vaten' : 'stuks')) }}
                                        </label>
                                        <div class="relative">
                                            <input type="number" step="1" min="0" wire:model.live="purchaseQuantity"
                                                placeholder="{{ $purchaseUnit === 'fles' ? '10' : ($purchaseUnit === 'blik' ? '48' : ($purchaseUnit === 'vat' ? '2' : '50')) }}"
                                                class="{{ $input }} font-bold text-slate-100">
                                        </div>
                                        <span class="text-xs text-slate-500 mt-1.5 block">Ingekocht aantal (laat leeg voor onbeperkt)</span>
                                        @error('purchaseQuantity') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Inkoopprijs -->
                                    <div class="{{ $purchaseUnit === 'stuk' ? 'md:col-span-1' : '' }}">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                            Inkoopprijs per {{ $purchaseUnit }}
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                            <input type="number" step="0.01" min="0" wire:model.live="purchasePrice"
                                                placeholder="{{ $purchaseUnit === 'fles' ? '1.50' : ($purchaseUnit === 'blik' ? '0.60' : ($purchaseUnit === 'vat' ? '120.00' : '1.20')) }}"
                                                class="{{ $input }} pl-8 font-bold text-amber-300">
                                        </div>
                                        <span class="text-xs text-slate-500 mt-1.5 block">Aankoopprijs excl. btw</span>
                                        @error('purchasePrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- STAP 3: HOE WORDT DIT OP DE KASSA VERKOCHT? -->
                            <div class="bg-slate-900/60 rounded-2xl p-5 border border-slate-700/60">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-bold text-xs flex items-center justify-center">3</span>
                                        <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-300">Verkoop op de Kassa</h4>
                                    </div>
                                    <span class="text-xs text-slate-400">Verkoopprijzen en porties aan de toog</span>
                                </div>

                                <!-- ALS FLES IS GEKOZEN -->
                                @if($purchaseUnit === 'fles')
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Verkoopwijze aan de toog
                                            </label>
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <label class="cursor-pointer">
                                                    <input type="radio" wire:model.live="bottleSellMode" value="glas" class="peer sr-only">
                                                    <div class="p-3 text-center rounded-xl border border-slate-700/80 bg-slate-800/80 text-xs font-bold text-slate-300 peer-checked:bg-emerald-600/25 peer-checked:border-emerald-400 peer-checked:text-emerald-200 peer-checked:ring-1 peer-checked:ring-emerald-400/50 transition-all flex items-center justify-center gap-2 h-12">
                                                        <span class="text-lg">🥤</span>
                                                        <span>Per glas (schenken)</span>
                                                    </div>
                                                </label>
                                                <label class="cursor-pointer">
                                                    <input type="radio" wire:model.live="bottleSellMode" value="fles" class="peer sr-only">
                                                    <div class="p-3 text-center rounded-xl border border-slate-700/80 bg-slate-800/80 text-xs font-bold text-slate-300 peer-checked:bg-emerald-600/25 peer-checked:border-emerald-400 peer-checked:text-emerald-200 peer-checked:ring-1 peer-checked:ring-emerald-400/50 transition-all flex items-center justify-center gap-2 h-12">
                                                        <span class="text-lg">🍾</span>
                                                        <span>Per hele fles</span>
                                                    </div>
                                                </label>
                                                <label class="cursor-pointer">
                                                    <input type="radio" wire:model.live="bottleSellMode" value="beide" class="peer sr-only">
                                                    <div class="p-3 text-center rounded-xl border border-slate-700/80 bg-slate-800/80 text-xs font-bold text-slate-300 peer-checked:bg-emerald-600/25 peer-checked:border-emerald-400 peer-checked:text-emerald-200 peer-checked:ring-1 peer-checked:ring-emerald-400/50 transition-all flex items-center justify-center gap-2 h-12">
                                                        <span class="text-lg">🥂</span>
                                                        <span>Beide (glas + fles)</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- VELDEN VOOR GLAS -->
                                        @if($bottleSellMode === 'glas')
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-slate-800 items-start">
                                                <!-- Inhoud glas -->
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Inhoud per glas (cl)
                                                    </label>
                                                    <input type="number" step="0.5" min="1" wire:model.live="servingVolume"
                                                        placeholder="20" class="{{ $input }} font-bold text-slate-100">
                                                    <div class="flex flex-wrap items-center gap-1 mt-2">
                                                        <span class="text-[11px] text-slate-500 font-medium mr-0.5">Snel:</span>
                                                        <button type="button" wire:click="setServingPreset('12.5')" class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors {{ $servingVolume == '12.5' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">12.5cl (Wijn)</button>
                                                        <button type="button" wire:click="setServingPreset('20')" class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors {{ $servingVolume == '20' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">20cl</button>
                                                        <button type="button" wire:click="setServingPreset('25')" class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors {{ $servingVolume == '25' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">25cl</button>
                                                        <button type="button" wire:click="setServingPreset('4')" class="px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors {{ $servingVolume == '4' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">4cl (Shot)</button>
                                                    </div>
                                                </div>

                                                <!-- Glazen per fles -->
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Glazen per fles
                                                    </label>
                                                    <input type="number" step="1" min="1" wire:model.live="glassesPerBottle"
                                                        placeholder="5" class="{{ $input }} font-bold text-slate-100">
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Berekend uit inhoud (vrij aanpasbaar)</span>
                                                    @error('glassesPerBottle') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>

                                                <!-- Verkoopprijs glas -->
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Verkoopprijs per glas
                                                    </label>
                                                    <div class="relative">
                                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                        <input type="number" step="0.05" min="0" wire:model.live="glassPrice"
                                                            placeholder="2.50" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                                    </div>
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Prijs per consumptie op kassa</span>
                                                    @error('glassPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>
                                            </div>

                                        <!-- VELDEN VOOR HELE FLES -->
                                        @elseif($bottleSellMode === 'fles')
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-800 items-start">
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Verkoopprijs per hele fles
                                                    </label>
                                                    <div class="relative">
                                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                        <input type="number" step="0.05" min="0" wire:model.live="bottlePrice"
                                                            placeholder="18.00" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                                    </div>
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Prijs voor een volledige fles aan de toog</span>
                                                    @error('bottlePrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>
                                            </div>

                                        <!-- BEIDE: 4 KOLOMMEN -->
                                        @else
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-800 items-start">
                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Inhoud per glas (cl)
                                                    </label>
                                                    <input type="number" step="0.5" min="1" wire:model.live="servingVolume"
                                                        placeholder="20" class="{{ $input }} font-bold text-slate-100">
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Grootte van 1 glas</span>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Glazen per fles
                                                    </label>
                                                    <input type="number" step="1" min="1" wire:model.live="glassesPerBottle"
                                                        placeholder="5" class="{{ $input }} font-bold text-slate-100">
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Berekend (aanpasbaar)</span>
                                                    @error('glassesPerBottle') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Prijs per glas
                                                    </label>
                                                    <div class="relative">
                                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                        <input type="number" step="0.05" min="0" wire:model.live="glassPrice"
                                                            placeholder="2.50" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                                    </div>
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Prijs per los glas</span>
                                                    @error('glassPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                        Prijs per hele fles
                                                    </label>
                                                    <div class="relative">
                                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                        <input type="number" step="0.05" min="0" wire:model.live="bottlePrice"
                                                            placeholder="18.00" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                                    </div>
                                                    <span class="text-xs text-slate-500 mt-1.5 block">Prijs voor hele fles</span>
                                                    @error('bottlePrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                <!-- ALS BLIK IS GEKOZEN -->
                                @elseif($purchaseUnit === 'blik')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Verkoopprijs per blik
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                <input type="number" step="0.05" min="0" wire:model.live="canPrice"
                                                    placeholder="2.50" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                            </div>
                                            <span class="text-xs text-slate-500 mt-1.5 block">Prijs per blikje op de kassa</span>
                                            @error('canPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                <!-- ALS VAT IS GEKOZEN -->
                                @elseif($purchaseUnit === 'vat')
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Inhoud per pintje/glas (cl)
                                            </label>
                                            <input type="number" step="0.5" min="1" wire:model.live="servingVolume"
                                                placeholder="25" class="{{ $input }} font-bold text-slate-100">
                                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                <span class="text-[11px] text-slate-500 font-medium mr-0.5">Snel:</span>
                                                <button type="button" wire:click="setServingPreset('25')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $servingVolume == '25' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">25cl (Boerke)</button>
                                                <button type="button" wire:click="setServingPreset('33')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $servingVolume == '33' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">33cl (Vaasje)</button>
                                                <button type="button" wire:click="setServingPreset('50')" class="px-2 py-0.5 rounded text-[11px] font-bold transition-colors {{ $servingVolume == '50' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' }}">50cl (Halve liter)</button>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Pintjes / glazen per vat
                                            </label>
                                            <input type="number" step="1" min="1" wire:model.live="glassesPerKeg"
                                                placeholder="150" class="{{ $input }} font-bold text-slate-100">
                                            <span class="text-xs text-slate-500 mt-1.5 block">Berekend uit {{ $packageVolume }}L (vrij aanpasbaar voor tapverlies)</span>
                                            @error('glassesPerKeg') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Verkoopprijs per pintje / glas
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                <input type="number" step="0.05" min="0" wire:model.live="kegGlassPrice"
                                                    placeholder="2.20" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                            </div>
                                            <span class="text-xs text-slate-500 mt-1.5 block">Prijs per consumptie op de kassa</span>
                                            @error('kegGlassPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                <!-- ALS STUK / PORTIE IS GEKOZEN -->
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                                Verkoopprijs per stuk / portie
                                            </label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                                <input type="number" step="0.05" min="0" wire:model.live="itemPrice"
                                                    placeholder="3.50" class="{{ $input }} pl-8 font-black text-emerald-400 text-lg">
                                            </div>
                                            <span class="text-xs text-slate-500 mt-1.5 block">Prijs per snack, cocktail of portie</span>
                                            @error('itemPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- LIVE PREVIEW / RESULTAAT SAMENVATTING -->
                            @php
                                $bName = trim($productName) ?: 'Drankje';
                                $qty = is_numeric($purchaseQuantity) ? (int)$purchaseQuantity : null;
                                $pPrice = is_numeric($purchasePrice) ? (float)$purchasePrice : null;
                            @endphp
                            <div class="bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-indigo-500/40 rounded-2xl p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-5 shadow-xl">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-600/25 border border-indigo-500/50 flex items-center justify-center text-indigo-300 text-2xl shrink-0">
                                        💡
                                    </div>
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Live Berekening & Kassa Preview</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-200 border border-indigo-500/30">Auto-update</span>
                                        </div>

                                        <div class="text-sm font-medium text-slate-200">
                                            @if($purchaseUnit === 'fles')
                                                @if($bottleSellMode === 'glas')
                                                    Kassa krijgt knop: <strong class="text-white">{{ $bName }}</strong> aan
                                                    <strong class="text-emerald-400">€ {{ $glassPrice ?: '0.00' }}</strong>
                                                    · Voorraad: <strong class="text-white">{{ $qty !== null ? ($qty * max((int)$glassesPerBottle, 1)).' glazen' : 'onbeperkt' }}</strong>
                                                    <span class="text-xs text-slate-400">({{ $qty ?: '∞' }} flessen à {{ $packageVolume }}{{ $packageVolumeUnit }} × {{ $glassesPerBottle }} gl/fles à {{ $servingVolume }}cl)</span>
                                                    @if($pPrice !== null)
                                                        @php
                                                            $costPerGlass = $pPrice / max((int)$glassesPerBottle, 1);
                                                            $marginPerGlass = is_numeric($glassPrice) ? ((float)$glassPrice - $costPerGlass) : null;
                                                        @endphp
                                                        <div class="text-xs text-amber-300/90 mt-1 flex flex-wrap items-center gap-2">
                                                            <span>Inkoop: € {{ number_format($pPrice, 2, ',', '.') }}/fles (€ {{ number_format($costPerGlass, 2, ',', '.') }} per glas)</span>
                                                            @if($marginPerGlass !== null)
                                                                <span>·</span>
                                                                <span class="font-bold text-emerald-400">Marge: +€ {{ number_format($marginPerGlass, 2, ',', '.') }} per glas</span>
                                                                @if($qty !== null)
                                                                    <span>·</span>
                                                                    <span class="font-bold text-emerald-300">Totale potentiële winst: +€ {{ number_format($marginPerGlass * ($qty * max((int)$glassesPerBottle, 1)), 2, ',', '.') }}</span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endif
                                                @elseif($bottleSellMode === 'fles')
                                                    Kassa krijgt knop: <strong class="text-white">{{ $bName }}</strong> aan
                                                    <strong class="text-emerald-400">€ {{ $bottlePrice ?: '0.00' }}</strong>
                                                    · Voorraad: <strong class="text-white">{{ $qty !== null ? $qty.' flessen' : 'onbeperkt' }}</strong>
                                                    <span class="text-xs text-slate-400">({{ $qty ?: '∞' }} hele flessen à {{ $packageVolume }}{{ $packageVolumeUnit }})</span>
                                                    @if($pPrice !== null)
                                                        @php
                                                            $marginPerBottle = is_numeric($bottlePrice) ? ((float)$bottlePrice - $pPrice) : null;
                                                        @endphp
                                                        <div class="text-xs text-amber-300/90 mt-1 flex flex-wrap items-center gap-2">
                                                            <span>Inkoop: € {{ number_format($pPrice, 2, ',', '.') }}/fles</span>
                                                            @if($marginPerBottle !== null)
                                                                <span>·</span>
                                                                <span class="font-bold text-emerald-400">Marge: +€ {{ number_format($marginPerBottle, 2, ',', '.') }} per fles</span>
                                                                @if($qty !== null)
                                                                    <span>·</span>
                                                                    <span class="font-bold text-emerald-300">Totale potentiële winst: +€ {{ number_format($marginPerBottle * $qty, 2, ',', '.') }}</span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endif
                                                @else
                                                    Kassa krijgt 2 knoppen:
                                                    <strong class="text-white">{{ $bName }} Glas</strong> (€ {{ $glassPrice ?: '0.00' }})
                                                    én <strong class="text-white">{{ $bName }} Fles</strong> (€ {{ $bottlePrice ?: '0.00' }})
                                                    @if($qty !== null)
                                                        · Ingekocht: <strong class="text-white">{{ $qty }} flessen à {{ $packageVolume }}{{ $packageVolumeUnit }}</strong>
                                                    @endif
                                                @endif
                                            @elseif($purchaseUnit === 'blik')
                                                Kassa krijgt knop: <strong class="text-white">{{ $bName }}</strong> aan
                                                <strong class="text-emerald-400">€ {{ $canPrice ?: '0.00' }}</strong>
                                                · Voorraad: <strong class="text-white">{{ $qty !== null ? $qty.' blikken' : 'onbeperkt' }}</strong>
                                                <span class="text-xs text-slate-400">({{ $qty ?: '∞' }} blikken à {{ $packageVolume }}{{ $packageVolumeUnit }})</span>
                                                @if($pPrice !== null)
                                                    @php
                                                        $marginPerCan = is_numeric($canPrice) ? ((float)$canPrice - $pPrice) : null;
                                                    @endphp
                                                    <div class="text-xs text-amber-300/90 mt-1 flex flex-wrap items-center gap-2">
                                                        <span>Inkoop: € {{ number_format($pPrice, 2, ',', '.') }}/blik</span>
                                                        @if($marginPerCan !== null)
                                                            <span>·</span>
                                                            <span class="font-bold text-emerald-400">Marge: +€ {{ number_format($marginPerCan, 2, ',', '.') }} per blik</span>
                                                            @if($qty !== null)
                                                                <span>·</span>
                                                                <span class="font-bold text-emerald-300">Totale potentiële winst: +€ {{ number_format($marginPerCan * $qty, 2, ',', '.') }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endif
                                            @elseif($purchaseUnit === 'vat')
                                                Kassa krijgt knop: <strong class="text-white">{{ $bName }}</strong> aan
                                                <strong class="text-emerald-400">€ {{ $kegGlassPrice ?: '0.00' }}</strong>
                                                · Voorraad: <strong class="text-white">{{ $qty !== null ? ($qty * max((int)$glassesPerKeg, 1)).' pintjes/glazen' : 'onbeperkt' }}</strong>
                                                <span class="text-xs text-slate-400">({{ $qty ?: '∞' }} vaten à {{ $packageVolume }}L × {{ $glassesPerKeg }} gl/vat à {{ $servingVolume }}cl)</span>
                                                @if($pPrice !== null)
                                                    @php
                                                        $costPerGlass = $pPrice / max((int)$glassesPerKeg, 1);
                                                        $marginPerGlass = is_numeric($kegGlassPrice) ? ((float)$kegGlassPrice - $costPerGlass) : null;
                                                    @endphp
                                                    <div class="text-xs text-amber-300/90 mt-1 flex flex-wrap items-center gap-2">
                                                        <span>Inkoop: € {{ number_format($pPrice, 2, ',', '.') }}/vat (€ {{ number_format($costPerGlass, 2, ',', '.') }} per pintje)</span>
                                                        @if($marginPerGlass !== null)
                                                            <span>·</span>
                                                            <span class="font-bold text-emerald-400">Marge: +€ {{ number_format($marginPerGlass, 2, ',', '.') }} per pintje</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                Kassa krijgt knop: <strong class="text-white">{{ $bName }}</strong> aan
                                                <strong class="text-emerald-400">€ {{ $itemPrice ?: '0.00' }}</strong>
                                                · Voorraad: <strong class="text-white">{{ $qty !== null ? $qty.' stuks' : 'onbeperkt' }}</strong>
                                                @if($pPrice !== null)
                                                    @php
                                                        $marginPerItem = is_numeric($itemPrice) ? ((float)$itemPrice - $pPrice) : null;
                                                    @endphp
                                                    <div class="text-xs text-amber-300/90 mt-1 flex flex-wrap items-center gap-2">
                                                        <span>Inkoop: € {{ number_format($pPrice, 2, ',', '.') }}/stuk</span>
                                                        @if($marginPerItem !== null)
                                                            <span>·</span>
                                                            <span class="font-bold text-emerald-400">Marge: +€ {{ number_format($marginPerItem, 2, ',', '.') }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <button type="button" id="btn-add-custom-product" wire:click="addNewProduct"
                                        class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-bold text-base transition-all shadow-lg shadow-emerald-500/30 flex items-center justify-center gap-2 active:scale-[0.98]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span>+ Toevoegen aan Kassa</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    @else
                        <!-- Mode 2: Pick from existing catalog products -->
                        <div class="bg-slate-900/60 rounded-2xl p-5 border border-slate-700/60">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                        Kies een bestaand product
                                    </label>
                                    <select id="existing-product-select" wire:model.live="newProductId" class="{{ $input }} text-base">
                                        <option value="">Selecteer product…</option>
                                        @foreach($availableProducts as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->category->name ?? 'Geen categorie' }}) — standaard € {{ number_format($product->price / 100, 2, ',', '.') }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-xs text-slate-500 mt-1.5 block">Kies uit je algemene productencatalogus</span>
                                    @error('newProductId') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                        Event Verkoopprijs (€)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                        <input type="number" step="0.05" min="0" wire:model="newPrice" placeholder="2.50" class="{{ $input }} pl-8 font-bold text-emerald-400">
                                    </div>
                                    <span class="text-xs text-slate-500 mt-1.5 block">Prijs voor dit specifieke event</span>
                                    @error('newPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 h-5 flex items-center">
                                        Voorraad (leeg = ∞)
                                    </label>
                                    <input type="number" step="1" min="0" wire:model="newStock" placeholder="bv. 100" class="{{ $input }} font-bold text-slate-100">
                                    <span class="text-xs text-slate-500 mt-1.5 block">Aantal stuks beschikbaar</span>
                                    @error('newStock') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-4 flex justify-end pt-3 border-t border-slate-800">
                                    <button type="button" id="btn-add-existing-product" wire:click="addExistingProduct"
                                        class="px-8 h-12 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-500/30 flex items-center justify-center gap-2 active:scale-[0.98]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Product toevoegen aan Event
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- OVERVIEW TABLE: Drank, Eten, Prijzen & Voorraad -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-xl overflow-hidden">
                    <div class="p-5 bg-slate-900/60 border-b border-slate-700/50 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                Overzicht Dranken, Eten & Voorraad voor dit Event
                            </h3>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                {{ $selectedEvent->products->count() }} items
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Verkoopprijs en voorraad zijn direct aanpasbaar in de tabel (auto-save).</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-900/40 border-b border-slate-700/50 text-slate-400 text-xs uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="p-4">Drank / Eten op Kassa</th>
                                    <th class="p-4">Aankoopverpakking</th>
                                    <th class="p-4 w-36">Verkoopprijs (€)</th>
                                    <th class="p-4 w-36">Kassa Voorraad</th>
                                    <th class="p-4">Verkocht & Resterend</th>
                                    <th class="p-4 w-24 text-right">Actie</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                @forelse($selectedEvent->products->sortBy('name') as $product)
                                    @php
                                        $planned = $product->pivot->stock_planned;
                                        $soldQty = $sold[$product->id] ?? 0;
                                        $remaining = $planned === null ? null : max($planned - $soldQty, 0);
                                        $percent = $planned ? min(100, round($soldQty / $planned * 100)) : 0;
                                        $barColor = $remaining === 0 ? 'bg-red-500' : ($percent >= 80 ? 'bg-amber-400' : 'bg-emerald-400');
                                        $pUnit = $product->pivot->purchase_unit;
                                        $pQty = $product->pivot->purchase_quantity;
                                        $sPerUnit = $product->pivot->servings_per_unit;
                                    @endphp
                                    <tr wire:key="event-product-{{ $product->id }}" class="hover:bg-slate-800/60 transition-colors"
                                        x-data="{ saved: false }"
                                        x-on:product-saved.window="if ($event.detail.productId === {{ $product->id }}) { saved = true; setTimeout(() => saved = false, 1500) }">
                                        
                                        <!-- Naam + Categorie -->
                                        <td class="p-4">
                                            <div class="text-slate-100 font-bold text-base">{{ $product->name }}</div>
                                            <div class="inline-block text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-700/60 text-slate-300 mt-1 border border-slate-600/40">
                                                {{ $product->category->name ?? 'Geen categorie' }}
                                            </div>
                                        </td>

                                        <!-- Aankoop verpakking info -->
                                        <td class="p-4">
                                            @if($pUnit === 'fles')
                                                <div class="text-sm text-slate-200 font-semibold flex items-center gap-1.5 flex-wrap">
                                                    <span>🍾</span>
                                                    <span>{{ $pQty !== null ? $pQty.' flessen' : 'Flessen' }}</span>
                                                    @if($product->pivot->package_volume)
                                                        <span class="text-xs px-2 py-0.5 rounded-md bg-indigo-500/15 text-indigo-300 font-bold border border-indigo-500/30">à {{ $product->pivot->package_volume }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-slate-400 mt-1">
                                                    @if($product->pivot->purchase_price)
                                                        <span class="text-amber-300/90 font-medium">Inkoop: € {{ number_format($product->pivot->purchase_price / 100, 2, ',', '.') }}/fles</span>
                                                        @if($sPerUnit && $sPerUnit > 1) · @endif
                                                    @endif
                                                    @if($sPerUnit && $sPerUnit > 1)
                                                        {{ $sPerUnit }} gl./fles @if($product->pivot->serving_volume)({{ $product->pivot->serving_volume }})@endif
                                                    @endif
                                                </div>
                                            @elseif($pUnit === 'blik')
                                                <div class="text-sm text-slate-200 font-semibold flex items-center gap-1.5 flex-wrap">
                                                    <span>🥫</span>
                                                    <span>{{ $pQty !== null ? $pQty.' blikken' : 'Blikken' }}</span>
                                                    @if($product->pivot->package_volume)
                                                        <span class="text-xs px-2 py-0.5 rounded-md bg-indigo-500/15 text-indigo-300 font-bold border border-indigo-500/30">à {{ $product->pivot->package_volume }}</span>
                                                    @endif
                                                </div>
                                                @if($product->pivot->purchase_price)
                                                    <div class="text-xs text-amber-300/90 font-medium mt-1">Inkoop: € {{ number_format($product->pivot->purchase_price / 100, 2, ',', '.') }}/blik</div>
                                                @endif
                                            @elseif($pUnit === 'vat')
                                                <div class="text-sm text-slate-200 font-semibold flex items-center gap-1.5 flex-wrap">
                                                    <span>🛢️</span>
                                                    <span>{{ $pQty !== null ? $pQty.' vat(en)' : 'Vat' }}</span>
                                                    @if($product->pivot->package_volume)
                                                        <span class="text-xs px-2 py-0.5 rounded-md bg-indigo-500/15 text-indigo-300 font-bold border border-indigo-500/30">à {{ $product->pivot->package_volume }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-slate-400 mt-1">
                                                    @if($product->pivot->purchase_price)
                                                        <span class="text-amber-300/90 font-medium">Inkoop: € {{ number_format($product->pivot->purchase_price / 100, 2, ',', '.') }}/vat</span> · 
                                                    @endif
                                                    @if($sPerUnit)
                                                        {{ $sPerUnit }} pintjes/vat @if($product->pivot->serving_volume)({{ $product->pivot->serving_volume }})@endif
                                                    @endif
                                                </div>
                                            @elseif($pUnit === 'stuk')
                                                <div class="text-sm text-slate-200 font-semibold flex items-center gap-1.5">
                                                    <span>🥪</span>
                                                    <span>{{ $pQty !== null ? $pQty.' stuks' : 'Per stuk' }}</span>
                                                </div>
                                                @if($product->pivot->purchase_price)
                                                    <div class="text-xs text-amber-300/90 font-medium mt-1">Inkoop: € {{ number_format($product->pivot->purchase_price / 100, 2, ',', '.') }}/stuk</div>
                                                @endif
                                            @else
                                                <span class="text-xs text-slate-500">—</span>
                                            @endif
                                        </td>

                                        <!-- Verkoopprijs input -->
                                        <td class="p-4">
                                            <div class="relative">
                                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm pointer-events-none">€</span>
                                                <input type="number" step="0.05" min="0"
                                                    wire:model="productPrices.{{ $product->id }}"
                                                    wire:change="updateProduct({{ $product->id }})"
                                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-8 pr-3 py-2 text-emerald-400 font-black text-base focus:border-indigo-500 focus:outline-none">
                                            </div>
                                            @error("productPrices.{$product->id}") <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </td>

                                        <!-- Kassa voorraad input -->
                                        <td class="p-4">
                                            <input type="number" step="1" min="0" placeholder="∞"
                                                wire:model="productStocks.{{ $product->id }}"
                                                wire:change="updateProduct({{ $product->id }})"
                                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-100 font-bold text-base focus:border-indigo-500 focus:outline-none">
                                            @error("productStocks.{$product->id}") <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                                        </td>

                                        <!-- Verkocht & Voortgangsbalk -->
                                        <td class="p-4 min-w-[200px]">
                                            <div class="flex items-center justify-between text-xs mb-1.5">
                                                <span class="text-slate-400 font-semibold">{{ $soldQty }} verkocht</span>
                                                @if($remaining === null)
                                                    <span class="font-bold text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded-md border border-indigo-500/30">Onbeperkt</span>
                                                @elseif($remaining === 0)
                                                    <span class="font-black text-red-400 bg-red-500/20 px-2 py-0.5 rounded-md border border-red-500/40 animate-pulse">UITVERKOCHT</span>
                                                @elseif($remaining <= 10)
                                                    <span class="font-bold text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-md border border-amber-500/40">{{ $remaining }} over (bijna op!)</span>
                                                @else
                                                    <span class="font-bold text-emerald-300 bg-emerald-500/20 px-2 py-0.5 rounded-md border border-emerald-500/40">{{ $remaining }} over</span>
                                                @endif
                                            </div>
                                            @if($planned !== null)
                                                <div class="h-2 rounded-full bg-slate-700/80 overflow-hidden">
                                                    <div class="h-full {{ $barColor }} transition-all duration-500" style="width: {{ $percent }}%"></div>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Acties -->
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <span x-show="saved" x-transition class="text-emerald-400 text-xs font-bold bg-emerald-500/10 px-2 py-1 rounded">✓ Opgeslagen</span>
                                                <button type="button" wire:click="removeProduct({{ $product->id }})"
                                                    wire:confirm="Weet je zeker dat je '{{ $product->name }}' uit dit event wilt verwijderen?"
                                                    class="text-red-400 hover:text-red-300 hover:bg-red-500/10 p-2 rounded-xl transition-all" title="Verwijderen uit dit event">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-12 text-center text-slate-500">
                                            <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-600">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                            </div>
                                            <p class="text-base font-bold text-slate-300">Nog geen dranken of eten toegevoegd aan dit event.</p>
                                            <p class="text-sm text-slate-500 mt-1">Geef hierboven aan wat je hebt aangekocht (fles, blik of vat) en voeg het toe aan de kassa.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-3xl p-16 text-center text-slate-400">
                    <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <h3 class="text-2xl font-bold text-slate-200 mb-2">Geen event geselecteerd</h3>
                    <p class="text-slate-400 max-w-md mx-auto mb-6">Selecteer links een event om de dranken, prijzen en voorraad te beheren, of maak een nieuw event aan.</p>
                    <button type="button" wire:click="createEvent" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition-all">
                        + Nieuw Event Aanmaken
                    </button>
                </div>
            @endif
        </section>
    </div>

    <!-- MODAL: Event Settings (Create / Edit) -->
    @if($showEventModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm" x-data @keydown.escape.window="$wire.set('showEventModal', false)">
            <div class="bg-slate-900 border border-slate-700 p-8 rounded-3xl shadow-2xl max-w-lg w-full relative">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-black text-slate-100">{{ $editingEventId ? 'Event Instellingen Aanpassen' : 'Nieuw Event Opzetten' }}</h3>
                    <button type="button" wire:click="$set('showEventModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4 mb-6">
                    <div>
                        <label for="event-name" class="{{ $label }}">Naam van het Event</label>
                        <input id="event-name" type="text" wire:model="name" placeholder="bv. Zomerbarbecue, Chiro Fuif, Vrijdagborrel..." class="{{ $input }}">
                        @error('name') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="event-date" class="{{ $label }}">Datum</label>
                        <input id="event-date" type="date" wire:model="date" class="{{ $input }}">
                        @error('date') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Toegestane Betaalmethodes op de Kassa</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($paymentModes as $mode)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="paymentMode" value="{{ $mode->value }}" class="peer sr-only">
                                    <div class="text-center text-xs font-bold p-3 rounded-xl border border-slate-700 bg-slate-800/80 text-slate-300 peer-checked:border-indigo-500 peer-checked:bg-indigo-600/25 peer-checked:text-indigo-200 transition-all">
                                        {{ $mode->label() }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('paymentMode') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    @if($paymentMode !== 'scanner')
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="event-card-price" class="{{ $label }}">Prijs consumptiekaart (€)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                <input id="event-card-price" type="number" step="0.50" min="0.50" wire:model="cardPrice" placeholder="20.00" class="{{ $input }} pl-8 font-bold text-fuchsia-400">
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5">
                                <span class="text-[11px] text-slate-500 font-medium">Snel:</span>
                                <button type="button" wire:click="$set('cardPrice', '10.00')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 10</button>
                                <button type="button" wire:click="$set('cardPrice', '15.00')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 15</button>
                                <button type="button" wire:click="$set('cardPrice', '20.00')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 20</button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Aankoopprijs volle consumptiekaart.</p>
                            @error('cardPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="event-vak-price" class="{{ $label }}">Waarde per vakje (€)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none">€</span>
                                <input id="event-vak-price" type="number" step="0.05" min="0.01" wire:model="vakPrice" placeholder="1.00" class="{{ $input }} pl-8 font-bold text-fuchsia-400">
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5">
                                <span class="text-[11px] text-slate-500 font-medium">Snel:</span>
                                <button type="button" wire:click="$set('vakPrice', '1.00')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 1,00</button>
                                <button type="button" wire:click="$set('vakPrice', '1.50')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 1,50</button>
                                <button type="button" wire:click="$set('vakPrice', '2.00')" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 hover:bg-slate-700">€ 2,00</button>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Waarde per doorstreept vakje.</p>
                            @error('vakPrice') <p class="text-red-400 text-xs mt-1 font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @endif
                </div>

                <div class="flex gap-3">
                    <button type="button" wire:click="$set('showEventModal', false)" class="w-1/3 py-3.5 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 transition-colors">Annuleren</button>
                    <button type="button" id="btn-save-event" wire:click="saveEvent" class="w-2/3 py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/30 transition-all">
                        {{ $editingEventId ? 'Wijzigingen Opslaan' : 'Event Aanmaken & Drank Toevoegen →' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: Copy products from another event -->
    @if($showCopyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm" x-data @keydown.escape.window="$wire.set('showCopyModal', false)">
            <div class="bg-slate-900 border border-slate-700 p-8 rounded-3xl shadow-2xl max-w-md w-full relative">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-100">Kopieer dranken van vorig event</h3>
                    <button type="button" wire:click="$set('showCopyModal', false)" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-sm text-slate-400 mb-6">Kies een vorig event om alle dranken, eten en prijzen in 1 klik over te nemen naar '{{ $selectedEvent->name }}'.</p>

                <div class="mb-6">
                    <label class="{{ $label }}">Kies bronevent</label>
                    <select wire:model="copyFromEventId" class="{{ $input }}">
                        <option value="">Selecteer event…</option>
                        @foreach($otherEvents as $other)
                            <option value="{{ $other->id }}">{{ $other->name }} ({{ $other->date?->format('d/m/Y') ?? 'Geen datum' }}) — {{ $other->products->count() }} items</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3">
                    <button type="button" wire:click="$set('showCopyModal', false)" class="w-1/2 py-3 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700">Annuleren</button>
                    <button type="button" wire:click="copyFromEvent" @disabled(! $copyFromEventId) class="w-1/2 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl shadow-lg transition-all">Kopiëren</button>
                </div>
            </div>
        </div>
    @endif
</div>

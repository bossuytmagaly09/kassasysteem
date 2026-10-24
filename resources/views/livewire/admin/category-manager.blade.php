<div>
    @section('title', 'Categorieën')
    
    <div class="flex justify-end mb-6">
        <button wire:click="create" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-xl font-bold transition-colors shadow-lg shadow-indigo-500/30">
            + Nieuwe Categorie
        </button>
    </div>

    <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden max-w-4xl">
        <table class="w-full text-left">
            <thead class="bg-slate-900/50 border-b border-slate-700/50 text-slate-400">
                <tr>
                    <th class="p-4 font-semibold">Naam</th>
                    <th class="p-4 font-semibold">Interne Slug</th>
                    <th class="p-4 font-semibold">Aantal Producten</th>
                    <th class="p-4 font-semibold">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                @foreach($categories as $category)
                <tr class="hover:bg-slate-800/60 transition-colors">
                    <td class="p-4 text-slate-200 font-bold">{{ $category->name }}</td>
                    <td class="p-4 text-slate-400 font-mono text-sm">{{ $category->slug }}</td>
                    <td class="p-4">
                        <span class="bg-slate-700 text-slate-300 px-3 py-1 rounded-full text-sm font-bold">
                            {{ $category->products_count }}
                        </span>
                    </td>
                    <td class="p-4">
                        <button wire:click="edit({{ $category->id }})" class="text-indigo-400 hover:text-indigo-300 font-medium">Bewerken</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 p-8 rounded-3xl shadow-2xl max-w-md w-full relative">
            <h3 class="text-2xl font-bold text-slate-100 mb-6">{{ $categoryId ? 'Categorie Aanpassen' : 'Nieuwe Categorie' }}</h3>
            
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-slate-400 text-sm font-medium mb-2">Naam (zichtbaar op de kassa)</label>
                    <input type="text" wire:model="name" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-200 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-slate-400 text-sm font-medium mb-2">Interne Slug (optioneel)</label>
                    <input type="text" wire:model="slug" placeholder="Wordt automatisch gegenereerd" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-slate-400 font-mono focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex gap-4">
                <button wire:click="$set('showModal', false)" class="w-1/2 py-3 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 transition-colors">Annuleren</button>
                <button wire:click="save" class="w-1/2 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-500 transition-colors">Opslaan</button>
            </div>
        </div>
    </div>
    @endif
</div>

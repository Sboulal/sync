<x-app-layout>
    
    <!-- 🟢 HEADER SECTION -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Assemblées</h1>
            <p class="text-blue-100 text-sm">Gestion des assemblées générales de la copropriété</p>
        </div>
        <div x-data>
            <button @click="$dispatch('open-modal')" class="mt-4 sm:mt-0 bg-white hover:bg-gray-50 text-[#0F396B] px-5 py-2.5 rounded-full text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nouvelle assemblée
            </button>
        </div>
    </x-slot>

    <!-- 🟢 CONTAINER DYAL LA PAGE W L-MODAL -->
    <div x-data="{ showModal: false }" @open-modal.window="showModal = true" class="relative">

        <!-- 🟢 MAIN CONTENT (Table 3la chkl Cards) -->
        <div class="bg-white/50 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            
            <!-- Toolbar (Filtres & Search) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <div class="flex flex-wrap items-center gap-2">
                     <button class="px-5 py-2 bg-[#1A5EAC] text-white text-sm font-bold rounded-full shadow-md shadow-[#1A5EAC]/20">Toutes</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">À venir</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Terminées</button>
                </div>

                <div class="relative w-full lg:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] sm:text-sm transition-all shadow-sm" placeholder="Rechercher une assemblée...">
                </div>
            </div>

            <!-- 🟢 Table m9adda (Row = Card) -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-3">
                    <thead>
                        <tr>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Détails de l'assemblée</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Heure & Lieu</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Ligne 1 (Inspirée de la maquette mobile) -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-500 flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-black text-gray-900 block">08 Oct 2026</span>
                                        <span class="text-xs font-bold text-gray-400">2026-10-08</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Assemblée Générale Just test</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate"><span class="font-bold text-gray-700">Ordre du jour:</span> Finance</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <div class="flex flex-col gap-1 text-sm font-medium text-gray-600">
                                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 19:00</span>
                                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> Garage</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-500 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Terminée
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors" title="Modifier">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-[#1A5EAC] transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Ligne 2 (Exemple : À venir) -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1A5EAC] flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-black text-[#0F396B] block">15 Nov 2026</span>
                                        <span class="text-xs font-bold text-gray-400">2026-11-15</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">AG Extraordinaire</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate"><span class="font-bold text-gray-700">Ordre du jour:</span> Vote pour les travaux de façade, budget prévisionnel</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <div class="flex flex-col gap-1 text-sm font-medium text-gray-600">
                                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> 18:30</span>
                                    <span class="flex items-center gap-1.5"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> Hall d'entrée</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-[#1A5EAC] bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100">
                                    <svg class="w-3.5 h-3.5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Prévue
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-[#1A5EAC] transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pt-6 mt-2 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500 font-medium">
                Affichage de 1 à 2 sur 12 assemblées
                <div class="flex items-center gap-2">
                    <button class="p-2 rounded-xl border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:border-[#1A5EAC] disabled:opacity-50 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                    <button class="p-2 rounded-xl border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:border-[#1A5EAC] transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                </div>
            </div>

        </div>

        <!-- ==========================================
             🟢 MODAL (max-w-3xl)
             ========================================== -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="showModal"
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-3xl my-8">
                    
                    <div class="bg-white px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-black text-[#0F396B]" id="modal-title">Programmer une assemblée</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full p-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 sm:px-8 bg-gray-50/50">
                        <form>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="space-y-4 sm:col-span-2">
                                    <h4 class="text-[11px] font-extrabold text-[#1A5EAC] uppercase tracking-wider">Détails de l'AG</h4>
                                </div>
                                
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Titre de l'assemblée</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Assemblée Générale Ordinaire 2026">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Date</label>
                                    <input type="date" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-600">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Heure</label>
                                    <input type="time" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-600">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Lieu</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Garage, Hall d'entrée...">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Statut</label>
                                     <select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-600">
                                        <option>Prévue</option>
                                        <option>Terminée</option>
                                        <option>Annulée</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Ordre du jour</label>
                                    <textarea rows="4" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all resize-none" placeholder="Ex: Approbation des comptes, Budget prévisionnel, Finance..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 sm:px-8">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="button" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1A5EAC] rounded-xl hover:bg-[#0F396B] shadow-md shadow-[#1A5EAC]/20 transition-all">
                            Enregistrer l'assemblée
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
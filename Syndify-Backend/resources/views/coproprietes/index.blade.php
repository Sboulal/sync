<x-app-layout>
    
    <!-- 🟢 HEADER SECTION -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Copropriété</h1>
            <p class="text-blue-100 text-sm">Gestion des lots et des propriétaires</p>
        </div>
        <div x-data>
            <button @click="$dispatch('open-modal')" class="mt-4 sm:mt-0 bg-white hover:bg-gray-50 text-[#0F396B] px-5 py-2.5 rounded-full text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Ajouter un lot
            </button>
        </div>
    </x-slot>

    <!-- 🟢 CONTAINER DYAL LA PAGE W L-MODAL -->
    <div x-data="{ showModal: false }" @open-modal.window="showModal = true" class="relative">

        <!-- 🟢 MAIN CONTENT (Table 3la chkl Cards) -->
        <div class="bg-white/50 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            
            <!-- Toolbar (Filters & Search) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                
                <!-- 🟢 Filters à gauche (machi f l-west) -->
                <div class="flex flex-wrap items-center gap-2">
                    <button class="px-5 py-2 bg-[#1A5EAC] text-white text-sm font-bold rounded-full shadow-md shadow-[#1A5EAC]/20">Tous</button>
                    <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">À jour</button>
                    <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">En retard</button>
                </div>

                <!-- Search -->
                <div class="relative w-full lg:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] sm:text-sm transition-all shadow-sm" placeholder="Rechercher un propriétaire, un lot...">
                </div>
            </div>

            <!-- 🟢 Table m9adda (Row = Card) b7al l-image li siftti -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-3"> <!-- border-spacing-y-3 hiya li kat3ti l-espace bin stoura -->
                    <thead>
                        <tr>
                            <!-- 🟢 Header m9add w n9i -->
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Id - Propriétaire</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Lot & Tantièmes</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Ligne 1 (Carte) -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <!-- 🟢 Derna rounded l-qnat dyal kolla row bash tban bhal carte -->
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-bold text-gray-400">#001</span>
                                    <div class="w-10 h-10 rounded-full bg-[#e6f4ea] text-[#137333] flex items-center justify-center text-sm font-black">DF</div>
                                    <span class="text-sm font-bold text-gray-900">Driss Fassi</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-bold text-gray-700">Lot A12</span>
                                <span class="text-xs text-gray-400 block mt-0.5">Étage 1 • 100 tan.</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-medium text-gray-600">0661234567</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-[#137333]">
                                    <span class="w-2 h-2 rounded-full bg-[#137333]"></span> À jour
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <button class="p-2 text-gray-400 hover:text-[#1A5EAC] transition-colors">
                                    <!-- Icone 3 no9at b7al f tswira -->
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Ligne 2 (Carte) -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-bold text-gray-400">#022</span>
                                    <img src="https://ui-avatars.com/api/?name=Youssef+Naciri&background=fce8e6&color=c5221f" class="w-10 h-10 rounded-full" alt="YN">
                                    <span class="text-sm font-bold text-gray-900">Youssef Naciri</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-bold text-gray-700">Lot B04</span>
                                <span class="text-xs text-gray-400 block mt-0.5">Étage 2 • 85 tan.</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-medium text-gray-600">0633333333</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-[#c5221f]">
                                    <span class="w-2 h-2 rounded-full bg-[#c5221f]"></span> En retard
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <button class="p-2 text-gray-400 hover:text-[#1A5EAC] transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                                </button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pt-6 mt-2 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500 font-medium">
                Affichage de 1 à 2 sur 48 lots
                <div class="flex items-center gap-2">
                    <button class="p-2 rounded-xl border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:border-[#1A5EAC] disabled:opacity-50 transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                    <button class="p-2 rounded-xl border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:border-[#1A5EAC] transition-colors"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                </div>
            </div>

        </div>

        <!-- ==========================================
             🟢 MODAL (Sghernah l max-w-2xl bash yban m9add)
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
                     class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-3xl my-8"> <!-- 🟢 Rddinah max-w-3xl machi 4xl -->
                    
                    <div class="bg-white px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-black text-[#0F396B]" id="modal-title">Ajouter un nouveau lot</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full p-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 sm:px-8 bg-gray-50/50">
                        <form>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <!-- Info Lot -->
                                <div class="space-y-4">
                                    <h4 class="text-[11px] font-extrabold text-[#1A5EAC] uppercase tracking-wider">Infos du Lot</h4>
                                    <div>
                                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Numéro du lot</label>
                                        <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: 001">
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Appartement / Étage</label>
                                        <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: A12, 3ème">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[13px] font-bold text-gray-700 mb-1">Tantièmes</label>
                                            <input type="number" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="100">
                                        </div>
                                        <div>
                                            <label class="block text-[13px] font-bold text-gray-700 mb-1">Surface (m²)</label>
                                            <input type="number" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="85">
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Propriétaire -->
                                <div class="space-y-4">
                                    <h4 class="text-[11px] font-extrabold text-[#1A5EAC] uppercase tracking-wider">Propriétaire</h4>
                                    <div>
                                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Nom complet</label>
                                        <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Jean Dupont">
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Téléphone</label>
                                        <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="06 00 00 00 00">
                                    </div>
                                    <div>
                                        <label class="block text-[13px] font-bold text-gray-700 mb-1">Email</label>
                                        <input type="email" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="email@exemple.com">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 sm:px-8">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="button" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1A5EAC] rounded-xl hover:bg-[#0F396B] shadow-md shadow-[#1A5EAC]/20 transition-all">
                            Enregistrer le lot
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
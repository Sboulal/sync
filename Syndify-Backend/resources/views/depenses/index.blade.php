<x-app-layout>
    
    <!-- 🟢 HEADER SECTION -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Dépenses</h1>
            <p class="text-blue-100 text-sm">Suivi des factures et interventions de la copropriété</p>
        </div>
        <div x-data>
            <button @click="$dispatch('open-modal')" class="mt-4 sm:mt-0 bg-white hover:bg-gray-50 text-[#0F396B] px-5 py-2.5 rounded-full text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nouvelle dépense
            </button>
        </div>
    </x-slot>

    <!-- 🟢 CONTAINER DYAL LA PAGE W L-MODAL -->
    <div x-data="{ showModal: false }" @open-modal.window="showModal = true" class="relative">

        <!-- 🟢 KPI: Total du mois (Inspiré du mobile) -->
        <div class="mb-6 bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total du mois</p>
                <p class="text-3xl font-black text-[#1A5EAC]">2 300,00 MAD</p>
            </div>
            <div class="hidden sm:block text-right">
                <p class="text-sm font-bold text-gray-600">Août 2026</p>
                <p class="text-xs text-gray-400">2 dépenses enregistrées</p>
            </div>
        </div>

        <!-- 🟢 MAIN CONTENT (Table 3la chkl Cards) -->
        <div class="bg-white/50 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            
            <!-- Toolbar (Search & Filtres) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                
                <div class="flex flex-wrap items-center gap-2">
                     <button class="px-5 py-2 bg-[#1A5EAC] text-white text-sm font-bold rounded-full shadow-md shadow-[#1A5EAC]/20">Ce mois</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Mois précédent</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Toutes</button>
                </div>

                <!-- Search -->
                <div class="relative w-full lg:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] sm:text-sm transition-all shadow-sm" placeholder="Rechercher une dépense, fournisseur...">
                </div>
            </div>

            <!-- 🟢 Table m9adda (Row = Card) -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-3">
                    <thead>
                        <tr>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Date & Catégorie</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Pièce Justificative</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Montant</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Ligne 1 -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1A5EAC] flex items-center justify-center shadow-inner">
                                        <!-- Icône Nettoyage (Balai) -->
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-black text-gray-900 block">05/08/2026</span>
                                        <span class="text-xs font-bold text-gray-500">Entretien</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Nettoyage des parties communes (Août)</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Réf: <span class="font-bold">FAC-2026-08-01</span></span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <button class="flex items-center gap-1.5 text-sm font-bold text-[#1A5EAC] hover:underline bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg> Voir PDF
                                </button>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 text-right">
                                <span class="text-lg font-black text-gray-800">800,00 MAD</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Ligne 2 -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center shadow-inner">
                                        <!-- Icône Réparation (Clé) -->
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-sm font-black text-gray-900 block">02/05/2026</span>
                                        <span class="text-xs font-bold text-gray-500">Maintenance</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Réparation Ascenseur Bâtiment A</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Fournisseur: <span class="font-bold">Otis</span></span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                 <button class="flex items-center gap-1.5 text-sm font-bold text-[#1A5EAC] hover:underline bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Voir Photo
                                </button>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 text-right">
                                <span class="text-lg font-black text-gray-800">1 500,00 MAD</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
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
                        <h3 class="text-lg font-black text-[#0F396B]" id="modal-title">Nouvelle Dépense</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full p-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 sm:px-8 bg-gray-50/50">
                        <form>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Description / Titre</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Réparation Ascenseur">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Catégorie</label>
                                     <select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-600">
                                        <option>Maintenance</option>
                                        <option>Entretien</option>
                                        <option>Eau & Électricité</option>
                                        <option>Assurance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Montant (MAD)</label>
                                    <input type="number" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: 3500">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Fournisseur (Opt)</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Otis">
                                </div>
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Référence (Opt)</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="REF-9082">
                                </div>
                                
                                <div class="sm:col-span-2 mt-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-2">Pièce justificative (Facture)</label>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                         <button type="button" class="flex-1 py-3 bg-white border-2 border-dashed border-gray-300 rounded-xl text-sm font-bold text-gray-500 hover:border-[#1A5EAC] hover:text-[#1A5EAC] transition-colors flex items-center justify-center gap-2">
                                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                             Prendre photo
                                         </button>
                                         <button type="button" class="flex-1 py-3 bg-blue-50 border border-blue-100 rounded-xl text-sm font-bold text-[#1A5EAC] hover:bg-[#1A5EAC] hover:text-white transition-colors flex items-center justify-center gap-2">
                                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                             Importer fichier
                                         </button>
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
                            Enregistrer dépense
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
<x-app-layout>
    
    <!-- 🟢 HEADER SECTION -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Cotisations</h1>
            <p class="text-blue-100 text-sm">Gestion et suivi des règlements des copropriétaires</p>
        </div>
        <div x-data>
            <button @click="$dispatch('open-modal')" class="mt-4 sm:mt-0 bg-white hover:bg-gray-50 text-[#0F396B] px-5 py-2.5 rounded-full text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nouveau paiement
            </button>
        </div>
    </x-slot>

    <!-- 🟢 CONTAINER DYAL LA PAGE W L-MODAL -->
    <div x-data="{ showModal: false }" @open-modal.window="showModal = true" class="relative">

        <!-- 🟢 KPI: Total encaissé (Inspiré du mobile) -->
        <div class="mb-6 bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-extrabold text-gray-400 uppercase tracking-wider mb-1">Total encaissé ce mois</p>
                <p class="text-3xl font-black text-[#137333]">15 000,00 MAD</p>
            </div>
            <div class="hidden sm:block text-right">
                <p class="text-sm font-bold text-gray-600">Avril 2026</p>
                <p class="text-xs text-gray-400">3 paiements enregistrés</p>
            </div>
        </div>

        <!-- 🟢 MAIN CONTENT (Table 3la chkl Cards) -->
        <div class="bg-white/50 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            
            <!-- Toolbar (Search & Filtres) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                
                <div class="flex flex-wrap items-center gap-2">
                     <button class="px-5 py-2 bg-[#1A5EAC] text-white text-sm font-bold rounded-full shadow-md shadow-[#1A5EAC]/20">Tous</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Virements</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Chèques</button>
                     <button class="px-5 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm">Espèces</button>
                </div>

                <!-- Search -->
                <div class="relative w-full lg:w-96">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] sm:text-sm transition-all shadow-sm" placeholder="Rechercher un résident, un lot...">
                </div>
            </div>

            <!-- 🟢 Table m9adda (Row = Card) -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-3">
                    <thead>
                        <tr>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Copropriétaire & Lot</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Mode de paiement</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Montant</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Ligne 1: Virement -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1A5EAC] flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 block">20/04/2026</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Ahmed Alaoui</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Lot: <span class="font-bold">14</span></span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-[#1A5EAC]">
                                    <span class="w-2 h-2 rounded-full bg-[#1A5EAC]"></span> Virement
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 text-right">
                                <span class="text-lg font-black text-[#137333]">+ 5 000,00 MAD</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors" title="Télécharger le reçu">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </button>
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors" title="Options">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Ligne 2: Chèque -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 block">10/02/2026</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Test Copro</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Lot: <span class="font-bold">12</span></span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-red-500">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Chèque
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 text-right">
                                <span class="text-lg font-black text-[#137333]">+ 7 500,00 MAD</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </button>
                                    <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Ligne 3: Virement -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl">
                                <div class="flex items-center gap-4">
                                     <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1A5EAC] flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 block">25/01/2026</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-xs">
                                <span class="text-sm font-bold text-[#0F396B] block truncate">Nabil Qadri</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Lot: <span class="font-bold">B01</span></span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-[#1A5EAC]">
                                    <span class="w-2 h-2 rounded-full bg-[#1A5EAC]"></span> Virement
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 text-right">
                                <span class="text-lg font-black text-[#137333]">+ 2 500,00 MAD</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-xl transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </button>
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
                        <h3 class="text-lg font-black text-[#0F396B]" id="modal-title">Enregistrer un paiement</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full p-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 sm:px-8 bg-gray-50/50">
                        <form>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Copropriétaire / Lot</label>
                                     <select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-900">
                                        <option>Sélectionnez un résident...</option>
                                        <option selected>Nabil Qadri (Lot: B01)</option>
                                        <option>Ahmed Alaoui (Lot: 14)</option>
                                        <option>Test Copro (Lot: 12)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Montant (MAD)</label>
                                    <input type="number" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: 2500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Mode de paiement</label>
                                     <select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-900">
                                        <option selected>Virement</option>
                                        <option>Chèque</option>
                                        <option>Espèces</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Référence (Optionnel)</label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: Chèque N°12345, ou VIR-098">
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 sm:px-8">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="button" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1A5EAC] rounded-xl hover:bg-[#0F396B] shadow-md shadow-[#1A5EAC]/20 transition-all uppercase tracking-wide">
                            ENREGISTRER LE PAIEMENT
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
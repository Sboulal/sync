<x-app-layout>
    
    <!-- 🟢 HEADER SECTION -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Documents</h1>
            <p class="text-blue-100 text-sm">Gestion et archivage des documents de la copropriété</p>
        </div>
        <div x-data>
            <button @click="$dispatch('open-modal')" class="mt-4 sm:mt-0 bg-white hover:bg-gray-50 text-[#0F396B] px-5 py-2.5 rounded-full text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Nouveau document
            </button>
        </div>
    </x-slot>

    <!-- 🟢 CONTAINER DYAL LA PAGE W L-MODAL -->
    <div x-data="{ showModal: false }" @open-modal.window="showModal = true" class="relative">

        <!-- 🟢 MAIN CONTENT (Table 3la chkl Cards) -->
        <div class="bg-white/50 backdrop-blur-sm rounded-3xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            
            <!-- Toolbar (Catégories = Dossiers & Search) -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8">
                
                <div class="flex flex-wrap items-center gap-2">
                     <button class="px-4 py-2 bg-[#1A5EAC] text-white text-sm font-bold rounded-full shadow-md shadow-[#1A5EAC]/20">Tous</button>
                     <button class="px-4 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm flex items-center gap-1.5"><svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Assemblées</button>
                     <button class="px-4 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm flex items-center gap-1.5"><svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Factures</button>
                     <button class="px-4 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm flex items-center gap-1.5"><svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Règlement</button>
                     <button class="px-4 py-2 bg-white text-gray-600 hover:text-[#1A5EAC] text-sm font-bold rounded-full transition-colors border border-gray-200 shadow-sm flex items-center gap-1.5"><svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Charges</button>
                </div>

                <!-- Search -->
                <div class="relative w-full xl:w-80">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" class="block w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-2xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] sm:text-sm transition-all shadow-sm" placeholder="Rechercher un document...">
                </div>
            </div>

            <!-- 🟢 Table m9adda (Row = Card) -->
            <div class="overflow-x-auto pb-4">
                <table class="w-full text-left border-separate border-spacing-y-3">
                    <thead>
                        <tr>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Fichier</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Détails du document</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Catégorie</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">Date d'ajout</th>
                            <th class="pb-2 px-6 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <!-- Ligne 1: AG -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl w-24">
                                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center shadow-inner">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-sm">
                                <span class="text-sm font-bold text-gray-900 block truncate">PV d'AG 2026</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Procès verbal de l'assemblée générale ordinaire.</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-600 bg-gray-50 px-3 py-1 rounded-lg border border-gray-200">
                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Assemblées générales
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-bold text-gray-700">12/10/2026</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-[#1A5EAC] hover:bg-[#1A5EAC] hover:text-white rounded-xl transition-colors" title="Télécharger">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </button>
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Ligne 2: Règlement -->
                        <tr class="bg-white shadow-sm hover:shadow-md transition-shadow group rounded-2xl">
                            <td class="py-4 px-6 border-y border-l border-gray-100 rounded-l-2xl w-24">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#1A5EAC] flex items-center justify-center shadow-inner">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100 max-w-sm">
                                <span class="text-sm font-bold text-gray-900 block truncate">Règlement de copropriété</span>
                                <span class="text-xs text-gray-500 block mt-1 truncate">Document officiel fixant les règles de la résidence.</span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-600 bg-gray-50 px-3 py-1 rounded-lg border border-gray-200">
                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg> Règlement
                                </span>
                            </td>
                            <td class="py-4 px-6 border-y border-gray-100">
                                <span class="text-sm font-bold text-gray-700">01/01/2026</span>
                            </td>
                            <td class="py-4 px-6 border-y border-r border-gray-100 rounded-r-2xl text-right">
                                <div class="flex items-center justify-end gap-2">
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-[#1A5EAC] hover:bg-[#1A5EAC] hover:text-white rounded-xl transition-colors" title="Télécharger">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </button>
                                     <button class="p-2 bg-gray-50 border border-gray-200 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
                        <h3 class="text-lg font-black text-[#0F396B] uppercase tracking-wide" id="modal-title">Nouveau Document</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full p-2 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 sm:px-8 bg-gray-50/50">
                        <form>
                            <div class="grid grid-cols-1 gap-5">
                                
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Nom du document <span class="text-red-500">*</span></label>
                                    <input type="text" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Ex: PV d'AG 2026">
                                </div>
                                
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Catégorie</label>
                                     <select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all text-gray-900">
                                        <option selected>Assemblées générales</option>
                                        <option>Factures</option>
                                        <option>Règlement</option>
                                        <option>Charges</option>
                                        <option>Autres</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-1">Description éventuelle</label>
                                    <textarea rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all resize-none placeholder-gray-400" placeholder="Saisissez une courte description..."></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-[13px] font-bold text-gray-700 mb-2">Fichier à publier <span class="text-red-500">*</span></label>
                                    <button type="button" class="w-full py-4 bg-blue-50/50 border-2 border-dashed border-[#1A5EAC]/30 rounded-xl text-sm font-bold text-[#1A5EAC] hover:bg-blue-50 hover:border-[#1A5EAC] transition-colors flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        Choisir un fichier
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>

                    <div class="bg-white px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 sm:px-8">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                            Annuler
                        </button>
                        <button type="button" class="px-6 py-2.5 text-sm font-bold text-white bg-[#1A5EAC] rounded-xl hover:bg-[#0F396B] shadow-md shadow-[#1A5EAC]/20 transition-all">
                            Publier
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
<x-app-layout>
    
    <!-- 🟢 Hna kan-passiw l-titre l-layout -->
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black mb-1">Tableau de bord</h1>
            <p class="text-blue-100 text-sm">Vue d'ensemble de la copropriété</p>
        </div>
        <!-- T9dri tzidi des boutons hna ila bghiti (b7al "Ajouter un lot") -->
    </x-slot>

    <!-- 🟢 KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">Total Lots</p>
            <p class="text-3xl font-black text-gray-800">48</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">Charges</p>
            <p class="text-3xl font-black text-[#1A5EAC]">125K</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">Encaissé</p>
            <p class="text-3xl font-black text-green-500">98.5K</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-2">Impayés</p>
            <p class="text-3xl font-black text-red-500">26.5K</p>
        </div>
    </div>

    <!-- 🟢 Suivi des charges + Incidents -->
    <div class="grid lg:grid-cols-3 gap-6 mb-8">

        <!-- Suivi des charges -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-black text-gray-800">Suivi des Charges</h2>
                <div class="flex items-center gap-4 text-xs font-bold text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#1A5EAC]"></span> Charges</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Encaissé</span>
                </div>
            </div>

            @php
                $chart = [
                    ['Jan', 70, 55], ['Fév', 80, 60], ['Mar', 65, 70],
                    ['Avr', 90, 75], ['Mai', 75, 80], ['Juin', 85, 65],
                    ['Juil', 60, 50], ['Août', 78, 72],
                ];
            @endphp

            <div class="flex items-end justify-between gap-3 h-56 border-b border-gray-100 pb-2">
                @foreach($chart as [$mois, $charges, $encaisse])
                    <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                        <div class="flex items-end gap-1 w-full justify-center h-full">
                            <div class="w-3 sm:w-4 rounded-t-md bg-[#1A5EAC]" style="height: {{ $charges }}%"></div>
                            <div class="w-3 sm:w-4 rounded-t-md bg-green-500" style="height: {{ $encaisse }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between gap-3 mt-2">
                @foreach($chart as [$mois])
                    <span class="flex-1 text-center text-xs font-bold text-gray-400">{{ $mois }}</span>
                @endforeach
            </div>
        </div>

        <!-- Incidents récents -->
        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <h2 class="text-lg font-black text-gray-800 mb-4">Incidents Récents</h2>
            <div class="space-y-3">
                @foreach([
                    ['Fuite d\'eau - Bât. A', 'Urgent', 'bg-red-50 text-red-600'],
                    ['Ascenseur en panne', 'En cours', 'bg-orange-50 text-orange-600'],
                    ['Éclairage parking', 'En cours', 'bg-orange-50 text-orange-600'],
                    ['Porte hall endommagée', 'Résolu', 'bg-green-50 text-green-600'],
                ] as [$titre, $statut, $classe])
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl bg-[#f4f7f9]">
                        <p class="text-sm font-bold text-gray-700 truncate">{{ $titre }}</p>
                        <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full {{ $classe }}">{{ $statut }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 🟢 Résidents + Documents -->
    <div class="grid lg:grid-cols-3 gap-6">

        <!-- Résidents -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-black text-gray-800">Résidents</h2>
                <a href="#" class="text-xs font-bold text-[#1A5EAC] hover:text-[#0F396B]">Voir tout</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            <th class="pb-3">Nom</th>
                            <th class="pb-3">Lot</th>
                            <th class="pb-3">Cotisation</th>
                            <th class="pb-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach([
                            ['Ahmed Benali', 'A-101', '1 200 MAD', 'Payé', 'bg-green-50 text-green-600'],
                            ['Sara El Idrissi', 'A-102', '1 200 MAD', 'Payé', 'bg-green-50 text-green-600'],
                            ['Youssef Alaoui', 'B-201', '1 500 MAD', 'En retard', 'bg-red-50 text-red-600'],
                            ['Fatima Zahra', 'B-202', '1 500 MAD', 'Payé', 'bg-green-50 text-green-600'],
                        ] as [$nom, $lot, $cotis, $statut, $classe])
                            <tr>
                                <td class="py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-[#1A5EAC] flex items-center justify-center text-xs font-black">
                                            {{ strtoupper(substr($nom, 0, 1)) }}
                                        </div>
                                        <span class="font-bold text-gray-700">{{ $nom }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-gray-500 font-medium">{{ $lot }}</td>
                                <td class="py-3 text-gray-500 font-medium">{{ $cotis }}</td>
                                <td class="py-3">
                                    <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full {{ $classe }}">{{ $statut }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documents -->
        <div class="bg-white p-6 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-gray-100">
            <h2 class="text-lg font-black text-gray-800 mb-4">Documents</h2>
            <div class="space-y-3">
                @foreach([
                    ['PV Assemblée Générale 2026', 'PDF'],
                    ['Règlement intérieur', 'PDF'],
                    ['Devis ascenseur', 'PDF'],
                    ['Facture électricité', 'PDF'],
                ] as [$titre, $type])
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-[#f4f7f9]">
                        <div class="w-9 h-9 rounded-lg bg-blue-100 text-[#1A5EAC] flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="flex-1 text-sm font-bold text-gray-700 truncate">{{ $titre }}</p>
                        <span class="text-[10px] font-black text-gray-400">{{ $type }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</x-app-layout>
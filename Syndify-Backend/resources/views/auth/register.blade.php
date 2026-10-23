<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription - Syndify</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-white min-h-screen flex">

    <!-- 🟢 LEFT SIDE : Tswira l-jdida (bg-left bash tban m9ada w l-byed ytkhba) -->
    <div class="hidden lg:block lg:w-1/2 bg-cover bg-left bg-no-repeat" style="background-image: url('{{ asset('images/bg-illustration.jpg') }}');">
        <!-- Khawya 7it l-logo w l-ktba aslan mssawbin f west l-tswira -->
    </div>

    <!-- 🟢 RIGHT SIDE : Formulaire (Bla itar w bla shadow) -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-6 sm:p-8 lg:p-12 overflow-y-auto">
        <div class="w-full max-w-md my-8">
            
            <!-- Mobile Logo -->
            <div class="lg:hidden flex justify-center mb-8">
                <div class="w-16 h-16 bg-[#1A5EAC] rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 2H9c-1.1 0-2 .9-2 2v2H5c-1.1 0-2 .9-2 2v14h18V4c0-1.1-.9-2-2-2zm-8 2h8v16h-8V4zm-6 6h4v10H5v-10z"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-3xl font-extrabold text-[#1A5EAC] text-center mb-2">Créer un compte</h2>
            <p class="text-sm text-gray-500 text-center mb-8">Remplissez les informations ci-dessous.</p>

            <form method="POST" action="{{ route('register') }}" x-data="{ role: 'coproprietaire' }" class="space-y-4">
                @csrf

                <!-- Nom & Prénom -->
                <div>
                    <label for="name" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Nom & Prénom</label>
                    <input id="name" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Ex: Nabil Qadri" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Téléphone -->
                <div>
                    <label for="tel" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Téléphone</label>
                    <input id="tel" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-[#1A5EAC] transition-colors" type="text" name="tel" :value="old('tel')" required autocomplete="tel" placeholder="06XXXXXXXX" />
                    <x-input-error :messages="$errors->get('tel')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Email</label>
                    <input id="email" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="contact@exemple.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Choix du Rôle (Copropriétaire ou Syndic) -->
                <div class="pt-2 pb-1">
                    <label class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-3">Type d'accès</label>
                    <div class="flex items-center space-x-6 bg-gray-50 p-3 rounded-xl border border-gray-200">
                        <label class="flex items-center text-sm font-bold text-[#1A5EAC] cursor-pointer">
                            <input type="radio" name="role" value="coproprietaire" x-model="role" class="w-4 h-4 border-gray-300 text-[#1A5EAC] shadow-sm focus:ring-[#1A5EAC]">
                            <span class="ml-2">Copropriétaire</span>
                        </label>
                        <label class="flex items-center text-sm font-bold text-[#1A5EAC] cursor-pointer">
                            <input type="radio" name="role" value="syndic" x-model="role" class="w-4 h-4 border-gray-300 text-[#1A5EAC] shadow-sm focus:ring-[#1A5EAC]">
                            <span class="ml-2">Syndic</span>
                        </label>
                    </div>
                </div>

                <!-- 🟢 Champs Dynamiques -->
                
                <!-- Si Copropriétaire : Code Résidence -->
                <div class="transition-all duration-300" x-show="role === 'coproprietaire'" x-cloak>
                    <label for="code_residence" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Code de la résidence</label>
                    <input id="code_residence" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="text" name="code_residence" :value="old('code_residence')" placeholder="Ex: SYND-8F4A9Z" />
                    <x-input-error :messages="$errors->get('code_residence')" class="mt-2" />
                </div>

                <!-- Si Syndic : Nom Résidence -->
                <div class="transition-all duration-300" x-show="role === 'syndic'" x-cloak>
                    <label for="nom_residence" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Nom de la résidence</label>
                    <input id="nom_residence" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="text" name="nom_residence" :value="old('nom_residence')" placeholder="Ex: Résidence Les Jardins" />
                    <x-input-error :messages="$errors->get('nom_residence')" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Mot de passe</label>
                    <input id="password" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-1">Confirmer mot de passe</label>
                    <input id="password_confirmation" class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Bouton Inscription -->
                <div class="pt-4">
                    <button type="submit" class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-[15px] font-bold text-white bg-[#1A5EAC] hover:bg-[#0b2b52] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A5EAC] transition-all transform hover:-translate-y-0.5">
                        S'inscrire
                    </button>
                </div>

                <!-- Login Link -->
                <div class="mt-6 text-center text-sm text-gray-600">
                    Déjà un compte ? 
                    <a href="{{ route('login') }}" class="font-bold text-[#1A5EAC] hover:underline ml-1">
                        Se connecter
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
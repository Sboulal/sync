<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Syndify</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-white min-h-screen flex">

    <!-- 🟢 LEFT SIDE : Tswira l-jdida -->
    <!-- Dernaha 'bg-left' bash tched ghir jiha zr9a w l-byed ytkhba te7t l-formulaire -->
    <div class="hidden lg:block lg:w-1/2 bg-cover bg-left bg-no-repeat" style="background-image: url('{{ asset('images/bg-illustration.jpg') }}');">
        <!-- Khlina had div khawya 7it l-logo w l-ktba deja kaynin mssawbin f west l-tswira li 3titini -->
    </div>

    <!-- 🟢 RIGHT SIDE : Formulaire -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-white p-8 sm:p-12 lg:p-24">
        <div class="w-full max-w-md">
            
            <!-- Mobile Logo (kayban ghir f tilifon 7it tswira makatbanch) -->
            <div class="lg:hidden flex justify-center mb-8">
                <div class="w-16 h-16 bg-[#1A5EAC] rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 2H9c-1.1 0-2 .9-2 2v2H5c-1.1 0-2 .9-2 2v14h18V4c0-1.1-.9-2-2-2zm-8 2h8v16h-8V4zm-6 6h4v10H5v-10z"/>
                        <path d="M13 14h2v2h-2zm0-4h2v2h-2zm4 4h2v2h-2zm0-4h2v2h-2zM7 14h2v2H7zm0-4h2v2H7z"/>
                    </svg>
                </div>
            </div>

            <h2 class="text-3xl font-extrabold text-[#1A5EAC] text-center mb-2">Bienvenue !</h2>
            <p class="text-sm text-gray-500 text-center mb-10">Connectez-vous à votre espace personnel.</p>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email ou Téléphone -->
                <div>
                    <label for="email" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide mb-2">Email ou Téléphone</label>
                    <input id="email" class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Entrez votre identifiant" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Mot de passe -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-xs font-bold text-[#1A5EAC] uppercase tracking-wide">Mot de passe</label>
                        @if (Route::has('password.request'))
                            <a class="text-xs font-semibold text-[#1A5EAC] hover:text-[#0b2b52] transition-colors" href="{{ route('password.request') }}">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <input id="password" class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] text-sm text-gray-900 transition-colors" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center pt-1">
                    <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#1A5EAC] shadow-sm focus:ring-[#1A5EAC]" name="remember">
                    <label for="remember_me" class="ml-2 block text-sm font-medium text-gray-600">
                        Se souvenir de moi
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-md text-[15px] font-bold text-white bg-[#1A5EAC] hover:bg-[#0b2b52] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1A5EAC] transition-all transform hover:-translate-y-0.5">
                        Se connecter
                    </button>
                </div>
                
                <div class="mt-6 text-center text-sm text-gray-600">
                    Pas encore de compte ? 
                    <a href="{{ route('register') }}" class="font-bold text-[#1A5EAC] hover:underline ml-1">
                        S'inscrire
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
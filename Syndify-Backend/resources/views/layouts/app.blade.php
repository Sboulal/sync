<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Syndify</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-800">

    <div x-data="{ sidebarOpen: false, profileDropdown: false, notifDropdown: false }" class="flex h-screen overflow-hidden bg-[#f4f7f9]">

        <!-- 🟢 OVERLAY MOBILE -->
        <div x-show="sidebarOpen" 
             x-transition.opacity.duration.300ms
             @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" x-cloak>
        </div>

        <!-- 🟢 SIDEBAR -->
        <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-white flex flex-col shadow-2xl transition-transform duration-300 transform lg:translate-x-0 lg:static"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="bg-[#0F396B] p-6 pt-12 flex flex-col justify-end min-h-[180px] relative">
                <button @click="sidebarOpen = false" class="absolute top-4 right-4 text-white/60 hover:text-white lg:hidden">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <svg class="w-7 h-7 text-[#0F396B]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 2H9c-1.1 0-2 .9-2 2v2H5c-1.1 0-2 .9-2 2v14h18V4c0-1.1-.9-2-2-2zm-8 2h8v16h-8V4zm-6 6h4v10H5v-10z"/>
                        <path d="M13 14h2v2h-2zm0-4h2v2h-2zm4 4h2v2h-2zm0-4h2v2h-2zM7 14h2v2H7zm0-4h2v2H7z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white tracking-wide">Syndify Menu</h2>
            </div>

         <nav class="flex-1 overflow-y-auto py-4 space-y-1">
    
    <!-- Accueil (Dashboard) -->
    <a href="{{ route('dashboard') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('dashboard') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Accueil
    </a>

    <!-- Copropriété -->
    <a href="{{ route('coproprietes.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('coproprietes.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Copropriété
    </a>

       <!-- Listes des Lots -->
    <a href="{{ route('lots.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('appels_fonds.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Listes des Lots
    </a>

    <!-- Assemblées -->
    <a href="{{ route('assemblees.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('assemblees.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Assemblées
    </a>

   
    <!-- Dépenses -->
    <a href="{{ route('depenses.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('depenses.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Dépenses
    </a>

    <!-- Annonces -->
    <a href="{{ route('annonces.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('annonces.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        Annonces
    </a>

    <!-- Paiements (Cotisations) -->
    <a href="{{ route('cotisations.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('cotisations.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        Paiements
    </a>

    <!-- Charges (Appels de fonds) -->
    <a href="{{ route('appels_fonds.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('appels_fonds.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Charges
    </a>

   <!-- Documents -->
    <a href="{{ route('documents.index') }}" class="flex items-center gap-4 px-6 py-3 font-bold transition-colors border-l-4 {{ request()->routeIs('documents.*') ? 'text-[#1A5EAC] bg-blue-50/50 border-[#1A5EAC]' : 'text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900 border-transparent' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Documents
    </a>

    
</nav>
        </aside>

        <!-- 🟢 MAIN WRAPPER -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <!-- 🟢 NAVBAR FIXE -->
            <header class="h-20 bg-white/90 backdrop-blur-lg border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 z-30 absolute top-0 w-full shadow-sm">
                <div class="flex items-center gap-4 flex-1">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-gray-500 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-lg transition-colors">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>

                    <div class="hidden sm:flex flex-1 items-center bg-gray-100 hover:bg-gray-200/60 border border-transparent focus-within:bg-white focus-within:border-[#1A5EAC] focus-within:ring-4 focus-within:ring-[#1A5EAC]/10 rounded-xl px-4 py-2.5 transition-all mr-4">
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Rechercher un lot, un copropriétaire, une facture..." class="bg-transparent border-none focus:ring-0 text-sm ml-3 w-full text-gray-800 placeholder-gray-400 outline-none">
                        <div class="hidden sm:flex items-center gap-1 border border-gray-200 bg-white px-2 py-0.5 rounded text-[10px] font-bold text-gray-400 ml-2">
                            <span>⌘</span><span>K</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                    <button class="relative p-2.5 text-gray-400 hover:text-[#1A5EAC] hover:bg-blue-50 rounded-full transition-colors">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                    <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>
                    <div class="relative">
                        <button @click="profileDropdown = !profileDropdown" @click.away="profileDropdown = false" class="flex items-center gap-3 p-1 pr-2 rounded-full hover:bg-gray-50 transition-colors">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ Auth::user()->name ?? 'Syndic' }}</p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Admin</p>
                            </div>
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Syndic') }}&background=1A5EAC&color=fff" class="w-9 h-9 rounded-full shadow-sm" alt="Avatar">
                        </button>
                        <div x-show="profileDropdown" x-cloak class="absolute right-0 mt-3 w-48 bg-white border border-gray-100 rounded-xl shadow-lg py-2 z-50">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-3 px-4 py-2 w-full text-left text-sm font-bold text-red-600 hover:bg-red-50">
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto w-full pt-20">
                <div class="relative bg-[#0F396B] w-full pt-10 pb-32 px-6 lg:px-10 bg-cover bg-center" style="background-image: url('{{ asset('images/bg-illustration.png') }}');">
                    <div class="absolute inset-0 bg-[#0F396B]/70"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-[#f4f7f9] to-transparent top-1/2"></div>
                    
                    <!-- 🟢 HNA FIN ZDNA L-VARIABLE $header -->
                    @if (isset($header))
                        <div class="relative z-10 text-white flex justify-between items-center">
                            {{ $header }}
                        </div>
                    @endif

                </div>

                <div class="relative z-20 px-6 lg:px-10 -mt-20 pb-12">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
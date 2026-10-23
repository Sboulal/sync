<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Syndify - La gestion de copropriété simplifiée</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-800 bg-[#f4f7f9]">

    <!-- 🟢 NAVBAR -->
    <header class="fixed top-0 w-full bg-white/90 backdrop-blur-md border-b border-gray-100 z-50 transition-all">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-[#0F396B] rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="text-2xl font-black text-[#0F396B] tracking-tight">Syndify</span>
            </div>

            <!-- Liens de navigation (Desktop) -->
            <nav class="hidden md:flex items-center gap-8">
                <a href="#accueil" class="text-sm font-bold text-gray-900 hover:text-[#1A5EAC] transition-colors">Accueil</a>
                <a href="#apropos" class="text-sm font-bold text-gray-600 hover:text-[#1A5EAC] transition-colors">À propos</a>
                <a href="#app" class="text-sm font-bold text-gray-600 hover:text-[#1A5EAC] transition-colors">L'Application</a>
                <a href="#contact" class="text-sm font-bold text-gray-600 hover:text-[#1A5EAC] transition-colors">Contact</a>
            </nav>

            <!-- Boutons Connexion / Inscription -->
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-bold text-[#1A5EAC] hover:text-[#0F396B] transition-colors">Accéder au Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:block text-sm font-bold text-gray-600 hover:text-[#1A5EAC] transition-colors">Se connecter</a>
                    <a href="{{ route('register') }}" class="bg-[#1A5EAC] hover:bg-[#0F396B] text-white px-5 py-2.5 rounded-full text-sm font-bold shadow-md shadow-[#1A5EAC]/20 transition-all">Créer un compte</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- 🟢 HERO SECTION (Accueil avec Dashboard Web/Desktop Frame) -->
    <section id="accueil" class="pt-32 pb-20 lg:pt-48 lg:pb-32 bg-gradient-to-b from-white to-[#f4f7f9] overflow-hidden relative">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col lg:flex-row items-center gap-16 relative z-10">
            <div class="flex-1 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-[#1A5EAC] text-xs font-black uppercase tracking-wider mb-6 border border-blue-100">
                    <span class="w-2 h-2 rounded-full bg-[#1A5EAC] animate-pulse"></span> Lancement officiel
                </span>
                <h1 class="text-5xl lg:text-6xl font-black text-[#0F396B] leading-tight mb-6">
                    Gérez votre <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#1A5EAC] to-blue-400">copropriété</span> en toute simplicité.
                </h1>
                <p class="text-lg text-gray-500 mb-8 max-w-2xl mx-auto lg:mx-0 font-medium">
                    Syndify connecte syndics et résidents. Suivez vos charges, signalez des incidents et accédez aux documents importants directement depuis votre smartphone ou ordinateur.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="{{ route('register') }}" class="w-full sm:w-auto bg-[#1A5EAC] hover:bg-[#0F396B] text-white px-8 py-4 rounded-full text-base font-bold shadow-xl shadow-[#1A5EAC]/30 transition-all flex items-center justify-center gap-2">
                        Commencer gratuitement
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="#app" class="w-full sm:w-auto bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-700 px-8 py-4 rounded-full text-base font-bold transition-all flex items-center justify-center">
                        Découvrir l'App
                    </a>
                </div>
            </div>
            
            <!-- 🖥️ Hero Web Dashboard Frame -->
            <div class="flex-1 relative w-full max-w-2xl lg:max-w-none">
                <div class="absolute inset-0 bg-gradient-to-tr from-blue-300 to-blue-100 rounded-full blur-3xl opacity-40 transform scale-105 -z-10"></div>
                
                <!-- Mockup Window Browser -->
                <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden transform hover:-translate-y-1 transition-transform duration-500">
                    <!-- Browser Bar -->
                    <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        <div class="ml-4 bg-white rounded-md px-3 py-1 text-xs text-gray-400 border border-gray-200 flex-1 max-w-xs font-mono">
                            https://syndify.ma/dashboard
                        </div>
                    </div>
                    <!-- Dashboard Image -->
                    <img src="{{ asset('images/dashboard.png') }}" alt="Syndify Dashboard" class="w-full h-auto object-cover">
                </div>
            </div>
        </div>
    </section>

    <!-- 🟢 À PROPOS -->
    <section id="apropos" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl lg:text-4xl font-black text-[#0F396B] mb-4">Pourquoi choisir Syndify ?</h2>
                <p class="text-gray-500 font-medium">Une solution complète pensée pour faciliter la vie des syndics et améliorer la transparence pour les copropriétaires.</p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-[#f4f7f9] p-8 rounded-3xl border border-gray-100 hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 text-[#1A5EAC]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Transparence financière</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Suivez les cotisations, les dépenses et le solde de la copropriété en temps réel. Fini les mauvaises surprises.</p>
                </div>
                <div class="bg-[#f4f7f9] p-8 rounded-3xl border border-gray-100 hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 text-[#1A5EAC]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Communication rapide</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Recevez des notifications push pour les assemblées générales, les pannes d'ascenseur ou les urgences.</p>
                </div>
                <div class="bg-[#f4f7f9] p-8 rounded-3xl border border-gray-100 hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 text-[#1A5EAC]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Documents centralisés</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Accédez à tout moment aux factures, devis, règlements intérieurs et PV d'assemblées générales.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 🟢 APPLICATION MOBILE (Tsawer Mobile dyal Flutter) -->
    <section id="app" class="py-24 bg-[#0F396B] relative overflow-hidden">
        <div class="absolute inset-0 bg-blue-900/50 backdrop-blur-3xl"></div>
        <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <div class="flex-1 text-center lg:text-left">
                    <h2 class="text-3xl lg:text-4xl font-black text-white mb-6">Votre résidence dans votre poche</h2>
                    <p class="text-blue-100 text-lg mb-8 max-w-lg mx-auto lg:mx-0">
                        Téléchargez l'application mobile Syndify pour Android et iOS. Une interface intuitive conçue avec Flutter pour gérer vos charges et consulter les annonces n'importe où.
                    </p>
                    <div class="flex items-center justify-center lg:justify-start gap-4">
                        <button class="bg-black hover:bg-gray-900 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-colors border border-gray-800">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.04 2.26-.74 3.58-.79 2.1-.12 3.82.91 4.88 2.51-4.04 2.37-3.28 7.38.74 8.92-1.02 2.61-3.01 4.79-4.28 1.53M12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
                            <div class="text-left">
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Télécharger sur</p>
                                <p class="text-sm font-bold leading-tight">App Store</p>
                            </div>
                        </button>
                        <button class="bg-black hover:bg-gray-900 text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-colors border border-gray-800">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M3.6 20.9l11.8-11.8-2.6-2.6L3.6 20.9zm13.1-13.1l2.5 2.5-2.5 2.5-2.5-2.5 2.5-2.5zm2.8 2.8l2.9 1.7c.8.5.8 1.2 0 1.6l-2.9 1.7-2.8-2.8 2.8-2.2zM2.5 3.4l11.4 11.4 2.5-2.5L3.6 2.1C3.1 1.7 2.5 1.9 2.5 3.4z"/></svg>
                            <div class="text-left">
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Disponible sur</p>
                                <p class="text-sm font-bold leading-tight">Google Play</p>
                            </div>
                        </button>
                    </div>
                </div>
                
                <!-- 📱 Tsawer Mobile -->
                <div class="flex-1 flex justify-center lg:justify-end gap-4 relative">
                    <!-- Screen 1 -->
                    <div class="w-48 sm:w-56 bg-white rounded-[2.5rem] p-2 shadow-2xl border-4 border-gray-800 transform -rotate-6 translate-y-8 z-10">
                        <div class="bg-gray-100 rounded-[2rem] overflow-hidden aspect-[9/19]">
                            <img src="{{ asset('images/a.jpeg') }}" class="w-full h-full object-cover" alt="App Screen 1">
                        </div>
                    </div>
                    <!-- Screen 2 -->
                    <div class="w-48 sm:w-56 bg-white rounded-[2.5rem] p-2 shadow-2xl border-4 border-gray-800 transform rotate-6 -translate-y-4">
                        <div class="bg-gray-100 rounded-[2rem] overflow-hidden aspect-[9/19]">
                            <img src="{{ asset('images/b.jpeg') }}" class="w-full h-full object-cover" alt="App Screen 2">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 🟢 CONTACT -->
    <section id="contact" class="py-24 bg-[#f4f7f9]">
        <div class="max-w-4xl mx-auto px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 overflow-hidden flex flex-col md:flex-row">
                <div class="bg-[#1A5EAC] p-10 text-white md:w-1/3 flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-black mb-2">Contactez-nous</h3>
                        <p class="text-blue-100 text-sm mb-8">Une question ? Notre équipe est là pour vous aider.</p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <svg class="w-5 h-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="text-sm font-bold">contact@syndify.ma</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <svg class="w-5 h-5 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span class="text-sm font-bold">+212 5 00 00 00 00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-10 md:w-2/3">
                    <form class="space-y-5">
                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nom complet</label>
                                <input type="text" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="Jean Dupont">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                                <input type="email" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all" placeholder="email@exemple.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Message</label>
                            <textarea rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#1A5EAC]/20 focus:border-[#1A5EAC] transition-all resize-none" placeholder="Comment pouvons-nous vous aider ?"></textarea>
                        </div>
                        <button type="button" class="w-full bg-[#0F396B] hover:bg-[#1A5EAC] text-white font-bold py-3 rounded-xl transition-colors shadow-md">
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- 🟢 FOOTER -->
    <footer class="bg-white border-t border-gray-100 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-12">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-[#0F396B] rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <span class="text-xl font-black text-[#0F396B] tracking-tight">Syndify</span>
                </div>
                
                <div class="flex flex-wrap justify-center gap-6">
                    <a href="#" class="text-sm font-bold text-gray-500 hover:text-[#1A5EAC] transition-colors">Politique de confidentialité</a>
                    <a href="#" class="text-sm font-bold text-gray-500 hover:text-[#1A5EAC] transition-colors">Conditions générales (CGU)</a>
                    <a href="#" class="text-sm font-bold text-gray-500 hover:text-[#1A5EAC] transition-colors">Mentions légales</a>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-xs text-gray-400 font-medium">
                    &copy; {{ date('Y') }} Syndify. Tous droits réservés.
                </p>
                <div class="flex items-center gap-4">
                    <a href="#" class="text-gray-400 hover:text-[#1A5EAC]"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg></a>
                    <a href="#" class="text-gray-400 hover:text-[#1A5EAC]"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
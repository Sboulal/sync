<section class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <header>
        <h2 class="text-xl font-semibold text-gray-900">
            {{ __('Informations du Profil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            {{ __("Mettez à jour les informations de votre compte (Nom, Téléphone, et adresse e-mail).") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nom Complet (M-adapté m3a base de données) -->
            <div>
                <x-input-label for="full_name" :value="__('Nom Complet')" />
                <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full transition duration-150 ease-in-out focus:ring-indigo-500 focus:border-indigo-500" :value="old('full_name', $user->full_name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
            </div>

            <!-- Téléphone -->
            <div>
                <x-input-label for="tel" :value="__('Téléphone')" />
                <x-text-input id="tel" name="tel" type="text" class="mt-1 block w-full transition duration-150 ease-in-out focus:ring-indigo-500 focus:border-indigo-500" :value="old('tel', $user->tel)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('tel')" />
            </div>
        </div>

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Adresse Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full transition duration-150 ease-in-out focus:ring-indigo-500 focus:border-indigo-500" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Votre adresse e-mail n\'est pas vérifiée.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Cliquez ici pour renvoyer l\'e-mail de vérification.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('Un nouveau lien de vérification a été envoyé.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
            <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">
                {{ __('Enregistrer les modifications') }}
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-medium text-green-600 bg-green-50 px-3 py-1 rounded-full"
                >{{ __('Profil mis à jour.') }}</p>
            @endif
        </div>
    </form>
</section>
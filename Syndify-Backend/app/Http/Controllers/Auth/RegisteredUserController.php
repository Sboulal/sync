<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validation dyal les champs
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'tel' => ['required', 'string', 'max:20'],
            'role' => ['required', 'in:syndic,coproprietaire'],
        ]);

        // 2. Création dyal l-user
        $user = User::create([
            'identifier' => Str::uuid(),
            'full_name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tel' => $request->tel,
            'role' => $request->role,
            'agreed_on_terms' => true,
            'status' => 'active', 
        ]);

        event(new Registered($user));

        // 🟢 7IYDINA Auth::login($user); BASH MAYDKHOLCH AUTOMATIQUEMENT

        // 🟢 REDIRECTION VERS LOGIN M3A MESSAGE DYAL SUCCÈS
        return redirect()->route('login')->with('status', 'Compte créé avec succès ! Veuillez vous connecter.');
    }
}
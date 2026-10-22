<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WebStandardAuthController extends Controller
{
    // --- Inscription Web ---
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Token khass bel Web
        $token = $user->createToken('web-token')->plainTextToken;

        return response()->json([
            'message' => 'Compte web créé avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // --- Connexion Web ---
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email awla mot de passe ghalat.'],
            ]);
        }

        $token = $user->createToken('web-token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion web réussie',
            'user' => $user,
            'token' => $token
        ]);
    }

    // --- Déconnexion Web ---
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }
}
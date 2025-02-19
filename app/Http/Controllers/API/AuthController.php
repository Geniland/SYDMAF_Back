<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur.
     */
    public function register(Request $request)
    {
        // Validation des données de la requête
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'secret_code' => 'nullable|string', // Code d'administration facultatif
            'phone' => 'required|string|max:15', // Numéro de téléphone obligatoire
        ]);

        // Définition du rôle
        $role = isset($validated['secret_code']) && $validated['secret_code'] === config('app.admin_secret_code') ? 'admin' : 'user';
        // dd($role);




        // Création de l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'], // Ajout du téléphone
            'role' => $role, // Attribuer le rôle
        ]);

        // Création du token d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion de l'utilisateur.
     */
    public function login(Request $request)
    {
        // Validation des données de la requête
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        // Vérification des identifiants
        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Récupérer l'utilisateur authentifié
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone, // Ajout du téléphone dans la réponse
                'role' => $user->role,
            ],
        ], 200);
    }

    /**
     * Récupération des informations de l'utilisateur authentifié.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Déconnexion de l'utilisateur.
     */
    public function logout(Request $request)
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}

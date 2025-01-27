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

        // dd($validated); 
        // dd($validated['secret_code'], env('ADMIN_SECRET_CODE'));
        //    dd($validated['phone']);
        // dd($role);

        
        // Vérifier le rôle

        $role = 'user'; // Rôle par défaut
        if (isset($validated['secret_code']) && $validated['secret_code'] === env('ADMIN_SECRET_CODE')) {
            $role = 'admin';
        } else{
            $role = 'user';
        } 

        // Création de l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role, // Attribuer le rôle
        ]);

        // Retour de la réponse avec les données de l'utilisateur et un token API
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion de l'utilisateur pour obtenir un token.
     */
    public function login(Request $request)
    {
        // Validation des données de la requête
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        // Tentative d'authentification de l'utilisateur
        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, // Ajout du rôle
            ],
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => ['Les informations d\'identification sont incorrectes'],
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur authentifié.
     */
    public function user(Request $request)
    {
        // Récupérer l'utilisateur connecté
        return response()->json($request->user());
    }

    /**
     * Déconnexion de l'utilisateur et révoquer le token.
     */
    public function logout(Request $request)
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Déconnexion réussie']);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BoutiqueController extends Controller
{

   
    // Liste toutes les boutiques
    public function index()
    {
        $boutiques = Boutique::all();
        return response()->json($boutiques);
    }

    // Crée une nouvelle boutique
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:255',
            'logo'        => 'nullable|image',
            'description' => 'nullable|string',
        ]);

        // Upload du logo si fourni
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        }

        // Vérifie si l'utilisateur est authentifié
        $userId = Auth::user();
        if (!$userId) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

       // Création de la boutique
        $boutique = Boutique::create([
            'nom' => $request->nom,
            'logo' => $request->logo,
            'description' => $request->description,
            'user_id' => $userId, // Récupère automatiquement l'ID de l'utilisateur connecté
        ]);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'boutique' => $boutique
        ], 201);
    }

    // Affiche une boutique spécifique
    public function show($id)
    {
        $boutique = Boutique::findOrFail($id);
        return response()->json($boutique);
    }

    // Met à jour une boutique
    public function update(Request $request, $id)
    {
        $boutique = Boutique::findOrFail($id);

        // Vérifie que l'utilisateur est bien le propriétaire
        if (Auth::id() !== $boutique->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'nom'         => 'required|string|max:255',
            'logo'        => 'nullable|image',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            // Suppression de l'ancien logo si existant
            if ($boutique->logo) {
                Storage::disk('public')->delete($boutique->logo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        }

        $boutique->update($data);
        return response()->json($boutique);
    }

    // Supprime une boutique
    public function destroy($id)
    {
        $boutique = Boutique::findOrFail($id);

        if (Auth::id() !== $boutique->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($boutique->logo) {
            Storage::disk('public')->delete($boutique->logo);
        }

        $boutique->delete();
        return response()->json(['message' => 'Boutique supprimée avec succès']);
    }
}

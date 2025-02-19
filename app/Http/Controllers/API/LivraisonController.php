<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Livraison;
use Illuminate\Support\Facades\Validator;

class LivraisonController extends Controller
{
    /**
     * Enregistrer une nouvelle commande
     */
    public function store(Request $request)
    {
        // Validation des données reçues
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'instructions' => 'nullable|string',
            'products' => 'required|array',
            'products.*.name' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Création de la commande
        $livraison = Livraison::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'instructions' => $request->instructions,
            'products' => $request->products, // Stocker le JSON
        ]);

        return response()->json([
            'message' => 'Commande enregistrée avec succès',
            'livraison' => $livraison,
        ], 201);
    }

    /**
     * Récupérer toutes les commandes
     */
    public function index()
    {
        return response()->json(Livraison::all());
    }
}

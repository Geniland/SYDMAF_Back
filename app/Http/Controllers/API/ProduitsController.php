<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; // Assurez-vous d'utiliser le bon namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Produits; // Modèle lié au contrôleur
use Illuminate\Support\Facades\Log;

class ProduitsController extends Controller
{
    /**
     * Ajout d'un nouveau produit avec une image.
     */
    public function store(Request $request)
    {
        Log::info('Requête reçue :', $request->except('image'));

        // Validation des données reçues
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'categories_id' => 'required|exists:categories,id', // Validation de la catégorie
        ]);

        // Upload de l'image
        $path = $request->file('image')->store('produits', 'public');

        // Création du produit dans la base de données
        $product = Produits::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image_path' => $path,
            'categories_id' => $validated['categories_id'],
        ]);

        // Retourne une réponse JSON
        return response()->json([
            'message' => 'Produit ajouté avec succès',
            'product' => $product,
        ], 201);
    }

    /**
     * Liste tous les produits.
     */
    public function index()
    {
        $produits = Produits::with('Categories')->get();
    
        return response()->json([
            'message' => 'Liste des produits récupérée avec succès',
            'produits' => $produits,
        ], 200);
    }

    /**
     * Récupère un produit spécifique.
     */
    public function show($id)
    {
        $product = Produits::findOrFail($id);

        return response()->json($product);
    }

    /**
     * Met à jour un produit spécifique.
     */
    public function update(Request $request, $id)
    {
        $product = Produits::findOrFail($id);

        // Validation des données mises à jour
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            
        ]);

        // Mettre à jour l'image si une nouvelle est fournie
        if ($request->hasFile('image')) {
            // Supprime l'ancienne image si elle existe
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            // Upload de la nouvelle image
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        // Mise à jour du produit dans la base de données
        $product->update($validated);

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'product' => $product,
        ]);
    }

    /**
     * Supprime un produit.
     */
    public function destroy($id)
    {
        $product = Produits::findOrFail($id);

        // Suppression de l'image du stockage
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }

    public function produitsParCategorie($categoryId)
{
    // Récupérer les produits appartenant à une catégorie spécifique
    $produits = Produits::where('category_id', $categoryId)->with('category')->get();

    if ($produits->isEmpty()) {
        return response()->json(['message' => 'Aucun produit trouvé pour cette catégorie'], 404);
    }

    return response()->json($produits, 200);
}


public function produitsMemeCategorie($produitId)
{
    // Récupérer le produit demandé
    $produit = Produits::find($produitId);

    if (!$produit) {
        return response()->json(['message' => 'Produit non trouvé'], 404);
    }

    // Récupérer les produits de la même catégorie, sauf le produit lui-même
    $produitsMemeCategorie = Produits::where('category_id', $produit->category_id)
        ->where('id', '!=', $produitId)
        ->get();

    if ($produitsMemeCategorie->isEmpty()) {
        return response()->json(['message' => 'Aucun autre produit trouvé dans cette catégorie'], 404);
    }

    return response()->json($produitsMemeCategorie, 200);
}

}

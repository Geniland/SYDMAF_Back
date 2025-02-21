<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; // Assurez-vous d'utiliser le bon namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Produits; // Modèle lié au contrôleur
use App\Models\Categories; // Modèle lié au contrôleur
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProduitsController extends Controller
{
    /**
     * Ajout d'un nouveau produit avec une image.
     */
    public function store(Request $request)
    {
        Log::info('Requête reçue :', $request->except('image'));

        try {
            DB::connection()->getPdo();
            Log::info("Connexion à la base de données réussie.");
        } catch (\Exception $e) {
            Log::error("Impossible de se connecter à la base de données", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur de connexion à la base de données'], 500);
        }

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

            // Vérifier si l'ID est bien généré
        if (!$product->id) {
            Log::error("L'enregistrement du produit a échoué", ['data' => $product]);
            return response()->json(['message' => 'Erreur lors de l\'enregistrement du produit'], 500);
        }

        Log::info("Produit enregistré avec succès :", ['id' => $product->id]);

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
        // Log::info("Données brutes reçues :", ['data' => $request->all()]);
        // Log::info("Nom du produit :", ['name' => $request->input('name')]);
        

        $product = Produits::find($id);
    
        if (!$product) {
            // Log::error("Produit non trouvé pour l'ID: $id");
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }
    
        // Validation des données mises à jour
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        // Log::info("Données validées :", $validated);
    
        // Mise à jour de l'image si une nouvelle est fournie
        if ($request->hasFile('image')) {
            // Suppression de l'ancienne image
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
    
            // Upload de la nouvelle image
            $validated['image_path'] = $request->file('image')->store('produits', 'public');
        }
    
        // Mise à jour du produit
        $updated = $product->update($validated);
    
        if (!$updated) {
            // Log::error("Échec de la mise à jour du produit ID: $id");
            return response()->json(['message' => 'Échec de la mise à jour'], 500);
        }
    
        Log::info("Produit mis à jour avec succès : ", $product->toArray());
    
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
        // Vérifier si la catégorie existe
        $category = Categories::find($categoryId);

        if (!$category) {
            return response()->json([
                'message' => 'Catégorie non trouvée'
            ], 404);
        }

        // Récupérer les produits de cette catégorie
        $produits = Produits::where('categories_id', $categoryId)->get();

        return response()->json([
            'category' => $category->name,
            'products' => $produits
        ], 200);
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

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categories;


class CategoriesController extends Controller
{
    /**
     * Récupérer toutes les catégories.
     */
    public function index()
    {
        // $categories = Categories::with('produits')->get(); // Inclut les produits liés à chaque catégorie

        // return response()->json([
        //     'message' => 'Liste des catégories récupérée avec succès',
        //     'categories' => $categories,
        // ], 200);
         // Récupérer toutes les catégories
         $categories = Categories::select('id', 'name')->get(); // Sélectionner explicitement 'id' et 'name'

         return response()->json([
             'message' => 'Liste des catégories récupérée avec succès',
             'categories' => $categories,
         ], 200);
    }

    /**
     * Créer une nouvelle catégorie.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories', // Le nom doit être unique
        ]);

        $categorie = Categories::create([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'categorie' => $categorie,
        ], 201);
    }

    /**
     * Afficher une catégorie spécifique.
     */
    public function show($id)
    {
        $categorie = Categories::with('produits')->find($id);

        if (!$categorie) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        return response()->json([
            'message' => 'Catégorie récupérée avec succès',
            'categorie' => $categorie,
        ], 200);
    }

    /**
     * Récupérer les produits associés à une catégorie spécifique.
     */
    // public function produitsParCategorie($categoryId)
    // {
    //     $category = Categories::with('produits')->find($categoryId);

    //     if (!$category) {
    //         return response()->json(['message' => 'Catégorie non trouvée'], 404);
    //     }

    //     return response()->json([
    //         'category' => $category->name,
    //         'products' => $category->produits,
    //     ], 200);
    // }

    /**
     * Mettre à jour une catégorie.
     */
    public function update(Request $request, $id)
    {
        $categorie = Categories::find($id);

        if (!$categorie) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $categorie->id,
        ]);

        $categorie->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'categorie' => $categorie,
        ], 200);
    }

    /**
     * Supprimer une catégorie.
     */
    public function destroy($id)
    {
        $categorie = Categories::find($id);

        if (!$categorie) {
            return response()->json(['message' => 'Catégorie non trouvée'], 404);
        }

        $categorie->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès'], 200);
    }
}

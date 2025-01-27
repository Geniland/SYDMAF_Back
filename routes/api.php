<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProduitsController;
use App\Http\Controllers\API\CategoriesController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'user']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::post('produits', [ProduitsController::class, 'store']);
Route::get('/produits', [ProduitsController::class, 'index']);
// Route::get('produits', [ProduitsController::class, 'index']);
Route::post('categories', [CategoriesController::class, 'store']);
Route::get('list-categories', [CategoriesController::class, 'index']);
Route::delete('supp-produit/{id}', [ProduitsController::class, 'destroy']);
Route::post('modification/{id}', [ProduitsController::class, 'update']);



// Route::get('categories/{id}/produits', [ProduitsController::class, 'produitsParCategorie'])->name('produits.parCategorie');

// Route::get('produits/{id}/meme-categorie', [ProduitsController::class, 'produitsMemeCategorie'])->name('produits.memeCategorie');

// recuperer les produits qui ont les meme categories
Route::get('/categories/{id}/produits', [CategoriesController::class, 'show']);

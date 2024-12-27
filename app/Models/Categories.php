<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Produits;

class Categories extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relation avec Produit (une catégorie a plusieurs produits)
    public function produits()
    {
        return $this->hasMany(Produits::class);
    }
}

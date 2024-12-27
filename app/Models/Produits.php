<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Ajout pour utiliser les factories
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Categories;

class Produits extends Model
{
    use HasApiTokens, Notifiable; // Ajout du trait HasApiTokens
    use HasFactory;

    protected $table = 'produits';

    /**
     * Les attributs qui peuvent être remplis via un formulaire ou une requête.
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'image_path',
        'categories_id',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     */
    protected $casts = [
        'price' => 'float',
    ];

    public function categories()
    {
        return $this->belongsTo(Categories::class, 'categories_id');
    }




}

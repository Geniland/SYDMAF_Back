<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Produits;

class Boutique extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'logo', 'description', 'user_id'];

    // Relation avec l'utilisateur
    public function utilisateur() {
        return $this->belongsTo(User::class);
    }

    // Relation avec les produits
    public function produits() {
        return $this->hasMany(Produits::class);
    }
}

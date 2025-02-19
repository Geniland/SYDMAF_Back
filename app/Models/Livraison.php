<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'phone', 'instructions', 'products'];

    protected $casts = [
        'products' => 'array', // Convertir le JSON en tableau PHP automatiquement
    ];
}

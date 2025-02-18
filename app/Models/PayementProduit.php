<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayementProduit extends Model
{
    use HasFactory;

    // Si le nom de la table ne correspond pas au pluriel du modèle, on le précise
    protected $table = 'payement_produits';

    // Les champs pouvant être assignés en masse
    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'status',
    ];
}

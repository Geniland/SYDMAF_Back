<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'crypto_amount',
        'crypto_currency',
        'fiat_amount',
        'payment_method',
        'phone_number',
        'status',
        'address',
        'user_id'
       
    ];
}

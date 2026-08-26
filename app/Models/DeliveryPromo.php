<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryPromo extends Model
{
    protected $fillable = [
        'title',
        'promo_type',
        'amount',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}

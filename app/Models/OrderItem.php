<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_id',
        'final_price',
        'quantity'
    ];

    protected $with = ['order', 'menu'];

    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function menu(){
        return $this->belongsTo(Menu::class, 'menu_id', 'id');
    }
}

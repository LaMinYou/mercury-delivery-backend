<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRiderOffer extends Model
{
    protected $fillable = ['order_id', 'rider_id', 'status'];
    protected $with = ['order', 'rider'];

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function rider(){
        return $this->belongsTo(User::class, 'rider_id', 'id');
    }
}

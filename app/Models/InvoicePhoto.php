<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePhoto extends Model
{
    protected $fillable = ['order_id', 'photo_path'];

    protected $with = ['order'];

    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}

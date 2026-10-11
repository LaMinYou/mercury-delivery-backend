<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePhoto extends Model
{
    protected $fillable = ['order_id', 'photo_path'];

    protected $with = ['order'];

    protected $appends = ['image_url'];

    public function order(){
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getImageUrlAttribute()
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        return asset('images/placeholder.jpg');
    }
}

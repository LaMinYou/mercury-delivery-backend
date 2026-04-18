<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'category_id', 
        'restaurant_id',
        'title',
        'subtitle',
        'image',
        'description',
        'available_count',
        'price',
        'discount_price',
        'is_available'
    ];

    protected $with = ['restaurant', 'category'];

    public function restaurant(){
        return $this->belongsTo(User::class, 'restaurant_id', 'id');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }
}

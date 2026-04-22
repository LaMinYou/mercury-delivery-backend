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

    // Data ထုတ်ပေးတိုင်း image_url ဆိုတဲ့ field အသစ် ပါသွားအောင် လုပ်ခြင်း
    protected $appends = ['image_url'];

    public function restaurant(){
        return $this->belongsTo(User::class, 'restaurant_id', 'id');
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    // Image URL အပြည့်အစုံ ထုတ်ပေးသော Accessor
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/placeholder.jpg'); // ပုံမရှိရင်ပြမယ့် default ပုံ
    }
}

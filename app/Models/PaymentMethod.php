<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [ 'name', 'is_active', 'image', 'logo_image' ];

    protected $appends = ['image_url', 'logo_url'];

    public function orders(){
        return $this->hasMany(Order::class, 'payment_id');
    }

    // Image URL အပြည့်အစုံ ထုတ်ပေးသော Accessor
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/placeholder.jpg'); // ပုံမရှိရင်ပြမယ့် default ပုံ
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo_image) {
            return asset('storage/' . $this->logo_image);
        }
        return asset('images/logo-placeholder.jpg'); // logo ပုံမရှိရင်ပြမယ့် default ပုံ
    }
}

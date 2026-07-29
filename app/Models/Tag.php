<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [ 'name', 'image' ];


    protected $appends = ['image_url'];

    public function menus(){
        return $this->belongsToMany(Menu::class, 'menu_tag');
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/placeholder.jpg'); // ပုံမရှိရင်ပြမယ့် default ပုံ
    }
}

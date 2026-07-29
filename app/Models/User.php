<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cover_image',
        'username',
        'email',
        'password',
        'phone',
        'address',
        'location',
        'status',
        'role_id',
        'latitude',
        'longitude', 
        'shop_message'
    ];

    protected $with = ['role'];

    protected $appends = ['image_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(){
        return $this->belongsTo(Role::class);
    }

    public function menus(){
        return $this->hasMany(Menu::class, 'restaurant_id');
    }

    public function restaurantOrders()
    {
        return $this->hasMany(Order::class, 'restaurant_id', 'id');
    }

    public function customers()
    {
        return $this->hasManyThrough(
            User::class,          // ၁။ ရယူချင်တဲ့ Model
            Order::class,         // ၂။ ကြားခံ Model (Orders table)
            'restaurant_id',      // ၃။ Order table ထဲက Restaurant ကိုညွှန်းတဲ့ Foreign key
            'id',                 // ၄။ Users table (Customer) ရဲ့ Primary key
            'id',                 // ၅။ Users table (Restaurant) ရဲ့ Primary key
            'customer_id'             // ၆။ Order table ထဲက Customer ကိုညွှန်းတဲ့ Foreign key
        )->distinct();           
    }

    public function customerOrders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function riderOrders()
    {
        return $this->hasMany(Order::class, 'rider_id', 'id');
    }

    public function userPaymentTransactions(){
        return $this->hasMany(PaymentTransaction::class);
    }

    // Image URL အပြည့်အစုံ ထုတ်ပေးသော Accessor
    public function getImageUrlAttribute()
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return asset('images/placeholder.jpg'); // ပုံမရှိရင်ပြမယ့် default ပုံ
    }
}

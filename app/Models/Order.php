<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'restaurant_id',
        'customer_id',
        'rider_id',
        'payment_id',
        'order_type',
        'dest_latitude',
        'dest_longitude',
        'dest_address',
        'dest_phone',
        'total_price',
        'delivery_fee',
        'service_fee',
        'payment_status',
        'delivery_status',
        'order_status',
        'user_note',
        'source_latitude',
        'source_longitude',
        'source_address',
        'source_phone',
        'item_type'
    ];

    protected $with = [
        'restaurant',
        'customer',
        'rider',
        'payment'
    ];

    public function restaurant(){
        return $this->belongsTo(User::class, 'restaurant_id', 'id');
    }

    public function customer(){
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function rider(){
        return $this->belongsTo(User::class, 'rider_id', 'id');
    }

    public function payment(){
        return $this->belongsTo(PaymentMethod::class, 'payment_id', 'id');
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function orderPaymentTransactions(){
        return $this->hasMany(PaymentTransaction::class);
    }
}

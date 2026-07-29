<?php

use Illuminate\Support\Facades\Broadcast;

//Broadcast::routes(['middleware' => [\Illuminate\Http\Middleware\HandleCors::class]]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('restaurants.{restaurantId}', function ($user, $restaurantId) {
    // လက်ရှိ Login ဝင်ထားတဲ့ User ရဲ့ ID နဲ့ ဆိုင် ID တူမှ ပေးဝင်မယ်
    //return (int) $user->id === (int) $restaurantId;
    return true;
});

Broadcast::channel('riders.{riderId}', function ($user, $riderId) {
    // လက်ရှိ Login ဝင်ထားသူသည် ၎င်းချန်နယ်ပိုင်ရှင် ရိုက်ဒါ ဖြစ်မှသာ ဝင်ခွင့်ပြုမည်
    return (int) $user->id === (int) $riderId;
});

Broadcast::channel('customers.{customerId}', function($user, $customerId) {
    return (int) $user->id === (int) $customerId;
});

Broadcast::channel('admin.{adminId}', function ($user, $adminId) {
    return (int) $user->id == (int) $adminId;
});
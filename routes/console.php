<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;
use App\Http\Controllers\OrderController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $expiredOrders = Order::whereNull('rider_id')
        ->where('Order_status', 'accepted')
        ->where('updated_at', '<=', now()->subMinutes(1))
        ->get();

    $controller = new OrderController();

    foreach ($expiredOrders as $order) {
        $sourceLat = $order->order_type == 'errand' ? $order->source_latitude : $order->restaurant->latitude;
        $sourceLng = $order->order_type == 'errand' ? $order->source_longitude : $order->restaurant->longitude;

        // Background မှ Reassign ပြန်လုပ်ပေးမည်
        $controller->assignToRiders($sourceLat, $sourceLng, $order);
    }
})->everyMinute();
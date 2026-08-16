<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Events\OrderHandOver;
use App\Events\RiderOrderAssigned;
use App\Models\Order;
use App\Models\OrderRiderOffer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|unique:orders,order_number',
            'restaurant_id' => 'required|exists:users,id',
            'customer_id' => 'required|exists:users,id',
            'order_type' => 'required',
            'dest_latitude' => 'required',
            'dest_longitude' => 'required',
            'dest_address' => 'required',
            'dest_phone' => 'required',
            'total_price' => 'required',
            'delivery_fee' => 'required',
            'service_fee' => 'required',
            'payment_id' => 'required|exists:payment_methods,id',
            'payment_status' => 'required',
            'delivery_status' => 'required',
            'order_status' => 'required',
            'user_note' => 'nullable|string|max:500',
        ]);

        try {
            $order = Order::create($validated);
            // if($order->payment->name === 'cash'){
            //     broadcast(new OrderCreated($order));
            // }

            return response()->json(['orderId' => $order->id], 201);
        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'မှာယူမှု မအောင်မြင်ပါ။ ခေတ္တစောင့်ပြီးမှ ပြန်လည်ကြိုးစားပါ။',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeExpressOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string|unique:orders,order_number',
            'restaurant_id' => 'nullable',
            'customer_id' => 'required|exists:users,id',
            'order_type' => 'required',
            'source_latitude' => 'required',
            'source_longitude' => 'required',
            'source_address' => 'required',
            'source_phone' => 'required',
            'dest_latitude' => 'required',
            'dest_longitude' => 'required',
            'dest_address' => 'required',
            'dest_phone' => 'required',
            'delivery_fee' => 'required',
            'service_fee' => 'required',
            'payment_id' => 'required|exists:payment_methods,id',
            'payment_status' => 'required',
            'delivery_status' => 'required',
            'order_status' => 'required',
            'item_type' => 'required|string',
            'user_note' => 'nullable|string|max:500',
        ]);

        try {
            $order = Order::create($request->all());
            //$order->update(['order_status' => 'accepted']);

            if ($order->payment->name === 'cash') {
                $order->update(['order_status' => 'accepted']);
                $sourceLat = $request->source_latitude;
                $sourceLng = $request->source_longitude;

                $hasRiders = $this->assignToRiders($sourceLat, $sourceLng, $order);

                if ($hasRiders) {
                    return response()->json([
                        'orderId' => $order->id,
                        'message' => 'Express Order တင်ခြင်း အောင်မြင်ပါသည်။ ရိုက်ဒါ ရှာဖွေနေပါသည်...'
                    ], 201);
                } else {
                    return response()->json([
                        'orderId' => $order->id,
                        'message' => 'Express Order တင်ခြင်း အောင်မြင်သော်လည်း လက်ရှိတွင် အားသော ရိုက်ဒါ မရှိသေးပါ။'
                    ], 201);
                }
            }

            return response()->json(['orderId' => $order->id], 201);
        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'မှာယူမှု မအောင်မြင်ပါ။ ခေတ္တစောင့်ပြီးမှ ပြန်လည်ကြိုးစားပါ။',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeMerchantOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string|unique:orders,order_number',
            'restaurant_id' => 'required|integer',
            'customer_id' => 'nullable',
            'order_type' => 'required',
            'dest_latitude' => 'required',
            'dest_longitude' => 'required',
            'dest_address' => 'required',
            'dest_phone' => 'required',
            'delivery_fee' => 'required',
            'service_fee' => 'required',
            'payment_id' => 'required',
            'payment_status' => 'required',
            'delivery_status' => 'required',
            'order_status' => 'required',
        ]);
        try {
            $order = Order::create($request->all());

            $restaurantLat = $order->restaurant->latitude;
            $restaurantLng = $order->restaurant->longitude;

            $hasRiders = $this->assignToRiders($restaurantLat, $restaurantLng, $order);

            if ($hasRiders) {
                return response()->json([
                    'orderId' => $order->id,
                    'message' => 'Rider ခေါ်ယူခြင်း အောင်မြင်ပါသည်။'
                ], 201);
            } else {
                return response()->json([
                    'orderId' => $order->id,
                    'message' => 'လက်ရှိတွင် အားသော ရိုက်ဒါ မရှိသေးပါ။'
                ], 201);
            }
        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Rider ခေါ်ယူခြင်း မအောင်မြင်ပါ။ ခေတ္တစောင့်ပြီးမှ ပြန်လည်ကြိုးစားပါ။',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignToRiders($sourceLat, $sourceLng, $order)
    {
        $nearbyRiders = collect();
        $radius = 5;
        $maxRadius = 15;

        // 💡 Infinite loop မဖြစ်အောင် တားဆီးရန် ကန့်သတ်ချက် ထည့်သွင်းခြင်း
        while ($nearbyRiders->isEmpty() && $radius <= $maxRadius) {

            $nearbyRiders = User::select('users.*')
                ->selectRaw(
                    '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                    [$sourceLat, $sourceLng, $sourceLat]
                )
                ->withCount(['riderOrders' => function ($query) {
                    $query->whereIn('delivery_status', ['picking', 'delivering']);
                }])
                ->where('role_id', 4)
                ->where('status', 'available')
                ->having('distance', '<', $radius)
                ->having('rider_orders_count', '<', 3)
                ->orderBy('distance', 'asc')
                ->take(3)
                ->get();

            // 💡 ဘာမှမတွေ့ရင် Radius ကို ၅ ကီလိုမီတာ တိုးမယ်
            if ($nearbyRiders->isEmpty()) {
                $radius += 5;
            }
        }

        // 💡 အဆင့် (၂) - ၁၅ ကီလိုမီတာအတွင်း ရှာမရပါက ကန့်သတ်ချက်လျှော့ချ၍ ကီလိုမီတာ ၂၀ အထိ ထပ်ရှာခြင်း
        if ($nearbyRiders->isEmpty()) {
            $nearbyRiders = User::select('users.*')
                ->selectRaw(
                    '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                    [$sourceLat, $sourceLng, $sourceLat]
                )
                ->where('role_id', 4)
                ->where('status', 'available')
                ->having('distance', '<', 20)
                ->orderBy('distance', 'asc')
                ->take(3)
                ->get();
        }

        // 💡 ရိုက်ဒါများ တွေ့ရှိပါက Offer ပို့ပြီး true ပြန်မည်၊ မတွေ့ပါက false ပြန်မည်
        if ($nearbyRiders->isNotEmpty()) {
            foreach ($nearbyRiders as $rider) {
                OrderRiderOffer::create([
                    'order_id' => $order->id,
                    'rider_id' => $rider->id,
                    'status' => 'pending'
                ]);

                broadcast(new RiderOrderAssigned($rider->id, $order));
            }
            return true;
        }

        return false;
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    public function getIncomingOrders($restaurantId)
    {
        $orders = Order::with('orderItems')->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'accepted')
            ->where('order_status', 'pending')
            ->latest('updated_at')
            ->get();

        return response()->json($orders);
    }

    public function updateOrderStatus(Order $order, Request $request)
    {
        try {
            if ($request->status === 'accepted') {
                $order->update(['order_status' => 'accepted']);

                $restaurantLat = $order->restaurant->latitude;
                $restaurantLng = $order->restaurant->longitude;

                $nearbyRiders = collect(); // အစပိုင်းတွင် အလွတ်တစ်ခု ဆောက်ထားမည်
                $radius = 5; // စတင်ရှာဖွေမည့် ကီလိုမီတာ (အကွာအဝေး)

                // အားသော ရိုက်ဒါ မတွေ့မချင်း သို့မဟုတ် ၁၅ ကီလိုမီတာ အထိ ဧရိယာချဲ့၍ ပတ်ရှာမည်
                while ($nearbyRiders->isEmpty() && $radius <= 15) {

                    $nearbyRiders = User::select('users.*')
                        ->selectRaw(
                            '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                            [$restaurantLat, $restaurantLng, $restaurantLat]
                        )
                        ->withCount(['riderOrders' => function ($query) {
                            $query->whereIn('delivery_status', ['picking', 'delivering']);
                        }])
                        ->where('role_id', 4)
                        ->where('status', 'available')
                        ->having('distance', '<', $radius) // ၅ ကီလိုမီတာမှ ၁၀၊ ၁၅ ကီလိုမီတာသို့ တဖြည်းဖြည်း ကျယ်ပြန့်သွားမည်
                        ->having('rider_orders_count', '<', 3) // သတိပြုရန် - withCount သုံးလျှင် variable နာမည်သည် {relation}_count အတိုင်း rider_orders_count ဖြစ်ရပါမည်
                        ->orderBy('distance', 'asc')
                        ->take(3)
                        ->get();

                    // အကယ်၍ မတွေ့ပါက နောက်တစ်ကြိမ်တွင် ၅ ကီလိုမီတာ ထပ်တိုး၍ ရှာရန် ပြင်ဆင်ခြင်း
                    if ($nearbyRiders->isEmpty()) {
                        $radius += 5;
                    }
                }

                //အကယ်၍ ၁၅ ကီလိုမီတာအထိ ရှာဖွေပြီးသည့်တိုင် ရိုက်ဒါ လုံးဝမရှိသေးပါက ကန့်သတ်ချက်ကို လျှော့ချ၍ (လက်ရှိအော်ဒါ ၃ ခုထက် ကျော်နေသူများကိုပါ) နောက်ဆုံးအနေဖြင့် အကုန်သိမ်းကျုံးရှာခြင်း
                if ($nearbyRiders->isEmpty()) {
                    $nearbyRiders = User::select('users.*')
                        ->selectRaw(
                            '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                            [$restaurantLat, $restaurantLng, $restaurantLat]
                        )
                        ->where('role_id', 4)
                        ->where('status', 'available')
                        ->having('distance', '<', 20) // အဝေးကြီးအထိ ရှာမည်
                        ->orderBy('distance', 'asc')
                        ->take(3)
                        ->get();
                }

                // ရိုက်ဒါများ တွေ့ရှိပါက ကမ်းလှမ်းချက် ပို့မည်
                if ($nearbyRiders->isNotEmpty()) {
                    foreach ($nearbyRiders as $rider) {
                        OrderRiderOffer::create([
                            'order_id' => $order->id,
                            'rider_id' => $rider->id,
                            'status' => 'pending'
                        ]);

                        broadcast(new RiderOrderAssigned($rider->id, $order));
                    }
                    return response()->json(['message' => 'Order accepted, looking for riders.'], 200);
                } else {
                    // လုံးဝကို ရှာမရတော့သည့် အခြေအနေ
                    return response()->json(['message' => 'Order accepted, but no riders are available right now.'], 200);
                }
            } elseif ($request->status === 'rejected') {
                $order->update([
                    'order_status' => 'rejected'
                ]);
                return response()->json(['message' => 'Order rejected.'], 200);
            } else {
                $order->update([
                    'order_status' => 'pending'
                ]);
                return response()->json(['message' => 'Order status is pending'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePaymentStatus(Order $order, Request $request)
    {
        try {
            if ($request->status === 'accepted') {
                $order->update([
                    'payment_status' => 'accepted'
                ]);
            } elseif ($request->status === 'rejected') {
                $order->update([
                    'payment_status' => 'rejected'
                ]);
            } else {
                $order->update([
                    'payment_status' => 'pending'
                ]);
            }

            if ($order->order_type == 'errand') {
                $order->update(['order_status' => 'accepted']);
                //assign to rider
                $sourceLat = $order->source_latitude;
                $sourceLng = $order->source_longitude;
                $hasRiders = $this->assignToRiders($sourceLat, $sourceLng, $order);

                if ($hasRiders) {
                    return response()->json([
                        'orderId' => $order->id,
                        'message' => 'Express Order တင်ခြင်း အောင်မြင်ပါသည်။ ရိုက်ဒါ ရှာဖွေနေပါသည်...'
                    ], 201);
                } else {
                    return response()->json([
                        'orderId' => $order->id,
                        'message' => 'Express Order တင်ခြင်း အောင်မြင်သော်လည်း လက်ရှိတွင် အားသော ရိုက်ဒါ မရှိသေးပါ။'
                    ], 201);
                }
                return response()->json(['message' => 'accepted for errand order']);
            } else {
                broadcast(new OrderCreated($order));
                //for background order noti
                if ($order->restaurant && $order->restaurant->fcm_token) {
                    try {
                        $messaging = app('firebase.messaging');

                        $message = CloudMessage::withTarget('token', $order->restaurant->fcm_token)
                            // ->withNotification(Notification::create(
                            //     'အော်ဒါအသစ် ရောက်ရှိ!',
                            //     "Order #{$order->order_number} အတွက် ပြင်ဆင်ပါ။"
                            // ))
                            ->withData([
                                'title' => 'အော်ဒါအသစ် ရောက်ရှိ!',
                                'body' => "Order #{$order->order_number} အတွက် ပြင်ဆင်ပါ။",
                                'target_url' => '/restaurant/',
                                'role' => 'restaurant'
                            ]);
                        $messaging->send($message);
                    } catch (\Exception $fcmError) {
                        Log::error('Firebase Send Error: ' . $fcmError->getMessage());
                    }
                }
            }

            return response()->json(['message' => 'updated payment status of order to accepted'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function orderHandOver(Request $request, Order $order)
    {
        $request->validate([
            'new_rider_id' => 'required|integer'
        ]);

        try {
            DB::beginTransaction();

            OrderRiderOffer::where('rider_id', $order->rider_id)
                ->where('order_id', $order->id)
                ->update(['status' => 'way-changed']);
            OrderRiderOffer::create([
                'order_id' => $order->id,
                'rider_id' => $request->new_rider_id,
                'status' => 'accepted'
            ]);

            $order->update([
                'rider_id' => $request->new_rider_id
            ]);

            DB::commit();
            broadcast(new OrderHandOver($request->new_rider_id, $order));
            return response()->json(['message' => "order is hand over to rider {$request->new_rider_id}"], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function releaseOrder(Order $order)
    {
        // 💡 လုံခြုံရေးစစ်ဆေးချက် - လက်ရှိ Login ဝင်ထားသူဟာ ဒီအော်ဒါကို ကိုင်ထားတဲ့ ရိုက်ဒါ ဟုတ်မဟုတ် စစ်မည်
        if ($order->rider_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        // 💡 လုံခြုံရေးစစ်ဆေးချက် ၂ - အော်ဒါက ဆိုင်မှာယူဆဲ (picking) အဆင့် ဖြစ်မှသာ Release ခွင့်ပြုမည်
        if ($order->delivery_status !== 'picking') {
            return response()->json(['error' => 'Cannot release order at this stage.'], 400);
        }

        try {
            DB::beginTransaction();

            OrderRiderOffer::where('order_id', $order->id)
                ->where('rider_id', $order->rider_id)
                ->where('status', 'accepted')
                ->update(['status' => 'released']);

            $order->update([
                'rider_id' => null,
                'delivery_status' => 'pending',
            ]);

            $restaurantLat = $order->order_type == 'errand' ? $order->source_latitude : $order->restaurant->latitude;
            $restaurantLng = $order->order_type == 'errand' ? $order->source_longitude : $order->restaurant->longitude;

            $nearbyRiders = collect();
            $radius = 5;

            // အနီးနားရှိ ရိုက်ဒါအသစ်များအား ပတ်ရှာခြင်း Logic (၁၅ ကီလိုမီတာအထိ)
            while ($nearbyRiders->isEmpty() && $radius <= 15) {
                $nearbyRiders = User::select('users.*')
                    ->selectRaw(
                        '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                        [$restaurantLat, $restaurantLng, $restaurantLat]
                    )
                    ->withCount(['riderOrders' => function ($query) {
                        $query->whereIn('delivery_status', ['picking', 'delivering']);
                    }])
                    ->where('role_id', 4)
                    ->where('status', 'available')
                    ->where('id', '!=', auth()->id())
                    ->having('distance', '<', $radius)
                    ->having('rider_orders_count', '<', 3)
                    ->orderBy('distance', 'asc')
                    ->take(3)
                    ->get();

                if ($nearbyRiders->isEmpty()) {
                    $radius += 5;
                }
            }

            // ၁၅ ကီလိုမီတာအထိ ရှာမရပါက ကန့်သတ်ချက်လျှော့ချ၍ အဝေးကြီးအထိ ထပ်ရှာခြင်း
            if ($nearbyRiders->isEmpty()) {
                $nearbyRiders = User::select('users.*')
                    ->selectRaw(
                        '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                        [$restaurantLat, $restaurantLng, $restaurantLat]
                    )
                    ->where('role_id', 4)
                    ->where('status', 'available')
                    ->where('id', '!=', auth()->id())
                    ->having('distance', '<', 20)
                    ->orderBy('distance', 'asc')
                    ->take(3)
                    ->get();
            }

            if ($nearbyRiders->isNotEmpty()) {
                foreach ($nearbyRiders as $rider) {
                    OrderRiderOffer::create([
                        'order_id' => $order->id,
                        'rider_id' => $rider->id,
                        'status' => 'pending'
                    ]);

                    broadcast(new RiderOrderAssigned($rider->id, $order));
                }

                DB::commit();
                return response()->json(['message' => 'Order released, looking for new riders.'], 200);
            } else {
                DB::commit();
                return response()->json(['message' => 'Order released, but no new riders are available right now.'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getWeeklyStats()
    {
        $evenDaysAgo = Carbon::now('Asia/Yangon')->subDays(6)->startOfDay();
        $today = Carbon::now()->endOfDay();

        $orders = Order::select(
            DB::raw('DATE(updated_at) as date'),
            DB::raw('count(*) as count')
        )
            ->whereBetween('updated_at', [$evenDaysAgo, $today])
            ->groupBy('date')
            ->orderBy('date', 'Asc')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now('Asia/Yangon')->subDays($i)->format('Y-m-d');
            $dayName = Carbon::now('Asia/Yangon')->subDays($i)->format('D');

            $chartData[$dayName] = $orders[$date] ?? 0;
        }

        return response()->json($chartData);
    }

    public function settleRiderOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            Order::whereIn('id', $request->order_ids)->update([
                'is_settled' => true,
            ]);

            return response()->json(['message' => 'updated settle to true for rider']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong']);
        }
    }

    public function settleRestaurantOrders(Request $request){
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            Order::whereIn('id', $request->order_ids)->update([
                'is_shop_settled' => true,
            ]);
            return response()->json(['message' => 'updated settle to true for restaurant']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong']);
        }
    }
}

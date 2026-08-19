<?php

namespace App\Http\Controllers;

use App\Events\OrderOfferWithdrawn;
use App\Events\RiderDeliverStatusUpdated;
use App\Events\RiderLocationUpdated;
use App\Models\Order;
use App\Models\OrderRiderOffer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query

        $query = User::where('role_id', 4)
            ->withCount(['riderOrders as weekly_orders_count' => function ($q) {
                $q->where('delivery_status', 'completed')->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            }]);

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('phone')) {
            $query->where('phone', $request->phone);
        }
        if ($request->filled('status')) {
            if ($request->status != 'all')
                $query->where('status', $request->status);
        }

        // 3. Handle Sorting (The fix for the arrows)
        if ($request->has('sortBy') && is_array($request->sortBy)) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('weekly_orders_count', 'desc');
        }

        // 4. Pagination
        $perPage = $request->input('itemsPerPage', 5);
        $restaurants = $query->paginate($perPage);

        return response()->json([
            'items' => $restaurants->items(),
            'total' => $restaurants->total(),
        ]);
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
        $request->validate([
            'name' => 'required|string|max:50',
            'username' => 'required|string',
            'password' => 'required|min:8',
            'phone' => [
                'required',
                'string',
                'regex:/^(09|\+959)(4|2|5|7|8|9|6)([0-9]{7,9})$/',
                'unique:users'
            ],

        ]);

        try {
            $rider = new User();
            $rider->role_id = 4;
            $rider->name = $request->name;
            $rider->username = $request->username;
            $rider->password = Hash::make($request->password);
            $rider->phone = $request->phone;
            $rider->latitude = $request->latitude;
            $rider->longitude = $request->longitude;
            $rider->status = 'inactive';
            $rider->save();

            return response()->json(['message' => 'new rider was added'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while creating the record.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'username' => 'required|string',
            'phone' => [
                'required',
                'string',
                'regex:/^(09|\+959)(4|2|5|7|8|9|6)([0-9]{7,9})$/',
                Rule::unique('users')->ignore($user->id)
            ],

        ]);

        try {
            $user->name = $request->name;
            $user->username = $request->username;
            $user->phone = $request->phone;
            $user->save();

            return response()->json(['message' => 'rider info was successfully updated'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong while creating the record.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'Deleted rider successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'current_latitude' => 'required|numeric',
            'current_longitude' => 'required|numeric',
        ]);

        $riderId = auth()->id();
        $lat = $request->current_latitude;
        $lng = $request->current_longitude;

        // ၁။ ရိုက်ဒါ လက်ရှိ ယူထားဆဲ active orders အရေအတွက်ကို အရင်ရေတွက်မည်
        $activeOrdersCount = Order::where('rider_id', $riderId)
            ->whereIn('delivery_status', ['picking', 'delivering'])
            ->count();

        if ($activeOrdersCount > 0) {
            Cache::put("rider.{$riderId}.location", ['latitude' => $lat, 'longitude' => $lng], now()->addMinutes(5));
            broadcast(new RiderLocationUpdated($riderId, $lat, $lng));
        }

        if ($activeOrdersCount < 3) {

            $lockKey = "rider." . $riderId . ".db_store_lock";

            if (!Cache::has($lockKey)) {
                // Lock မရှိသေးရင် DB ထဲ တစ်ကြိမ်သွားရေးပြီး၊ ၃ မိနစ်စာ Lock မှတ်ထားလိုက်ပါသည်
                auth()->user()->update([
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);

                Cache::put($lockKey, true, now()->addMinutes(3));

                return response()->json([
                    'message' => 'Location updated in DB and locked for 3 minutes',
                    'status' => 'db_updated'
                ]);
            }
        }

        // အော်ဒါ ၃ ခုပြည့်နေချိန် သို့မဟုတ် ၃ မိနစ် Lock မိနေချိန် သို့မဟုတ် အော်ဒါမရှိသေးချိန်တွင် ဤနေရာသို့ ရောက်ပါမည်
        return response()->json([
            'message' => 'Location processed safely',
            'status' => 'processed'
        ]);
    }

    public function acceptOrder($orderId)
    {
        $riderId = auth()->id();

        return DB::transaction(function () use ($orderId, $riderId) {
            $order = Order::lockForUpdate()->find($orderId);

            if ($order->rider_id !== null) {
                return response()->json(['message' => 'စိတ်မရှိပါနဲ့၊ ဤအော်ဒါအား အခြားသူ ယူသွားပါပြီ။'], 400);
            }

            $order->update([
                'rider_id' => $riderId,
                'delivery_status' => 'picking'
            ]);

            OrderRiderOffer::where('order_id', $orderId)
                ->where('rider_id', $riderId)
                ->update(['status' => 'accepted']);

            OrderRiderOffer::where('order_id', $orderId)
                ->where('rider_id', '!=', $riderId) // မိမိမဟုတ်သော အခြားသူများ
                ->where('status', 'pending')       // pending ဖြစ်နေဆဲသူများကိုသာ
                ->update(['status' => 'canceled']); // သို့မဟုတ် 'expired'

            $otherRidersIds = OrderRiderOffer::where('order_id', $orderId)
                ->where('status', 'canceled')
                ->pluck('rider_id');

            foreach ($otherRidersIds as $otherRiderId) {
                broadcast(new OrderOfferWithdrawn($otherRiderId, $orderId));
            }

            $order->load('orderItems');

            return response()->json(['message' => 'အော်ဒါအား လက်ခံရယူမှု အောင်မြင်ပါသည်၊ ဆိုင်သို့ သွားယူပေးပါဦး။', 'order' => $order], 200);
        });
    }

    public function currentActiveOrders()
    {
        $orders = Order::with('orderItems')->where('rider_id', auth()->id())
            ->whereIn('delivery_status', ['picking', 'delivering'])
            ->latest()->get();

        return response()->json(['orders' => $orders]);
    }

    public function toggleStatus(Request $request)
    {
        try {
            if ($request->status === 'available') {
                auth()->user()->update([
                    'status' => 'available'
                ]);
            } else {
                auth()->user()->update([
                    'status' => 'inactive',
                    'fcm_token' => null
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function updateDeliveryStatus(Request $request, Order $order)
    {
        $request->validate([
            'deliveryStatus' => 'required|string'
        ]);
        try {
            $order->update([
                'delivery_status' => $request->deliveryStatus
            ]);
            broadcast(new RiderDeliverStatusUpdated($order->customer_id, $order));

            return response()->json([
                'message' => 'အော်ဒါအခြေအနေ အောင်မြင်စွာ ပြောင်းလဲပြီးပါပြီ။',
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            Log::error('Delivery Status Update Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'လုပ်ဆောင်ချက် မအောင်မြင်ပါ။ ခေတ္တစောင့်ပြီးမှ ပြန်လည်ကြိုးစားပါ။',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function completedOrders(Request $request)
    {
        try {
            //$riderId = auth()->id();
            $riderId = $request->riderId;
            $filter = $request->query('filter', 'this_week'); // take default value as 'week' if filter does not contains data

            $query = Order::where('rider_id', $riderId)
                ->where('delivery_status', 'completed');

            if ($filter === 'this_week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($filter === 'last_week') {
                $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
            } elseif ($filter === 'this_month') {
                $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            }

            $orders = $query->latest()->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function nearestRiders(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        $riderLat = $request->latitude;
        $riderLng = $request->longitude;

        $nearbyRiders = User::select('users.*')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                [$riderLat, $riderLng, $riderLat]
            )
            ->where('role_id', 4)
            ->where('status', 'available')
            ->where('id', '!=', Auth::id())
            ->having('distance', '<', 5)
            ->orderBy('distance', 'asc')
            ->take(10)
            ->get();
        return response()->json($nearbyRiders);
    }
}

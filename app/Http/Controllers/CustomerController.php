<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::where('role_id', 3)->withCount('customerOrders');

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('contact')) {
            $field = filter_var($request->contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            $query->where($field, 'like', "%{$request->contact}%");
        }
        if ($request->filled('level') && $request->level != 'All') {
            if($request->level == 'Diamond'){
                    $query->having('customer_orders_count', '>', 149);
                }elseif($request->level == 'Gold'){
                    $query->havingBetween('customer_orders_count', [100, 149]);
                }else{
                    $query->havingBetween('customer_orders_count',[0, 99]);
                }
        }

        // 3. Handle Sorting (The fix for the arrows)
        if ($request->has('sortBy') && is_array($request->sortBy)) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->latest(); // Default sort if no arrow is clicked
        }

        // 4. Pagination
        $perPage = $request->input('itemsPerPage', 5);
        $customers = $query->paginate($perPage);

        $processedItems = collect($customers->items())->map(function ($user) {
            $count = $user->customer_orders_count;
            if($count > 149) $level = 'Diamond';
            elseif($count > 99) $level = 'Gold';
            else $level = 'Silver';

            $user->level = $level;

            return $user;
        });

        return response()->json([
            'items' => $processedItems,
            'total' => $customers->total(),
        ]);
    }

    public function shopCustomers(Request $request){
        $shopId = auth()->id();
        // $query = auth()->user()->customers()->withCount(['customerOrders as customer_orders_count' => function($q) use($shopId){
        //     $q->where('restaurant_id', $shopId);
        // }]);
        $query = auth()->user()->customers()->withCount('customerOrders');

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('contact')) {
            $field = filter_var($request->contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            $query->where($field, 'like', "%{$request->contact}%");
        }
        if ($request->filled('level') && $request->level != 'All') {
            if($request->level == 'Diamond'){
                    $query->having('customer_orders_count', '>', 149);
                }elseif($request->level == 'Gold'){
                    $query->havingBetween('customer_orders_count', [100, 149]);
                }else{
                    $query->havingBetween('customer_orders_count',[0, 99]);
                }
        }

        // 3. Handle Sorting (The fix for the arrows)
        if ($request->has('sortBy') && is_array($request->sortBy)) {
            foreach ($request->sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('users.id', 'desc');
        }

        // 4. Pagination
        $perPage = $request->input('itemsPerPage', 5);
        $customers = $query->paginate($perPage);

        $processedItems = collect($customers->items())->map(function ($user) {
            $count = $user->customer_orders_count;
            if($count > 149) $level = 'Diamond';
            elseif($count > 99) $level = 'Gold';
            else $level = 'Silver';

            $user->level = $level;

            return $user;
        });

        return response()->json([
            'items' => $processedItems,
            'total' => $customers->total(),
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
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function currentOrders()
    {
        $orders = Order::with('orderItems')->where('customer_id', auth()->user()->id)
            ->whereIn('delivery_status', ['pending', 'picking', 'delivering'])
            ->latest()->get();
        return response()->json($orders);
    }

    public function currentOrderDetails(Order $order)
    {
        return response()->json($order);
    }

    public function getUserLevel()
    {
        $user = auth()->user();
        $orderCount = $user->customerOrders
            ->where('order_status', 'accepted')
            ->where('delivery_status', 'completed')
            ->count();
        $level = 'Bronze';

        if ($orderCount >= 150) {
            $level = 'Diamond';
        } elseif ($orderCount >= 100) {
            $level = 'Gold';
        } else {
            $level = 'Bronze';
        }

        return response()->json([
            'user_level' => $level,
            'order_count' => $orderCount
        ]);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            if ($request->fieldName == 'name') {
                $user->update(['name' => $request->editField]);
            }
            if ($request->fieldName == 'email') {
                $user->update(['email' => $request->editField]);
            }
            if ($request->fieldName == 'phone') {
                $user->update(['phone' => $request->editField]);
            }
            return response()->json(['message' => 'Successfully updated your profile', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Sorry! Unable to update your profile']);
        }
    }
}

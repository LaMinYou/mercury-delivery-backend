<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query
        $query = User::where('role_id', 2)->withCount(['restaurantOrders as weekly_order_count' => function ($q) {
            $q->where('delivery_status', 'completed')->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }]);

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('address')) {
            $query->where('address', 'like', "%{$request->address}%");
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
            $query->orderBy('weekly_order_count', 'desc');
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
            'name' => 'required|string',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|min:8',
            'phone' => [
                'required',
                'string',
                'regex:/^(09|\+959)(4|2|5|7|8|9|6)([0-9]{7,9})$/', // pattern အပြည့်အစုံကို / / ထဲမှာ ထည့်ပါ
                'unique:users'
            ],
            'address' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {

            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email ?? NULL;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            $user->role_id = 2;
            $user->status = 'active';
            $user->save();
            return response()->json(['message' => 'Created record for new restaurant'], 201);
        } catch (\Exception $e) {

            Log::error('Error creating restaurant: ' . $e->getMessage());
            return response()->json([
                // 'error' => 'Something went wrong while creating the record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try {
        } catch (\Exception $e) {
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        // 1. Validate (Include the unique ignore fix from before)
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'phone' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        // 2. Update properties
        $user->fill($request->only(['name', 'username', 'email', 'phone', 'address', 'latitude', 'longitude']));

        // 3. Save and check for errors
        if ($user->save()) {
            return response()->json(['message' => 'Updated successfully']);
        }

        return response()->json(['message' => 'Error saving to database'], 500);
    }

    public function handleStatus(User $user)
    {
        if ($user->status == 'active') {
            $user->status = 'inactive';
        } else {
            $user->status = 'active';
        }
        if ($user->save()) {
            return response()->json(['message' => 'Updated status successfully']);
        }

        return response()->json(['message' => 'Error saving to database'], 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'Deleted restaurant successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }

    public function findbyCustomer(Request $request)
    {
        $searchTag = $request->input('tag');
        $search = $request->input('search');

        $restaurants = User::where('role_id', 2)->has('menus')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('menus', function ($menuQuery) use ($search) {
                        $menuQuery->where('title', 'like', "%{$search}%")
                                    ->orWhere('subtitle', 'like', "%{$search}%");
                    });
                });
            })
            ->when($searchTag, function ($query) use ($searchTag) {
                $query->whereHas('menus.tags', function ($q) use ($searchTag) {
                    $q->where('name', $searchTag);
                });
            })
            ->with(['menus' => function ($query) use ($searchTag) {
                if ($searchTag) {
                    $query->whereHas('tags', function ($q) use ($searchTag) {
                        $q->where('name', $searchTag);
                    })->latest()->limit(1);
                } else {
                    $query->latest();
                }
            }])
            ->latest()->get();

        if ($restaurants->isEmpty()) {
            $restaurants = User::where('role_id', 2)->has('menus')->with('menus')->latest()->get();
        }

        $foodShops = collect();
        $comesticShops = collect();
        $otherShops = collect();

        foreach ($restaurants as $restaurant) {
            $categories = $restaurant->menus->pluck('category')->map(function ($item) {
                return strtolower($item->name);
            })->toArray();

            $hasFood = count(array_intersect($categories, ['food', 'drinks', 'drink', 'snack', 'snacks']));
            $hasComestic = count(array_intersect($categories, ['comestic', 'comestics']));

            if ($hasFood) $foodShops->push($restaurant);
            if ($hasComestic) $comesticShops->push($restaurant);
            if (!$hasFood && !$hasComestic) $otherShops->push($restaurant);
        }

        return response()->json([
            'foodShops' => $foodShops->values(),
            'comesticShops' => $comesticShops->values(),
            'otherShops' => $otherShops->values()
        ]);
    }


    public function findById(User $user)
    {
        return response()->json($user->load('menus'));
    }

    public function completedOrders(Request $request)
    {
        try {
            //$riderId = auth()->id();
            $restaurantId = $request->restaurantId;
            $filter = $request->query('filter', 'this_week'); // take default value as 'week' if filter does not contains data

            $query = Order::where('restaurant_id', $restaurantId)
                ->where('delivery_status', 'completed')
                ->where('order_status', 'accepted')
                ->where('order_type', 'food');

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

    public function updateShopStatus(Request $request, User $user)
    {
        try {
            if ($request->filled('message')) {
                $user->shop_message = $request->message;
            }
            $user->status = $request->status;
            $user->save();

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

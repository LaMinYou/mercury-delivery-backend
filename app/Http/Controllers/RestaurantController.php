<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $query = User::where('role_id', 2);

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('address')) {
            $query->where('address', 'like', "%{$request->address}%");
        }
        if ($request->filled('status')) {
            if($request->status != 'all')
                $query->where('status', $request->status);
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
            'phone' => 'required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'address' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {

            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->email = $request->email ?? '';
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
                'error' => 'Something went wrong while creating the record.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try{

        }catch(\Exception $e){

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

    public function handleStatus(User $user){
        if($user->status == 'active'){
            $user->status = 'inactive';
        }else{
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
        try{
            $user->delete();
            return response()->json(['message' => 'Deleted restaurant successfully']);
        }catch(\Exception $e){
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }
}

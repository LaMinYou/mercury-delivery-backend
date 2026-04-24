<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RiderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query
        $query = User::where('role_id', 4);

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('phone')) {
            $query->where('phone', $request->address);
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
        try{
            $user->delete();
            return response()->json(['message' => 'Deleted rider successfully']);
        }catch(\Exception $e){
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }
}

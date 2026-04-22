<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
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
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB limit
            'description' => 'required',
            'available_count' => 'required|integer',
            'is_available' => 'required'
        ]);

        try {
            // store image in path storage/app/public/menus
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('menus', 'public');

                // store data into database
                $menu = Menu::create([
                    'category_id' => $request->category_id,
                    'restaurant_id' => $request->restaurant_id,
                    'title' => $request->title,
                    'subtitle' => $request->subtitle,
                    'price' => $request->price,
                    'available_count' => $request->available_count,
                    'is_available' => $request->is_available,
                    'image' => $path,
                    'description' => $request->description
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Menu created successfully',
                    'data' => $menu,
                    'url' => asset('storage/' . $path)
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        return response()->json($menu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {

        // Validation (Image ကို optional ထားပါ)
        $request->validate([
            'title' => 'required',
            'subtitle' => 'required|string|max:255',
            'category_id' => 'required',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
            'description' => 'required',
            'available_count' => 'required|integer',
            'is_available' => 'required'
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // remove old image
            if ($menu->image) {
                Storage::disk('public')->delete($menu->image);
            }
            // store new image
            $data['image'] = $request->file('image')->store('menus', 'public');
        }

        $menu->update($data);
        return response()->json(['status' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        try{
            $menu->delete();
            return response()->json(['message' => 'Deleted menu successfully']);
        }catch(\Exception $e){
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }

    public function findByRestaurantId(Request $request, $id)
    {
        // 1. Base Query
        $query = Menu::where('restaurant_id', $id);

        // 2. Apply Search Filters
        if ($request->filled('title')) {
            $query->where('title', 'like', "%{$request->title}%");
        }
        if ($request->filled('subtitle')) {
            $query->where('subtitle', 'like', "%{$request->subtitle}%");
        }
        if ($request->filled('category_id')) {
            if ($request->category_id != null)
                $query->where('category_id', $request->category_id);
        }
        if ($request->filled('discount')) {
            if ($request->discount != 'All')
                $query->whereNotNull('discount_price');
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
}

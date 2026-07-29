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
            'is_available' => 'required',
            'prepare_time' => 'required|integer'
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
                    'description' => $request->description,
                    'prepare_time' => $request->prepare_time
                ]);

                if ($request->has('tags')) {
                    $tagIds = json_decode($request->tags);
                    $menu->tags()->sync($tagIds);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Menu created successfully',
                    'data' => $menu->load('tags'),
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
        return response()->json($menu->load('tags'));
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
            'is_available' => 'required',
            'prepare_time' => 'required|integer'
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
        // Tags များကို Update လုပ်ခြင်း (sync သုံးရင် အဟောင်းဖြုတ် အသစ်ထည့် အလိုလိုလုပ်ပေးပါတယ်)
        if ($request->has('tags')) {
            $tagIds = json_decode($request->tags);
            $menu->tags()->sync($tagIds);
        }
        return response()->json(['status' => 'success']);
    }

    public function updateAvailableCount(Menu $menu, Request $request)
    {
        $request->validate([
            'order_count' => 'required|integer'
        ]);

        $orderCount = $request->order_count;

        if ($orderCount > 0) {
            if ($menu->available_count < $orderCount) {
                return response()->json([
                    'error' => "{$menu->subtitle} မှာယူသည့် ပမာဏသည် ဆိုင်ရှိ လက်ကျန်ထက် ကျော်လွန်နေပါသည်"
                ], 422);
            }

            $menu->decrement('available_count', $orderCount);
        } else {
            $menu->increment('available_count', abs($orderCount));
        }

        return response()->json([
            'message' => 'Menu available count has been reduced',
            'current_stock' => $menu->fresh()->available_count
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        try {
            $menu->delete();
            return response()->json(['message' => 'Deleted menu successfully']);
        } catch (\Exception $e) {
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

    public function findRestaurantMenusByCustomer(Request $request, $id)
    {
        try {
            $query = Menu::with('tags')->where('restaurant_id', $id);

            if ($request->filled('tag')) {
                $searchTag = $request->input('tag');

                $query->orderByRaw("EXISTS (
                    SELECT 1 FROM menu_tag 
                    JOIN tags ON tags.id = menu_tag.tag_id 
                    WHERE menu_tag.menu_id = menus.id 
                    AND tags.name = ?
                ) DESC", [$searchTag]);
            }

            if ($request->filled('searchMenu')) {
                $searchTerm = $request->input('searchMenu');

                $query->orderByRaw("
                    CASE 
                        WHEN subtitle LIKE ? THEN 1 
                        WHEN title LIKE ? THEN 2 
                        ELSE 3 
                    END ASC
                ", ['%' . $searchTerm . '%', '%' . $searchTerm . '%']);
            }


            $menus = $query->get();

            return response()->json($menus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

}

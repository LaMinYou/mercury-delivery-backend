<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        //
    }
}

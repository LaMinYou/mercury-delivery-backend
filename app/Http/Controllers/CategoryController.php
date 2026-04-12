<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query
        $query = Category::query();

        // 2. Apply Search Filters
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
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
        $categories = $query->paginate($perPage);

        return response()->json([
            'items' => $categories->items(),
            'total' => $categories->total(),
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
            'name' => 'required|string'
        ]);

        try{
            $category = new Category();
            $category->name = $request->name;
            $category->save();
             return response()->json(['message' => 'Created record for new categories'], 201);
        }catch(\Exception $e){
            Log::error('Error creating restaurant: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while creating the record.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        $category->fill($request->only(['name']));
        if ($category->save()) {
            return response()->json(['message' => 'Updated successfully']);
        }

        return response()->json(['message' => 'Error saving to database'], 500);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try{
            $category->delete();
            return response()->json(['message' => 'Deleted restaurant successfully']);
        }catch(\Exception $e){
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query
        $query = Tag::query();

        // 2. Apply Search Filters
        // if ($request->filled('name')) {
        //     $searchTerm = $request->name;
        //     $query->where(function ($q) use ($searchTerm) {
        //         // နည်းလမ်း (၁) Database ထဲက စာသားက ပိုရှည်နေရင် (e.g. DB: "အချိုပွဲများ", Search: "အချို")
        //         $q->where('name', 'like', "%{$searchTerm}%")

        //             // နည်းလမ်း (၂) Search term က ပိုရှည်နေရင် (e.g. DB: "အချိုပွဲများ", Search: "အချိုပွဲများ55")
        //             ->orWhereRaw("INSTR(?, name) > 0", [$searchTerm]);
        //     });
        // }
        if($request->filled('name')){
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
        $tags = $query->paginate($perPage);

        return response()->json([
            'items' => $tags->items(),
            'total' => $tags->total(),
        ]);
    }

    public function all(){
        $tags = Tag::latest()->get();
        return response()->json($tags);
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
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('tags', 'public');

                $tag = Tag::create([
                    'name' => $request->name,
                    'image' => $path
                ]);

                return response()->json([
                    'message' => 'Tag created successfully',
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
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = $request->except('image');

        try {
            if ($request->hasFile('image')) {
                if ($tag->image) {
                    Storage::disk('public')->delete($tag->image);
                }
                // store new image
                $data['image'] = $request->file('image')->store('tags', 'public');
            }
            $tag->update($data);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        try {
            if ($tag->menus()->exists()) {
                return response()->json(['message' => 'This tag is in use by some menus.'], 422);
            }
            $tag->delete();
            return response()->json(['message' => 'Deleted tag successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }
}

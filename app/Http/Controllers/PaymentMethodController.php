<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Base Query
        $query = PaymentMethod::query();

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
        $payments = $query->paginate($perPage);

        return response()->json([
            'items' => $payments->items(),
            'total' => $payments->total(),
        ]);
    }

    public function all(){
        $payments = PaymentMethod::where('is_active', 1)->latest()->get();
        return response()->json($payments);
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
            'is_active' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'logo_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        try {
            // ပထမဆုံး image အတွက် default value ကို null ထားပါမယ်
            $path = null;
            $logoPath = null;

            // တကယ်လို့ image တင်ခဲ့ရင် image ကို သိမ်းပြီး path ရှာမယ်
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $path = $file->store('payments', 'public');
            }

            if($request->hasFile('logo_image')){
                $logo = $request->file('logo_image');
                $logoPath = $logo->store('payment-logos', 'public');
            }

            // image ပါပါ မပါပါ ဒေတာကို database ထဲ သေချာပေါက် သွင်းပါမယ်
            $payment = PaymentMethod::create([
                'name' => $request->name,
                'is_active' => $request->is_active,
                'image' => $path, // image မပါရင် null ဖြစ်နေမှာပါ
                'logo_image' => $logoPath
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'new payment created successfully',
                'data' => $payment,
                'url' => $path ? asset('storage/' . $path) : null // image ရှိမှ url ပြန်ပေးမယ်
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'name' => 'required|string',
            'is_active' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);
        $data = $request->except('image');
        try {
            // store image in path storage/app/public/menus
            if ($request->hasFile('image')) {
                if ($paymentMethod->image) {
                    Storage::disk('public')->delete($paymentMethod->image);
                }

                $data['image'] = $request->file('image')->store('payments', 'public');
            }

            $paymentMethod->update($data);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        try {
            $paymentMethod->delete();
            return response()->json(['message' => 'Deleted menu successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong while deleteing the record.'], 500);
        }
    }
}

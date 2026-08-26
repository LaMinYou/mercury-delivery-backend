<?php

namespace App\Http\Controllers;

use App\Models\DeliveryPromo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryPromoController extends Controller
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
            'promo_type' => 'required|in:free,flat',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        // delete old record
        DeliveryPromo::truncate();

        //convert to UTC format from local datetime
        $startDateUtc = Carbon::parse($request->start_date, 'Asia/Yangon')->setTimezone('UTC');
        $endDateUtc = Carbon::parse($request->end_date, 'Asia/Yangon')->setTimezone('UTC');
        $promo = DeliveryPromo::create([
            'title' => $request->title,
            'promo_type' => $request->promo_type,
            'amount' => $request->promo_type === 'free' ? 0 : $request->amount,
            'start_date' => $startDateUtc,
            'end_date' => $endDateUtc,
        ]);

        return response()->json([
            'message' => 'Delivery promotion saved successfully!',
            'data' => $promo
        ], 201);
    }

    public function getActivePromo()
    {
        $now = Carbon::now('UTC');
        $promo = DeliveryPromo::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->latest()
            ->first();

        return response()->json($promo);
        // $promo = DeliveryPromo::latest()->first();

        // return response()->json($promo);
    }

    /**
     * Display the specified resource.
     */
    public function show(DeliveryPromo $deliveryPromo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeliveryPromo $deliveryPromo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeliveryPromo $deliveryPromo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeliveryPromo $deliveryPromo)
    {
       try{
         $deliveryPromo->delete();
        return response()->json(['message' => 'promo cleared']);
       }catch(\Exception $e){
        return response()->json(['message' => 'something went wrong!']);
       }
    }
}

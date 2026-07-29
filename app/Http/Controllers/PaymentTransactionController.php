<?php

namespace App\Http\Controllers;

use App\Events\PaymentTransactionCreated;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class PaymentTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $transactions = PaymentTransaction::latest()->get();
            return response()->json($transactions, 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
            'user_id' => 'required|exists:users,id',
            'order_id' => 'required|exists:orders,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        try{
            $path = null;
            if($request->hasFile('image')){
                $file = $request->file('image');
                $path = $file->store('transactions', 'public');
            }
            $transaction = PaymentTransaction::create([
                'user_id' => $request->user_id,
                'order_id' => $request->order_id,
                'image' => $path
            ]);
            broadcast(new PaymentTransactionCreated($transaction));

            return response()->json(['message' => 'transaction is created'], 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentTransaction $paymentTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentTransaction $paymentTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentTransaction $paymentTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTransaction $paymentTransaction)
    {
        try{
            $paymentTransaction->delete();
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}

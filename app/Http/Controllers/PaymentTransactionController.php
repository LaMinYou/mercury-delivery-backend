<?php

namespace App\Http\Controllers;

use App\Events\PaymentTransactionCreated;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

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
            //for payment transaction background noti
            $admin = User::where('role_id', 1)->whereNotNull('fcm_token')->first();
            if ($admin->fcm_token) {
                    try {
                        $messaging = app('firebase.messaging');

                        $message = CloudMessage::withTarget('token', $admin->fcm_token)
                            // ->withNotification(Notification::create(
                            //     'Transaction အသစ် ရောက်ရှိ!',
                            //     "ငွေပေးချေမှုကိုစစ်ဆေးပါ။"
                            // ))
                            ->withData([
                                'title' => 'Transaction အသစ် ရောက်ရှိ!',
                                'body' => "ငွေပေးချေမှုကိုစစ်ဆေးပါ။",
                                'target_url' => '/admin/transactions/',
                                'role' => 'admin'
                            ]);
                        $messaging->send($message);
                    } catch (\Exception $fcmError) {
                        Log::error('Firebase Send Error: ' . $fcmError->getMessage());
                    }
                }

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

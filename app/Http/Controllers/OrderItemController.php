<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
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
            'order_id' => 'required|exists:orders,id',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menus,id',
            'items.*.finalPrice' => 'required|numeric',
            'items.*.quantity' => 'required|numeric'
        ]);

        try {
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $request->order_id,
                    'menu_id' => $item['id'],
                    'final_price' => $item['finalPrice'],
                    'quantity' => $item['quantity']
                ]);
            }
            $order = Order::find($request->order_id);
            if($order->payment->name === 'cash'){
                broadcast(new OrderCreated($order));
            }

            return response()->json(['message' => "Order items are created for order {$request->order_id}"], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderItem $orderItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderItem $orderItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    // Get all orders (admin can see all orders)
    public function index(Request $request)
    {
        $orders = Order::with('user')->get();
        return response()->json($orders);
    }

    // Get details of a specific order
    public function show($order_id)
    {
        $order = Order::with('orderItems.product')->findOrFail($order_id);
        return response()->json($order);
    }
}

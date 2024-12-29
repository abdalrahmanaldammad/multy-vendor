<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreOrderController extends Controller
{
    // Get all orders related to the store (store owner can see only their store's orders)
    public function index(Request $request)
    {
        $store = Auth::user()->stores->first(); // Get the first store
        if (!$store) {
            return response()->json(['message' => 'User has no store.'], 404);
        }
        $storeId = $store->id;
        // Fetch orders with items that belong to the store
        $orders = Order::whereHas('orderItems', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        })->with(['orderItems' => function ($query) use ($storeId) {
            $query->where('store_id', $storeId)->with('product'); // Eager load products for these items
        }])->get();

        return response()->json($orders);
    }


    // Get details of a specific order
    public function show($order_id)
    {
        $store = Auth::user()->stores->first(); // Get the first store
        if ($store) {
            $storeId = $store->id;
        } else {
            return response()->json(['message' => 'User has no store.'], 404);
        }
        $order = Order::where('id', $order_id)
            ->whereHas('orderItems', function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->with(['orderItems' => function ($query) use ($storeId) {
                $query->where('store_id', $storeId)->with('product'); // Filter orderItems and include product details
            }])
            ->firstOrFail();

        return response()->json($order);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'order_status' => 'required|in:pending,processing,completed,canceled',
        ]);

        // Find the order item by ID, ensuring the store owner owns the store
        $orderItem = OrderItem::where('id', $id)
            ->whereIn('store_id', Auth::user()->stores->pluck('id')) // Ensure the store belongs to the user
            ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Order item not found or not accessible'], 404);
        }

        // Check if the new status is 'canceled'
        if ($request->order_status === 'canceled') {
            $store = Store::find($orderItem->store_id);
            if ($store) {
                $product = $store->products()->where('id', $orderItem->product_id)->first();
                if ($product) {
                    // Increment the available quantity in the store
                    $product->available_quantity += $orderItem->quantity;
                    $product->save();
                }
            }
        }

        // Update the status
        $orderItem->order_status = $request->order_status;
        $orderItem->save();

        return response()->json(['message' => 'Order status updated successfully', 'order_item' => $orderItem]);
    }
}

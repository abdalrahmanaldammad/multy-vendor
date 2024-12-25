<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        // Create a new order
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => 0, // We'll update the price after calculating it
        ]);

        $totalPrice = 0;

        foreach ($request->products as $item) {
            $product = Product::find($item['product_id']);

            // Check if there is enough stock in the store
            if ($product->available_quantity < $item['quantity']) {
                return response()->json(['message' => 'Not enough stock for product ' . $product->product_name], 400);
            }

            // Calculate the total price for the order
            $totalPrice += $product->price * $item['quantity'];

            // Create the order item
            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'store_id' => $product->store_id,
            ]);

            // Update the available quantity in the store
            $product->update([
                'available_quantity' => $product->available_quantity - $item['quantity'],
            ]);
        }

        // Update the total price of the order
        $order->update(['total_price' => $totalPrice]);

        return response()->json(['message' => 'Order placed successfully.', 'order_id' => $order->id]);
    }

    public function index()
    {
        $orders = Auth::user()->orders;
        return response()->json($orders);
    }

    // Get details of a specific order
    public function show($order_id)
    {
        $order = Auth::user()->orders()
            ->with(['user', 'orderItems.product']) // Eager load related models
            ->findOrFail($order_id);

        return response()->json($order);
    }


    public function cancel(Request $request, $id)
    {
        // Find the order item by ID
        $orderItem = OrderItem::where('id', $id)
            ->whereHas('order', function ($query) {
                $query->where('user_id', auth()->id()); // Ensure the order belongs to the authenticated user
            })->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Order item not found or not accessible'], 404);
        }

        // Check if the order item is already processed or completed
        if (in_array($orderItem->order_status, ['processing', 'completed'])) {
            return response()->json(['message' => 'Order item cannot be canceled'], 400);
        }

        // Begin a transaction to ensure data integrity
        DB::beginTransaction();

        try {
            // Update the status to 'canceled'
            // $orderItem->update(['order_status' => 'canceled']);
            $orderItem->order_status = "canceled";
            $orderItem->save();

            // Update the available quantity in the store
            $store = Store::find($orderItem->store_id);
            if ($store) {
                $product = $store->products()->where('id', $orderItem->product_id)->first();
                if ($product) {
                    $product->available_quantity += $orderItem->quantity;
                    $product->save();
                }
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Order item canceled successfully', 'order_item' => $orderItem]);
        } catch (\Exception $e) {
            // Rollback the transaction if any error occurs
            DB::rollBack();

            return response()->json(['message' => 'Failed to cancel order item', 'error' => $e->getMessage()], 500);
        }
    }




    public function updateOrderItems(Request $request, $order_id)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $order = Auth::user()->orders()->with('orderItems')->findOrFail($order_id);

        $incomingProducts = collect($request->products); // Convert the incoming products to a collection
        $existingItems = $order->orderItems->keyBy('product_id'); // Existing items keyed by product_id

        // Update or add products
        foreach ($incomingProducts as $product) {
            $productId = $product['product_id'];
            $newQuantity = $product['quantity'];
            $productModel = Product::findOrFail($productId);

            if ($existingItems->has($productId)) {
                // Update quantity if changed
                $orderItem = $existingItems[$productId];
                $oldQuantity = $orderItem->quantity;

                if ($oldQuantity !== $newQuantity) {
                    $quantityDifference = $newQuantity - $oldQuantity;

                    // Check if there is enough stock when increasing quantity
                    if ($quantityDifference > 0 && $productModel->available_quantity < $quantityDifference) {
                        return response()->json([
                            'message' => 'Not enough stock for product: ' . $productModel->product_name,
                        ], 400);
                    }

                    // Update the available quantity in the store
                    $productModel->update([
                        'available_quantity' => $productModel->available_quantity - $quantityDifference,
                    ]);

                    // Update the order item quantity
                    $orderItem->update(['quantity' => $newQuantity]);
                }

                $existingItems->forget($productId); // Remove from the collection to track remaining items
            } else {
                // Add new product to the order
                if ($productModel->available_quantity < $newQuantity) {
                    return response()->json([
                        'message' => 'Not enough stock for product: ' . $productModel->product_name,
                    ], 400);
                }

                $order->orderItems()->create([
                    'product_id' => $productId,
                    'quantity' => $newQuantity,
                    'price' => $productModel->price,
                    'store_id' => $productModel->store_id,
                ]);

                // Decrease the available quantity in the store
                $productModel->update([
                    'available_quantity' => $productModel->available_quantity - $newQuantity,
                ]);
            }
        }

        // Delete products not in the incoming list and restore their quantities
        $existingItems->each(function ($item) {
            $productModel = Product::findOrFail($item->product_id);
            $productModel->update([
                'available_quantity' => $productModel->available_quantity + $item->quantity,
            ]);
            $item->delete();
        });

        // Recalculate total price
        $totalPrice = $order->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        $order->update(['total_price' => $totalPrice]);

        return response()->json(['message' => 'Order items updated successfully.']);
    }
}

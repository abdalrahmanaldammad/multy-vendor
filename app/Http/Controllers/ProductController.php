<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function createProduct(Request $request)
    {
        $validated =  $request->validate([
            'product_name' => 'required|string|max:255',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'available_quantity' => 'required|integer|min:0',
            'price' => 'required|integer|min:1',
        ]);

        // Get the authenticated user's store
        $store = Store::where('user_id', Auth::user()->id)->first();

        // Check if the store exists
        if (!$store) {
            return response()->json(['message' => 'No store found for the authenticated user.'], 404);
        }

        // Handle product image upload if provided
        $imagePath = null;
        if ($request->hasFile('product_image')) {
            $imagePath = $request->file('product_image')->store('product_images', 'public');
        }

        // Create the product
        $product = Product::create([
            'product_name' => $validated['product_name'],
            'product_image' => $imagePath,
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'store_id' => $store->id,
            'available_quantity' => $validated['available_quantity'],
            'price' => $validated['price'],
        ]);

        return response()->json(['message' => 'Product created successfully.', 'product' => $product], 201);
    }
    public function  updateProduct(Request $request, $product_id)
    {
        $validated = $request->validate([
            'product_name' => 'nullable|string|max:255',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'available_quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|integer|min:1',
        ]);

        // Find the product by ID
        $product = Product::find($product_id);

        // Check if the product exists
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // Check if the authenticated user owns the store
        $store = Store::where('user_id', Auth::user()->id)->first();
        if (!$store || $product->store_id !== $store->id) {
            return response()->json(['message' => 'You are not authorized to update this product.'], 403);
        }

        // Handle product image upload if provided
        if ($request->hasFile('product_image')) {
            // Delete the old image if it exists
            // if ($product->product_image) {
            //     Storage::delete('public/' . $product->product_image);
            // }

            // Store the new image
            $product->product_image = $request->file('product_image')->store('product_images', 'public');
        }

        // Update product fields
        $product->update(array_filter([
            'product_name' => $validated['product_name'] ?? $product->product_name,
            'description' => $validated['description'] ?? $product->description,
            'category_id' => $validated['category_id'] ?? $product->category_id,
            'available_quantity' => $validated['available_quantity'] ?? $product->available_quantity,
            'price' => $validated['price'] ?? $product->price,
        ]));

        return response()->json(['message' => 'Product updated successfully.', 'product' => $product], 200);
    }

    public function getMyProductsStore()
    {
        // Get the authenticated user's store
        $store = Store::where('user_id', Auth::user()->id)->first();

        // Check if the user owns a store
        if (!$store) {
            return response()->json(['message' => 'No store found for the authenticated user.'], 404);
        }

        // Get all products for the user's store
        $products = Product::where('store_id', $store->id)->get();

        return response()->json(['products' => $products], 200);
    }


    public function getAllProducts()
    {
        // Retrieve all products
        $products = Product::with('store', 'category')->get();

        return response()->json(['products' => $products], 200);
    }
    public function getProductDetails($product_id)
    {
        // Find the product by ID and load relationships
        $product = Product::with('store', 'category')->find($product_id);

        // Check if the product exists
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // Return the product details
        return response()->json(['product' => $product], 200);
    }

    public function searchProducts(Request $request)
    {
        // Retrieve query parameters
        $productName = $request->query('product_name');
        $storeName = $request->query('store_name');
        $categoryName = $request->query('category_name');
        $sortBy = $request->query('sort_by'); // 'price' or 'available_quantity'
        $sortOrder = $request->query('sort_order', 'asc'); // 'asc' or 'desc', default to 'asc'
        // Build the query
        $query = Product::query();

        // Filter by product_name
        if ($productName) {
            $query->where('product_name', 'LIKE', "%$productName%");
        }

        // Filter by store_name
        if ($storeName) {
            $query->whereHas('store', function ($q) use ($storeName) {
                $q->where('store_name', 'LIKE', "%$storeName%");
            });
        }

        // Filter by category_name
        if ($categoryName) {
            $query->whereHas('category', function ($q) use ($categoryName) {
                $q->where('name', 'LIKE', "%$categoryName%");
            });
        }

        // Apply sorting
        if (in_array($sortBy, ['price', 'available_quantity'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Execute the query
        $products = $query->with('store', 'category')->get();

        // Return the response
        return response()->json(['products' => $products], 200);
    }
}

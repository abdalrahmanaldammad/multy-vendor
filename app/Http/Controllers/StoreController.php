<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function createStore(Request $request)
    {

        $validatedData = $request->validate([
            'store_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if a store with the given name already exists
        $existingStore = Store::where('store_name', $validatedData['store_name'])->first();

        if ($existingStore) {
            // If the store exists, return a message
            return response()->json(['message' => 'The store already exists.', 'store' => $existingStore], 200);
        }
        $store = Store::create([
            'store_name' => $validatedData['store_name'],
            'description' => $validatedData['description'] ?? null,
            'user_id' => Auth::user()->id, // Get the authenticated user's ID
        ]);

        return response()->json(['message' => 'Store created successfully', 'store' => $store], 201);
    }
    public function updateStore(Request $request)
    {
        // Validate the request
        $validatedData = $request->validate([
            'new_store_name' => 'nullable|string|max:255|unique:stores,store_name', // Optional new name
            'description' => 'nullable|string', // Optional new description
        ]);

        // Find the store by its name
        $store = Store::where('user_id', Auth::user()->id)->first();

        // Check if the store exists
        if (!$store) {
            return response()->json(['message' => 'The store does not exist.'], 404);
        }

        // Update the store's details if provided
        if (isset($validatedData['new_store_name'])) {
            $store->store_name = $validatedData['new_store_name'];
        }
        if (isset($validatedData['description'])) {
            $store->description = $validatedData['description'];
        }

        // Save the updated store
        $store->save();

        return response()->json(['message' => 'Store updated successfully.', 'store' => $store], 200);
    }

    public function deletStore($store_id)
    {
        $store = Store::find($store_id);
        // Check if the store exists
        if (!$store) {
            return response()->json(['message' => 'The store does not exist.'], 404);
        }
        // Delete the store
        $store->delete();

        return response()->json(['message' => 'Store deleted successfully.'], 200);
    }

    public function filterStoreByName(Request $request)
    {
        // Get the store_name from the query parameters
        $storeName = $request->query('store_name');

        // Validate the input
        if (!$storeName) {
            return response()->json(['message' => 'The store_name query parameter is required.'], 400);
        }

        // Retrieve the store(s) that match the name
        $stores = Store::where('store_name', 'LIKE', "%{$storeName}%")->get();

        // Check if any stores were found
        if ($stores->isEmpty()) {
            return response()->json(['message' => 'No stores found matching the given name.'], 404);
        }

        return response()->json(['stores' => $stores], 200);
    }
    public function getAllStores()
    {
        $stores = Store::all();

        // Check if there are any stores
        if ($stores->isEmpty()) {
            return response()->json(['message' => 'No stores found.'], 404);
        }

        // Return the stores
        return response()->json(['stores' => $stores], 200);
    }
    public function getMyStore()
    {
        // Get the authenticated user's ID
        $userId = Auth::user()->id;

        // Retrieve the stores for the authenticated user
        $stores = Store::where('user_id', $userId)->get();

        // Check if the user has any stores
        if ($stores->isEmpty()) {
            return response()->json(['message' => 'No stores found for this user.'], 404);
        }

        // Return the stores
        return response()->json(['stores' => $stores], 200);
    }
}

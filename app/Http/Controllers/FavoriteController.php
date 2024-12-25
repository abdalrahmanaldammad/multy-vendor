<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    // Add a product to favorites
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $favorite = Favorite::create([
            'product_id' => $request->product_id,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Favorite added successfully', 'favorite' => $favorite], 201);
    }

    // List all favorites for the authenticated user
    public function index()
    {
        $favorites = Favorite::with('product')->where('user_id', auth()->id())->get();

        return response()->json($favorites);
    }

    // Remove a product from favorites
    public function destroy($id)
    {
        $favorite = Favorite::where('id', $id)->where('user_id', auth()->id())->first();

        if (!$favorite) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Favorite removed successfully']);
    }
}

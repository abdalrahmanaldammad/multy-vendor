<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(StoreUserRequest $request)
    {
        // Check if user exists by phone number
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            // If user exists, return response indicating the user already exists
            return response()->json([
                'message' => 'User with this phone number already exists.',
                'user' => $user,
            ], 400);  // Returning 400 Bad Request
        }
        // If user does not exist, create a new user
        $newUser = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),  // Hash the password
            'role' => $request->role
        ]);

        // Return the newly created user as response
        return response()->json([
            'message' => 'User created successfully.',
            'user' => $newUser,
        ], 201);  // Returning 201 Created
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        $credentials = $request->only('phone', 'password');
        $user = User::where("phone", $credentials['phone'])->first();
        if (!$user) {
            return response()->json([
                'message' => 'Please register first.',
            ], 404); // Return 404 if user is not found
        }
        $token = $user->createToken("auth_token")->plainTextToken;
        return response()->json(['message' => 'Welcome, User!', 'token' => $token]);
    }
}

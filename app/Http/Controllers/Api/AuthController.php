<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Type;

class AuthController extends Controller
{
public function login(Request $request)
{
    Log::info('Login Request', $request->all());
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
        'type'     => 'nullable|string|exists:types,name', // Validate type if present
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // If type is provided, ensure user has that type
    if ($request->filled('type')) {
        $hasType = $user->types()->where('name', $request->type)->exists();
        if (!$hasType) {
            return response()->json(['message' => 'User does not have required type'], 403);
        }
    }

    // Create Sanctum token
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'types' => $user->types->pluck('name'), // Return all types
        ]
    ]);
}


    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    // Get current user (for profile/SPA boot)
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}

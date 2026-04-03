<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Login Request', $request->only('email', 'type'));

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'type' => 'nullable|string|exists:types,name',
        ]);

        $user = User::with(['types', 'roles.permissions', 'branch:id,name,restaurant_id', 'restaurant:id,name,kind'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($request->filled('type')) {
            $hasType = $user->types()->where('name', $request->type)->exists();
            if (!$hasType) {
                return response()->json(['message' => 'User does not have required type'], 403);
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
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
        $user = $request->user()->loadMissing(['types', 'roles.permissions', 'branch:id,name,restaurant_id', 'restaurant:id,name,kind']);

        return response()->json($this->serializeUser($user));
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'roles' => $user->roles->pluck('name')->values(),
            'permissions' => $user->permissionNames()->values(),
            'branch_id' => $user->branch_id,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'restaurant_id' => $user->branch->restaurant_id,
            ] : null,
            'restaurant_id' => $user->restaurant_id,
            'restaurant' => $user->restaurant ? [
                'id' => $user->restaurant->id,
                'name' => $user->restaurant->name,
                'kind' => $user->restaurant->kind,
            ] : null,
            'types' => $user->types->pluck('name')->values(),
        ];
    }
}

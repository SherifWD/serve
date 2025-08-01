<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();
    if ($user->role === 'owner') {
        return User::with('branch')->get();
    }
    return User::where('branch_id', $user->branch_id)->get();
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'role' => 'required|in:supervisor,staff',
        'branch_id' => 'required|exists:branches,id',
    ]);
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'role' => $request->role,
        'branch_id' => $request->branch_id,
        'password' => bcrypt(str_random(10)), // send temp password via email
    ]);
    // send email invitation logic here
    return $user;
}

public function update(Request $request, User $user)
{
    $user->update($request->only('name', 'role', 'branch_id'));
    return $user;
}

public function destroy(User $user)
{
    $user->delete();
    return response()->json(['message' => 'Deleted']);
}

}

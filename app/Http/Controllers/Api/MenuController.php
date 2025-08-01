<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Category;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with('categories','branch')->get();
        return response()->json(['data' => $menus]);
    }

    public function show($id)
    {
        $menu = Menu::with('categories','branch')->findOrFail($id);
        return response()->json(['data' => $menu]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $menu = Menu::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if (!empty($data['categories'])) {
            $menu->categories()->sync($data['categories']);
        }
        return response()->json(['data' => $menu->load('categories')], 201);
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $menu->update([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
        ]);
        if (isset($data['categories'])) {
            $menu->categories()->sync($data['categories']);
        }
        return response()->json(['data' => $menu->load('categories')]);
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->categories()->detach();
        $menu->delete();
        return response()->json(['success' => true]);
    }
}

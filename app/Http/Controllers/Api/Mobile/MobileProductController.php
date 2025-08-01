<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Request;

class MobileProductController extends Controller
{
    public function index()
{
    $categories = \App\Models\Category::with([
        'products:id,name,price,category_id,image',
        'questions' => function($q) {
            $q->select('id', 'category_id', 'question', 'image');
        },
        'questions.choices' => function($q) {
            $q->select('id', 'question_id', 'choice', 'image');
        }
    ])->get(['id', 'name']);

    // Optionally, filter out categories with no products
    $categories = $categories->filter(function($cat) {
        return $cat->products->isNotEmpty();
    })->values();

    return response()->json(['data' => $categories]);
}


    
}
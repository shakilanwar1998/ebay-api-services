<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CategoryService;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function suggest(Request $request, CategoryService $service)
    {
        $title = $request->get('q', '');
        $categories = $service->getCategorySuggestions($title);
        // Return as id => name for Filament Select
        $results = [];
        foreach ($categories as $cat) {
            $results[$cat['id']] = $cat['name'];
        }
        return response()->json($results);
    }
} 
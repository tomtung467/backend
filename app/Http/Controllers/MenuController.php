<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use App\Models\Recipe;
use App\Services\Menu\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function getCategories()
    {
        $categories = Category::with('foods')->get();
        return response()->json($categories);
    }

    public function getCategoryFoods($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $foods = Food::where('category_id', $categoryId)->where('is_available', true)->get();
        return response()->json([
            'category' => $category,
            'foods' => $foods,
        ]);
    }

    public function getFoodDetails($foodId)
    {
        $food = Food::with('recipes')->findOrFail($foodId);
        return response()->json($food);
    }

    public function getAllMenus()
    {
        $menus = $this->menuService->getAllMenus();
        return response()->json($menus);
    }

    public function createFood(Request $request)
    {
        $validated = $request->validate([
            'food_name' => 'required|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
        ]);

        $food = Food::create($validated);
        return response()->json($food, 201);
    }

    public function updateFood($id, Request $request)
    {
        $food = Food::findOrFail($id);
        $food->update($request->validated());
        return response()->json($food);
    }

    public function deleteFood($id)
    {
        Food::destroy($id);
        return response()->json(['message' => 'Food deleted']);
    }

    public function getRecipes()
    {
        $recipes = Recipe::with('recipeItems.ingredient')->get();
        return response()->json($recipes);
    }

    public function createRecipe(Request $request)
    {
        $validated = $request->validate([
            'food_id' => 'required|exists:foods,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'preparation_time' => 'nullable|integer',
            'difficulty_level' => 'nullable|string',
        ]);

        $recipe = Recipe::create($validated);
        return response()->json($recipe, 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Food;
use App\Models\Recipe;
use App\Services\Menu\MenuService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function getCategories(Request $request)
    {
        $query = Category::query()->orderBy('name');

        if (!$request->boolean('simple')) {
            $query->withCount('foods');
        }

        $categories = $query->get(['id', 'name', 'description']);
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

    public function getFoods(Request $request)
    {
        $fields = $request->filled('fields')
            ? array_values(array_intersect(explode(',', $request->query('fields')), [
                'id', 'name', 'price', 'category_id', 'description', 'is_available',
                'is_popular', 'image_url', 'preparation_time', 'created_at', 'updated_at',
            ]))
            : ['id', 'name', 'price', 'category_id', 'description', 'is_available', 'is_popular', 'image_url', 'preparation_time'];

        if (!in_array('id', $fields, true)) {
            $fields[] = 'id';
        }

        $foods = Food::query()
            ->when($request->boolean('with_category'), fn ($query) => $query->with('category:id,name'))
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->query('category_id'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit((int) $request->query('limit', 200))
            ->get($fields);

        return response()->json($foods);
    }

    public function createFood(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'food_name' => 'nullable|string',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
            'image_url' => 'nullable|string|max:2048',
            'image_file' => 'nullable|image|max:2048',
            'preparation_time' => 'nullable|integer',
            'spicy_level' => 'nullable|integer',
            'calories' => 'nullable|integer',
            'is_popular' => 'nullable|boolean',
        ]);

        $this->prepareFoodImage($request, $validated);

        $validated['name'] = $validated['name'] ?? $validated['food_name'];
        unset($validated['food_name']);

        $food = Food::create($validated);
        return response()->json($food, 201);
    }

    public function updateFood($id, Request $request)
    {
        $food = Food::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'food_name' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'nullable|string',
            'is_available' => 'sometimes|boolean',
            'image_url' => 'nullable|string|max:2048',
            'image_file' => 'nullable|image|max:2048',
            'preparation_time' => 'nullable|integer',
            'spicy_level' => 'nullable|integer',
            'calories' => 'nullable|integer',
            'is_popular' => 'sometimes|boolean',
        ]);

        $this->prepareFoodImage($request, $validated);

        if (array_key_exists('food_name', $validated)) {
            $validated['name'] = $validated['food_name'];
        }
        unset($validated['food_name']);

        $food->update($validated);
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

    private function prepareFoodImage(Request $request, array &$validated): void
    {
        if (($validated['image_url'] ?? null) && str_starts_with($validated['image_url'], 'data:image/')) {
            throw ValidationException::withMessages([
                'image_url' => 'Please upload the image file or paste a normal image URL. Base64 images are not stored in image_url.',
            ]);
        }

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $directory = public_path('uploads/foods');

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            $filename = hash_file('sha256', $file->getRealPath()).'.'.$extension;
            $targetPath = $directory.DIRECTORY_SEPARATOR.$filename;

            if (!file_exists($targetPath)) {
                $file->move($directory, $filename);
            }

            $validated['image_url'] = '/uploads/foods/'.$filename;
        }

        unset($validated['image_file']);
    }
}

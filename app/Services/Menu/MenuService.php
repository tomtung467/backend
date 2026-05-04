<?php

namespace App\Services\Menu;

use App\Models\Category;
use App\Models\Food;
use App\Models\Recipe;
use App\Repositories\Menu\MenuRepository;

interface IMenuService
{
    public function getAllMenus();
    public function getCategoriesWithFoods();
    public function getFoodsByCategory($categoryId);
    public function getRecipes();
}

class MenuService implements IMenuService
{
    protected $menuRepository;

    public function __construct(MenuRepository $menuRepository)
    {
        $this->menuRepository = $menuRepository;
    }

    public function getAllMenus()
    {
        return $this->menuRepository->getAllMenus();
    }

    public function getCategoriesWithFoods()
    {
        return Category::with('foods')->get();
    }

    public function getFoodsByCategory($categoryId)
    {
        return Food::where('category_id', $categoryId)->where('is_available', true)->get();
    }

    public function getRecipes()
    {
        return Recipe::with('recipeItems.ingredient')->get();
    }

    public function createFood($data)
    {
        return Food::create($data);
    }

    public function updateFood($id, $data)
    {
        $food = Food::findOrFail($id);
        $food->update($data);
        return $food;
    }
}

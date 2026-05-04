<?php

namespace App\Repositories\Menu;

use App\Models\Food;
use App\Models\Category;
use App\Repositories\BaseRepository;

interface IMenuRepository
{
    public function getAllMenus();
    public function getFoodsByCategory($categoryId);
}

class MenuRepository extends BaseRepository implements IMenuRepository
{
    protected $model = Food::class;

    public function getAllMenus()
    {
        return Category::with('foods')->get();
    }

    public function getFoodsByCategory($categoryId)
    {
        return Food::where('category_id', $categoryId)
            ->where('is_available', true)
            ->get();
    }
}

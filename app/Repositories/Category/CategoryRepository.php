<?php
namespace App\Repositories\Category;
use App\Models\Category;
use App\Repositories\BaseRepository;
class CategoryRepository extends BaseRepository implements ICategoryRepository
{
    public function getAllCategories()
    {
        return Category::all();
    }
    public function getCategoryById($id)
    {
        return Category::findOrFail($id);
    }
    public function createCategory(array $data)
    {
        return Category::create($data);
    }
    public function updateCategory($id, array $data)
    {
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }
    public function deleteCategory($id)
    {        $category = Category::findOrFail($id);
        $category->delete();
        return true;
    }
}

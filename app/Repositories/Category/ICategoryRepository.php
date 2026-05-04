<?php
namespace App\Repositories\Category;
use App\Repositories\IBaseRepository;
interface ICategoryRepository extends IBaseRepository
{
    public function getAllCategories();
    public function getCategoryById($id);
    public function createCategory(array $data);
    public function updateCategory($id, array $data);
    public function deleteCategory($id);
}


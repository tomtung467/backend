<?php

namespace App\Repositories\Inventory;

use App\Models\Ingredient;
use App\Repositories\BaseRepository;

interface IInventoryRepository
{
    public function getLowStockItems();
    public function updateStock($id, $quantity);
}

class InventoryRepository extends BaseRepository implements IInventoryRepository
{
    protected $model = Ingredient::class;

    public function getLowStockItems()
    {
        return Ingredient::whereRaw('current_quantity <= min_quantity')->get();
    }

    public function updateStock($id, $quantity)
    {
        $ingredient = $this->find($id);
        $ingredient->update(['current_quantity' => $quantity]);
        return $ingredient;
    }
}

<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryLog;
use App\Repositories\Inventory\InventoryRepository;

interface IInventoryService
{
    public function updateStock($ingredientId, $data);
    public function getLowStockItems();
    public function deductFromRecipe($recipeId, $quantity);
}

class InventoryService implements IInventoryService
{
    protected $inventoryRepository;

    public function __construct(InventoryRepository $inventoryRepository)
    {
        $this->inventoryRepository = $inventoryRepository;
    }

    public function updateStock($ingredientId, $data)
    {
        $ingredient = Ingredient::findOrFail($ingredientId);
        
        $oldQuantity = $ingredient->current_quantity;
        $newQuantity = $oldQuantity + $data['quantity'];

        if ($newQuantity < 0) {
            throw new \Exception('Insufficient stock');
        }

        $ingredient->update(['current_quantity' => $newQuantity]);

        // Log the inventory change
        InventoryLog::create([
            'ingredient_id' => $ingredientId,
            'action_type' => $data['action_type'],
            'quantity_change' => $data['quantity'],
            'reference_type' => $data['reference_type'] ?? 'manual',
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $ingredient;
    }

    public function getLowStockItems()
    {
        return Ingredient::whereRaw('current_quantity <= min_quantity')->get();
    }

    public function deductFromRecipe($recipeId, $quantity = 1)
    {
        $recipe = \App\Models\Recipe::with('recipeItems')->findOrFail($recipeId);

        foreach ($recipe->recipeItems as $item) {
            $requiredQuantity = -($item->quantity * $quantity);
            $this->updateStock($item->ingredient_id, [
                'quantity' => $requiredQuantity,
                'action_type' => 'consumption',
                'reference_type' => 'recipe',
                'reference_id' => $recipeId,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryLog;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function getInventory()
    {
        $inventory = Ingredient::all();
        return response()->json($inventory);
    }

    public function getIngredientDetails($id)
    {
        $ingredient = Ingredient::with('logs')->findOrFail($id);
        return response()->json($ingredient);
    }

    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'action_type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $result = $this->inventoryService->updateStock($id, $validated);
        return response()->json($result);
    }

    public function getLowStockItems()
    {
        $lowStockItems = $this->inventoryService->getLowStockItems();
        return response()->json($lowStockItems);
    }

    public function getInventoryLogs($ingredientId)
    {
        $logs = InventoryLog::where('ingredient_id', $ingredientId)
            ->latest()
            ->paginate(50);
        return response()->json($logs);
    }

    public function createIngredient(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:ingredients',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric',
            'unit' => 'required|string',
            'min_stock_level' => 'required|numeric',
            'cost_per_unit' => 'required|numeric',
        ]);

        $ingredient = Ingredient::create($validated);
        return response()->json($ingredient, 201);
    }

    public function updateIngredient($id, Request $request)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->update($request->validated());
        return response()->json($ingredient);
    }
}

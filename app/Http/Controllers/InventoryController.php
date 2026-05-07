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

    public function getInventory(Request $request)
    {
        $fields = $request->boolean('summary')
            ? ['id', 'name', 'category', 'current_quantity', 'min_quantity', 'unit', 'unit_cost']
            : ['*'];

        $inventory = Ingredient::query()
            ->when($request->boolean('low_stock'), function ($query) {
                $query->whereRaw('current_quantity <= min_quantity');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit((int) $request->query('limit', 200))
            ->get($fields);

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
            'action_type' => 'nullable|string',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['action_type'] = $validated['action_type'] ?? $validated['reason'] ?? 'adjustment';

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
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'current_quantity' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'unit' => 'required|string',
            'min_quantity' => 'nullable|numeric',
            'min_stock_level' => 'nullable|numeric',
            'max_quantity' => 'nullable|numeric',
            'unit_cost' => 'nullable|numeric',
            'cost_per_unit' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['category'] = $validated['category'] ?? 'general';
        $validated['current_quantity'] = $validated['current_quantity'] ?? $validated['quantity'] ?? 0;
        $validated['min_quantity'] = $validated['min_quantity'] ?? $validated['min_stock_level'] ?? 0;
        $validated['max_quantity'] = $validated['max_quantity'] ?? max($validated['current_quantity'], $validated['min_quantity'], 0);
        $validated['unit_cost'] = $validated['unit_cost'] ?? $validated['cost_per_unit'] ?? 0;
        unset($validated['quantity'], $validated['min_stock_level'], $validated['cost_per_unit']);

        $ingredient = Ingredient::create($validated);
        return response()->json($ingredient, 201);
    }

    public function updateIngredient($id, Request $request)
    {
        $ingredient = Ingredient::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:ingredients,name,' . $id,
            'category' => 'sometimes|string',
            'description' => 'nullable|string',
            'current_quantity' => 'sometimes|numeric',
            'quantity' => 'sometimes|numeric',
            'unit' => 'sometimes|string',
            'min_quantity' => 'sometimes|numeric',
            'min_stock_level' => 'sometimes|numeric',
            'max_quantity' => 'sometimes|numeric',
            'unit_cost' => 'sometimes|numeric',
            'cost_per_unit' => 'sometimes|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('quantity', $validated)) {
            $validated['current_quantity'] = $validated['quantity'];
        }
        if (array_key_exists('min_stock_level', $validated)) {
            $validated['min_quantity'] = $validated['min_stock_level'];
        }
        if (array_key_exists('cost_per_unit', $validated)) {
            $validated['unit_cost'] = $validated['cost_per_unit'];
        }
        unset($validated['quantity'], $validated['min_stock_level'], $validated['cost_per_unit']);

        $ingredient->update($validated);
        return response()->json($ingredient);
    }

    public function deleteIngredient($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();

        return response()->json(['message' => 'Ingredient deleted']);
    }
}

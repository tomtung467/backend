<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableReservation;
use App\Models\TableMerge;
use App\Services\Tables\TableService;
use Illuminate\Http\Request;

class TableController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->tableService = $tableService;
    }

    public function getAllTables(Request $request)
    {
        if ($request->boolean('summary')) {
            $tables = Table::query()
                ->select('id', 'table_number', 'capacity', 'section', 'layout_x', 'layout_y', 'shape', 'merged_into_table_id', 'status', 'current_customer_count', 'occupied_since')
                ->with(['orders' => function ($query) {
                    $query->select('id', 'table_id', 'status', 'payment_requested_at')
                        ->whereNotIn('status', ['paid', 'cancelled'])
                        ->withCount(['orderItems as ready_items_count' => fn ($items) => $items->where('status', 'ready')])
                        ->latest();
                }])
                ->orderBy('table_number')
                ->get()
                ->map(function ($table) {
                    $activeOrders = $table->orders;
                    $table->active_orders_count = $activeOrders->count();
                    $table->ready_items_count = $activeOrders->sum('ready_items_count');
                    $table->payment_requested_at = optional(
                        $activeOrders->whereNotNull('payment_requested_at')->sortByDesc('payment_requested_at')->first()
                    )->payment_requested_at;
                    unset($table->orders);

                    return $table;
                });

            return response()->json($tables);
        }

        $tables = Table::with(['orders' => function ($query) {
            $query->whereNotIn('status', ['paid', 'cancelled'])
                ->with('orderItems.food')
                ->latest();
        }])->orderBy('table_number')->get()->map(function ($table) {
            $activeOrders = $table->orders;
            $table->active_orders_count = $activeOrders->count();
            $table->ready_items_count = $activeOrders
                ->flatMap(fn ($order) => $order->orderItems)
                ->where('status', 'ready')
                ->count();
            $table->payment_requested_at = optional(
                $activeOrders->whereNotNull('payment_requested_at')->sortByDesc('payment_requested_at')->first()
            )->payment_requested_at;

            return $table;
        });

        return response()->json($tables);
    }

    public function createTable(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|integer|unique:tables,table_number',
            'capacity' => 'required|integer|min:1',
            'section' => 'nullable|string|max:100',
            'layout_x' => 'nullable|integer|min:0',
            'layout_y' => 'nullable|integer|min:0',
            'shape' => 'nullable|string|in:circle,square,rectangle',
            'status' => 'nullable|string|in:empty,available,occupied,reserved',
        ]);

        if (($validated['status'] ?? null) === 'available') {
            $validated['status'] = 'empty';
        }
        $validated['status'] = $validated['status'] ?? 'empty';
        $validated['shape'] = $validated['shape'] ?? 'rectangle';

        $table = Table::create($validated);
        return response()->json($table, 201);
    }

    public function updateTable(Request $request, $id)
    {
        $table = Table::findOrFail($id);
        $validated = $request->validate([
            'table_number' => 'sometimes|integer|unique:tables,table_number,' . $id,
            'capacity' => 'sometimes|integer|min:1',
            'section' => 'nullable|string|max:100',
            'layout_x' => 'nullable|integer|min:0',
            'layout_y' => 'nullable|integer|min:0',
            'shape' => 'nullable|string|in:circle,square,rectangle',
            'status' => 'nullable|string|in:empty,available,occupied,reserved',
            'merged_into_table_id' => 'nullable|exists:tables,id',
        ]);

        if (($validated['status'] ?? null) === 'available') {
            $validated['status'] = 'empty';
        }

        $table->update($validated);
        return response()->json($table);
    }

    public function deleteTable($id)
    {
        $table = Table::findOrFail($id);
        if ($table->orders()->whereNotIn('status', ['paid', 'cancelled'])->exists()) {
            return response()->json(['message' => 'Cannot delete a table with active orders'], 422);
        }

        Table::where('merged_into_table_id', $table->id)->update(['merged_into_table_id' => null]);
        $table->delete();

        return response()->json(['message' => 'Table deleted']);
    }

    public function getTableDetails($id)
    {
        $table = Table::with(['orders', 'reservations'])->findOrFail($id);
        return response()->json($table);
    }

    public function updateTableStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:empty,available,occupied,reserved,maintenance',
        ]);

        $table = Table::findOrFail($id);
        $status = $validated['status'] === 'available' ? 'empty' : $validated['status'];
        $table->update([
            'status' => $status,
            'occupied_since' => $status === 'occupied' ? ($table->occupied_since ?? now()) : null,
            'current_customer_count' => $status === 'occupied' ? max(1, (int) $table->current_customer_count) : 0,
        ]);

        return response()->json($table);
    }

    public function assignTable(Request $request, $id)
    {
        $validated = $request->validate([
            'number_of_guests' => 'required|integer|min:1',
        ]);

        $table = Table::findOrFail($id);
        if (!$this->tableService->canAssignTable($table, $validated['number_of_guests'])) {
            return response()->json(['error' => 'Table cannot accommodate guests'], 400);
        }

        $table->update([
            'status' => 'occupied',
            'current_customer_count' => $validated['number_of_guests'],
            'occupied_since' => now(),
        ]);
        return response()->json($table);
    }

    public function releaseTable($id)
    {
        $table = Table::findOrFail($id);
        $this->tableService->releaseTable($table);
        $table->update([
            'status' => 'empty',
            'current_customer_count' => 0,
            'occupied_since' => null,
        ]);

        return response()->json($table);
    }

    public function createReservation(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
            'reservation_time' => 'required|date_format:Y-m-d H:i:s',
            'number_of_guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string',
            'pre_order_items' => 'nullable|array',
        ]);

        $table = Table::findOrFail($validated['table_id']);
        if ($table->status !== 'empty') {
            return response()->json(['message' => 'Only empty tables can be reserved'], 422);
        }

        $reservationTime = \Carbon\Carbon::parse($validated['reservation_time']);
        $hasConflict = TableReservation::where('table_id', $validated['table_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('reservation_time', [
                $reservationTime->copy()->subHours(2),
                $reservationTime->copy()->addHours(2),
            ])
            ->exists();

        if ($hasConflict) {
            return response()->json(['message' => 'This table already has a reservation around that time'], 422);
        }

        $reservation = TableReservation::create($validated);
        return response()->json($reservation, 201);
    }

    public function getReservations()
    {
        $reservations = TableReservation::where('status', 'confirmed')
            ->where('reservation_time', '>=', now())
            ->orderBy('reservation_time')
            ->get();

        return response()->json($reservations);
    }

    public function mergeTables(Request $request)
    {
        $validated = $request->validate([
            'primary_table_id' => 'required|exists:tables,id',
            'merged_table_ids' => 'required|array',
            'merged_table_ids.*' => 'exists:tables,id',
        ]);

        Table::whereIn('id', $validated['merged_table_ids'])
            ->where('id', '!=', $validated['primary_table_id'])
            ->update(['merged_into_table_id' => $validated['primary_table_id']]);

        $primary = Table::findOrFail($validated['primary_table_id']);
        return response()->json($primary->fresh(), 201);
    }

    public function unmergeTables($mergeId)
    {
        Table::where('merged_into_table_id', $mergeId)->update(['merged_into_table_id' => null]);
        return response()->json(['message' => 'Tables unmerged successfully']);
    }
}

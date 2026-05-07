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
                ->select('id', 'table_number', 'capacity', 'section', 'status', 'current_customer_count', 'occupied_since')
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
        ]);

        $merge = $this->tableService->mergeTables($validated);
        return response()->json($merge, 201);
    }

    public function unmergeTables($mergeId)
    {
        $merge = TableMerge::findOrFail($mergeId);
        $this->tableService->unmergeTables($merge);

        return response()->json(['message' => 'Tables unmerged successfully']);
    }
}

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

    public function getAllTables()
    {
        $tables = Table::all();
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
            'status' => 'required|string|in:available,occupied,reserved,maintenance',
        ]);

        $table = Table::findOrFail($id);
        $table->update(['status' => $validated['status']]);

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

        $table->update(['status' => 'occupied']);
        return response()->json($table);
    }

    public function releaseTable($id)
    {
        $table = Table::findOrFail($id);
        $this->tableService->releaseTable($table);
        $table->update(['status' => 'available']);

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
        ]);

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

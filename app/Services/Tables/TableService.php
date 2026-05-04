<?php

namespace App\Services\Tables;

use App\Models\Table;
use App\Models\TableMerge;

interface ITableService
{
    public function canAssignTable($table, $numberOfGuests);
    public function releaseTable($table);
    public function mergeTables($data);
    public function unmergeTables($merge);
}

class TableService implements ITableService
{
    public function canAssignTable($table, $numberOfGuests)
    {
        return $table->capacity >= $numberOfGuests && $table->status === 'available';
    }

    public function releaseTable($table)
    {
        return $table->update(['status' => 'available']);
    }

    public function mergeTables($data)
    {
        $merge = TableMerge::create([
            'primary_table_id' => $data['primary_table_id'],
            'merged_table_ids' => json_encode($data['merged_table_ids']),
            'merged_at' => now(),
            'merged_by' => auth()->id(),
        ]);

        // Update table statuses
        Table::whereIn('id', $data['merged_table_ids'])->update(['status' => 'merged']);

        return $merge;
    }

    public function unmergeTables($merge)
    {
        $mergedTableIds = json_decode($merge->merged_table_ids);
        
        Table::whereIn('id', $mergedTableIds)->update(['status' => 'available']);
        
        $merge->update(['unmerged_at' => now()]);

        return $merge;
    }
}

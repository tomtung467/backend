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
        return $table->capacity >= $numberOfGuests && in_array($table->status, ['empty', 'available'], true);
    }

    public function releaseTable($table)
    {
        return $table->update(['status' => 'empty']);
    }

    public function mergeTables($data)
    {
        $merge = TableMerge::create([
            'primary_table_id' => $data['primary_table_id'],
            'merged_table_ids' => json_encode($data['merged_table_ids']),
            'merged_at' => now(),
            'merged_by' => auth()->id(),
        ]);

        Table::whereIn('id', $data['merged_table_ids'])
            ->update(['merged_into_table_id' => $data['primary_table_id']]);

        return $merge;
    }

    public function unmergeTables($merge)
    {
        $mergedTableIds = json_decode($merge->merged_table_ids);
        
        Table::whereIn('id', $mergedTableIds)->update(['merged_into_table_id' => null]);
        
        $merge->update(['unmerged_at' => now()]);

        return $merge;
    }
}

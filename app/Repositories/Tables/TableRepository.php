<?php

namespace App\Repositories\Tables;

use App\Models\Table;
use App\Repositories\BaseRepository;

interface ITableRepository
{
    public function getAvailableTables();
    public function getTablesByCapacity($capacity);
}

class TableRepository extends BaseRepository implements ITableRepository
{
    protected $model = Table::class;

    public function getAvailableTables()
    {
        return Table::where('status', 'available')->get();
    }

    public function getTablesByCapacity($capacity)
    {
        return Table::where('capacity', '>=', $capacity)
            ->where('status', 'available')
            ->get();
    }
}

<?php
namespace App\Enums\Table;

enum TableStatusEnum: string
{
    case EMPTY = 'empty';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
}

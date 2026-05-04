<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Table;

class TablePolicy
{
    /**
     * Determine whether the user can view the table.
     */
    public function view(User $user, Table $table)
    {
        return $user->hasRole(['staff', 'chef', 'admin']);
    }

    /**
     * Determine whether the user can create tables.
     */
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the table.
     */
    public function update(User $user, Table $table)
    {
        return $user->hasRole(['staff', 'admin']);
    }

    /**
     * Determine whether the user can delete the table.
     */
    public function delete(User $user, Table $table)
    {
        // Only admin can delete tables, and only if empty
        return $user->hasRole('admin') && $table->status === 'empty';
    }

    /**
     * Determine whether the user can assign table.
     */
    public function assign(User $user, Table $table)
    {
        return $user->hasRole(['staff', 'admin']);
    }

    /**
     * Determine whether the user can merge tables.
     */
    public function merge(User $user)
    {
        return $user->hasRole(['staff', 'admin']);
    }
}

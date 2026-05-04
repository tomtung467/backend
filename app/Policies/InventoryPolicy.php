<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ingredient;

class InventoryPolicy
{
    /**
     * Determine whether the user can view inventory.
     */
    public function view(User $user)
    {
        return $user->hasRole(['staff', 'chef', 'admin']);
    }

    /**
     * Determine whether the user can create ingredients.
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'chef']);
    }

    /**
     * Determine whether the user can update inventory.
     */
    public function update(User $user, Ingredient $ingredient)
    {
        return $user->hasRole(['admin', 'chef', 'staff']);
    }

    /**
     * Determine whether the user can delete ingredients.
     */
    public function delete(User $user, Ingredient $ingredient)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view low stock alerts.
     */
    public function viewLowStock(User $user)
    {
        return $user->hasRole(['chef', 'admin']);
    }
}

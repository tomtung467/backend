<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Food;

class MenuPolicy
{
    /**
     * Determine whether the user can view the menu.
     */
    public function view(User $user, Food $food)
    {
        return true; // Everyone can view menu
    }

    /**
     * Determine whether the user can create menu items.
     */
    public function create(User $user)
    {
        return $user->hasRole(['admin', 'chef']);
    }

    /**
     * Determine whether the user can update menu items.
     */
    public function update(User $user, Food $food)
    {
        return $user->hasRole(['admin', 'chef']);
    }

    /**
     * Determine whether the user can delete menu items.
     */
    public function delete(User $user, Food $food)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage recipes.
     */
    public function manageRecipe(User $user)
    {
        return $user->hasRole(['admin', 'chef']);
    }
}

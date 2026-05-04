<?php

namespace App\Traits;

use App\Models\Role;

use Illuminate\Database\Eloquent\Builder;

trait HasRoles
{
    /**
     * Check if user has a role
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return $this->roles()->whereIn('name', $role)->exists();
        }
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        return $this->roles()->whereIn('name', (array)$roles)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles($roles)
    {
        $roles = (array)$roles;
        $rolesCount = count($roles);
        return $this->roles()->whereIn('name', $roles)->count() === $rolesCount;
    }

    /**
     * Assign a role to user
     */
    public function assignRole($role)
    {
        if (!$this->hasRole($role)) {
            return $this->roles()->attach(
                Role::where('name', $role)->firstOrCreate(['name' => $role])
            );
        }
    }

    /**
     * Remove a role from user
     */
    public function removeRole($role)
    {
        return $this->roles()->detach(Role::where('name', $role)->first());
    }

    /**
     * User roles relationship
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Get all permissions through roles
     */
    public function permissions()
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique();
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee)
    {
        // Users can view their own profile, or admins can view anyone
        return $user->id === $employee->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update employees.
     */
    public function update(User $user, Employee $employee)
    {
        // Users can update their own profile, admins can update anyone
        return $user->id === $employee->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete employees.
     */
    public function delete(User $user, Employee $employee)
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view KPI data.
     */
    public function viewKPI(User $user, Employee $employee)
    {
        // Users can view their own KPI, managers and admins can view all
        return $user->id === $employee->user_id ||
               $user->hasRole(['manager', 'admin']);
    }
}

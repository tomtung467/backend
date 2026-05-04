<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'user_id',
        'first_name',
        'last_name',
        'department_id',
        'position',
        'salary',
        'hire_date',
        'employee_id_number',
        'phone',
        'address',
        'status',
    ];

    protected $dates = [
        'hire_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function kpiSnapshots()
    {
        return $this->hasMany(KPISnapshot::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class, 'created_by');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}


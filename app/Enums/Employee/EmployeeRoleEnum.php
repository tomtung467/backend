<?php
namespace App\Enums\Employee;

enum EmployeeRoleEnum: string
{
    case ADMIN = 'admin';
    case WAITER = 'waiter';
    case CHEF = 'chef';
    case CASHER = 'casher';
}

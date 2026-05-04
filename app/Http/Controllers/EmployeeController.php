<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\KPISnapshot;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    public function getEmployees()
    {
        $employees = Employee::with(['department', 'user'])->get();
        return response()->json($employees);
    }

    public function getEmployeeDetails($id)
    {
        $employee = Employee::with(['department', 'user', 'kpiSnapshots'])->findOrFail($id);
        return response()->json($employee);
    }

    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'required|unique:employees',
            'user_id' => 'required|exists:users,id|unique:employees',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string',
            'salary' => 'required|numeric',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        $employee = Employee::create($validated);
        return response()->json($employee, 201);
    }

    public function updateEmployee($id, Request $request)
    {
        $employee = Employee::findOrFail($id);
        $employee->update($request->validated());
        return response()->json($employee);
    }

    public function getKPIData($employeeId)
    {
        $kpiData = KPISnapshot::where('employee_id', $employeeId)
            ->latest('date')
            ->limit(30)
            ->get();

        return response()->json($kpiData);
    }

    public function getDepartments()
    {
        $departments = Department::with('employees')->get();
        return response()->json($departments);
    }

    public function getEmployeesByDepartment($departmentId)
    {
        $employees = Employee::where('department_id', $departmentId)->get();
        return response()->json($employees);
    }

    public function updateEmployeeStatus($id, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        $employee = Employee::findOrFail($id);
        $employee->update($validated);
        return response()->json($employee);
    }
}

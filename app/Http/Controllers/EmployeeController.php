<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\KPISnapshot;
use App\Models\User;
use App\Services\Employee\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'employee_id_number' => 'nullable|unique:employees',
            'employee_code' => 'nullable|unique:employees,employee_id_number',
            'user_id' => 'nullable|exists:users,id|unique:employees',
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|in:staff,chef,manager,admin',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'required|string',
            'salary' => 'required|numeric',
            'hire_date' => 'required|date',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,on_leave,terminated',
        ]);

        if (empty($validated['user_id'])) {
            if (empty($validated['email'])) {
                return response()->json(['message' => 'Email is required when creating a new user for employee'], 422);
            }

            $user = User::create([
                'name' => $validated['name'] ?? trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?? 'password123'),
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'] ?? 'staff',
            ]);

            $validated['user_id'] = $user->id;
        }

        $validated['employee_id_number'] = $validated['employee_id_number'] ?? $validated['employee_code'];
        $validated['employee_id_number'] = $validated['employee_id_number'] ?? 'EMP' . str_pad((string) ((Employee::max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
        unset($validated['employee_code'], $validated['name'], $validated['email'], $validated['password'], $validated['role']);

        $employee = Employee::create($validated);
        return response()->json($employee, 201);
    }

    public function updateEmployee($id, Request $request)
    {
        $employee = Employee::findOrFail($id);
        $validated = $request->validate([
            'employee_id_number' => 'sometimes|unique:employees,employee_id_number,' . $id,
            'employee_code' => 'sometimes|unique:employees,employee_id_number,' . $id,
            'user_id' => 'sometimes|exists:users,id|unique:employees,user_id,' . $id,
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'sometimes|string',
            'salary' => 'sometimes|numeric',
            'hire_date' => 'sometimes|date',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,on_leave,terminated',
        ]);

        if (array_key_exists('employee_code', $validated)) {
            $validated['employee_id_number'] = $validated['employee_code'];
        }
        unset($validated['employee_code']);

        $employee->update($validated);
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

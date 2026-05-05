<?php
namespace App\Repositories\Employee;
use App\Models\Employee;
use App\Enums\Employee\EmployeeStatusEnum;
use App\Repositories\BaseRepository;

class EmployeeRepository extends BaseRepository implements IEmployeeRepository
{
    protected $model = Employee::class;

    public function find($id)
    {
        return Employee::find($id);
    }
    public function create(array $data)
    {
        return Employee::create($data);
    }
    public function update($id, array $data)
    {
        $employee = Employee::find($id);
        if ($employee) {
            $employee->update($data);
            return $employee;
        }
        return null;
    }
    public function delete($id)
    {
        $employee = Employee::find($id);
        if ($employee) {
            $employee->status = EmployeeStatusEnum::INACTIVE->value;
            $employee->save();
            return true;
        }
        return false;
    }

}

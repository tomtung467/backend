<?php
namespace App\Services\Employee;
use App\Repositories\Employee\IEmployeeRepository;
use App\Services\BaseService;

class EmployeeService extends BaseService implements IEmployeeService
{
    protected $employeeRepository;
    public function __construct(IEmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }
    public function getAllEmployees()
    {
        return $this->employeeRepository->all();
    }
    public function getEmployeeById($id)
    {
        return $this->employeeRepository->find($id);
    }
    public function createEmployee(array $data)
    {
        return $this->employeeRepository->create($data);
    }
    public function updateEmployee($id, array $data)
    {
        return $this->employeeRepository->update($id, $data);
    }
    public function deleteEmployee($id)
    {
        return $this->employeeRepository->delete($id);
    }
}

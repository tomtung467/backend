<?php
namespace App\Repositories\Employee;
use \App\Repositories\IBaseRepository;
interface IEmployeeRepository extends IBaseRepository
{
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}

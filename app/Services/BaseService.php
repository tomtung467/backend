<?php
namespace App\Services;
use App\Traits\ApiResponseTrait;

abstract class BaseService
{
    //use ApiResponseTrait;
    protected $repository;
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
        public function getPaginated($perPage = 10) {
        return $this->repository->getAllWithPagination($perPage);
    }
    public function getAll()
    {
        $data = $this->repository->all();
        return $data;
    }
    public function getById($id)
    {
        $data = $this->repository->find($id);
        if (!$data) {
            return null;
        }
        return $data;
    }
    public function create(array $attributes)
    {
        $data = $this->repository->create($attributes);
        return $data;
    }
    public function update($id, array $attributes)
    {
        $updated = $this->repository->update($id, $attributes);
        if (!$updated) {
            return null;
        }
        return $updated;

    }
    public function delete($id)
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            return null;
        }
        return true;
    }
}

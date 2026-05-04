<?php
namespace App\Repositories;
use Illuminate\Database\Eloquent\Model;
abstract class BaseRepository implements IBaseRepository
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    public function getModel(){}

    public function setModel()
    {
        $model = app()->make($this->getModel());
        $this->model = $model;
    }

    public function all(array $with = [])
    {
        return $this->model->with($with)->get();
    }
     public function allWithFilter($filter, array $with = [])
    {
        return $this->model->with($with)->filter($filter)->get();
    }
    public function getAllWithPagination($perPage = 10, array $with = [])
    {
        return $this->model->with($with)->paginate($perPage);
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    public function update($id, array $attributes)
    {
        $record = $this->model->find($id);
        if ($record) {
            $record->update($attributes);
            return $record->fresh();
        }
        return false;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }
}

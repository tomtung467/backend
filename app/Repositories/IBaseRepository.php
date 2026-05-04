<?php

namespace App\Repositories;

interface IBaseRepository
{
    public function all(array $with = []);

    public function allWithFilter($filter, array $with = []);

    public function getAllWithPagination($perPage = 10, array $with = []);

    public function find($id);

    public function create(array $attributes);

    public function update($id, array $attributes);

    public function delete($id);
}

<?php
namespace App\Repositories\User;

use App\Repositories\BaseRepository;
use App\Models\User;
use App\Repositories\User\IUserRepository;

class UserRepository extends BaseRepository implements IUserRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
    public function getModel()
    {
        return User::class;
    }
    public function VisibleTo(User $user)
    {
        $query = $this->model->newQuery();

        if ($user->role && $user->role->isAdmin()) {
            return $query;
        }

        if ($user->role && $user->role->isManager()) {
            return $query->where('role', '!=', 'admin');
        }

        return $query->where('id', $user->id);
    }
}

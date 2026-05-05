<?php
namespace App\Repositories\User;

use App\Repositories\BaseRepository;
use App\Models\User;
use App\Repositories\User\IUserRepository;

class UserRepository extends BaseRepository implements IUserRepository
{
    protected $model = User::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function getModel()
    {
        return User::class;
    }
    public function VisibleTo(User $user)
    {
        $model = $this->model instanceof User ? $this->model : app($this->model);
        $query = $model->newQuery();

        if ($user->role && $user->role->isAdmin()) {
            return $query;
        }

        if ($user->role && $user->role->isManager()) {
            return $query->where('role', '!=', 'admin');
        }

        return $query->where('id', $user->id);
    }
}

<?php
namespace App\Repositories\User;
use App\Repositories\IBaseRepository;
use App\Models\User;
interface IUserRepository extends IBaseRepository
{
    public function VisibleTo(User $user);
}

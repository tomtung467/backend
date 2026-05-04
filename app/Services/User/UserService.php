<?php
namespace App\Services\User;

use App\Repositories\User\IUserRepository;
use App\Services\User\IUserService;
use App\Services\BaseService;
use Nette\Utils\Json;

class UserService extends BaseService implements IUserService
{
    protected $userRepository;

    public function __construct(IUserRepository $userRepository)
    {
        parent::__construct($userRepository);
        $this->userRepository = $userRepository;
    }
    public function getAll()
    {
        $user = auth()->guard()->user();
        $data = $this->repository->VisibleTo($user)->with('leaveApplications')->get();
        return $data;
    }
    public function getPaginated($perPage = 10)
    {
        $user = auth()->guard()->user();
        $data = $this->repository->VisibleTo($user)->with('leaveApplications')->paginate($perPage);
        return $data;
    }
}

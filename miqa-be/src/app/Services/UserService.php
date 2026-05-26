<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get paginated users
     */
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->getPaginated($fields, $perPage);
    }

    /**
     * Get all users without pagination
     */
    public function getAll(array $fields = ['*']): Collection
    {
        return $this->userRepository->getAll($fields);
    }

    /**
     * Search users by query
     */
    public function searchUsers(string $query, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->searchByNameAndEmail($query, $fields, $perPage);
    }

    /**
     * Get users by gender
     */
    public function findUsersByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->findByGender($gender, $fields, $perPage);
    }

    /**
     * Get users by role
     */
    public function findUsersByRole(string $role, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->findByRole($role, $fields, $perPage);
    }

    /**
     * Search users with pagination for modal
     */
    public function searchWithPagination(string $query = '', array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        return $this->userRepository->searchWithPagination($query, $fields, $page, $perPage);
    }
}
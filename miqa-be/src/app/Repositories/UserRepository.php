<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)->with('roles')->latest()->paginate($perPage);
    }

    public function getAll(array $fields = ['*']): Collection
    {
        return User::select($fields)->with('roles')->latest()->get();
    }

    /**
     * Ditambahkan with('roles') untuk efisiensi UserResource di API profile
     */
    public function getById(string $id, array $fields = ['*']): User
    {
        return User::select($fields)->with('roles')->findOrFail($id);
    }

    /**
     * Satukan logika pencarian agar tidak redundan
     */
    public function search(string $query, array $fields = ['*'], int $perPage = 10, int $page = null): LengthAwarePaginator
    {
        return User::select($fields)
            ->with('roles')
            ->when(!empty($query), function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
            });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function searchForModal(string $query, array $fields = ['*'], int $limit = 6): Collection
    {
        return User::select($fields)
            ->with('roles')
            ->when(!empty($query), function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                });
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function findByCode(string $code, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)->with('roles')->where('code', $code)->latest()->paginate($perPage);
    }

    public function findByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)->with('roles')->where('gender', $gender)->latest()->paginate($perPage);
    }

    public function findByRole(string $role, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)->role($role)->with('roles')->latest()->paginate($perPage);
    }

    public function create(array $data): User
    {
        $manager = User::create($data);
        $manager->assignRole('manager');
        return $manager->fresh();
    }

    public function update(string $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function delete(string $id): bool
    {
        $user = User::findOrFail($id);
        return (bool) $user->delete();
    }
}

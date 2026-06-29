<?php

namespace App\Repositories;

use App\Models\Presence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PresenceRepository
{
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Presence::select($fields)
            ->latest('created_at')
            ->with('roles')
            ->paginate($perPage);
    }

    public function getAll(array $fields = ['*']): Collection
    {
        return Presence::select($fields)->with('roles')->latest('created_at')->get();
    }

    /**
     * Ditambahkan with('roles') untuk efisiensi PresenceResource di API profile
     */
    public function getById(string $id, array $fields = ['*']): Presence
    {
        return Presence::select($fields)->with('roles')->findOrFail($id);
    }

    /**
     * Satukan logika pencarian agar tidak redundan
     */
    public function search(string $query, array $fields = ['*'], int $perPage = 10, int $page = null): LengthAwarePaginator
    {
        return Presence::select($fields)
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
        return Presence::select($fields)
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
        return Presence::select($fields)->with('roles')->where('code', $code)->latest()->paginate($perPage);
    }

    public function findByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Presence::select($fields)->with('roles')->where('gender', $gender)->latest()->paginate($perPage);
    }

    public function findByRole(string $role, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Presence::select($fields)->role($role)->with('roles')->latest()->paginate($perPage);
    }

    public function create(array $data): Presence
    {
        $manager = Presence::create($data);
        $manager->assignRole('manager');
        return $manager->fresh();
    }

    public function update(string $id, array $data): Presence
    {
        $presence = Presence::findOrFail($id);
        $presence->update($data);
        return $presence->fresh();
    }

    public function delete(string $id): bool
    {
        $presence = Presence::findOrFail($id);
        return (bool) $presence->delete();
    }
}

<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\TeacherRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TeacherRepository implements TeacherRepositoryInterface
{
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)
            ->role('teacher')
            ->with('roles')
            ->withCount('subjects')
            ->latest()
            ->paginate($perPage);
    }

    public function getAll(array $fields = ['*']): Collection
    {
        return User::select($fields)
            ->role('teacher')
            ->with('roles')
            ->withCount('subjects')
            ->latest()
            ->get();
    }

    public function findWithSubjects(string $id, array $fields = ['*']): User
    {
        return User::select($fields)
            ->role('teacher')
            ->with([
                'roles',
                'subjects:id,name,photo,tagline,teacher_id,topic_id',
                'subjects.topic:id,name,photo'
            ])
            ->findOrFail($id);
    }

    /**
     * Replaces explicit skip/take calculations by using Laravel's native manual-page length paginator parameter.
     */
    public function searchByNameAndEmail(string $query, array $fields = ['*'], int $perPage = 10, int $page = null): LengthAwarePaginator
    {
        return User::select($fields)
            ->role('teacher')
            ->with('roles')
            ->withCount('subjects')
            ->when(!empty($query), function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
            });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Unified modal list generator matching your UserRepository formatting.
     */
    public function searchForModal(string $query, array $fields = ['*'], int $limit = 6): Collection
    {
        return User::select($fields)
            ->role('teacher')
            ->withCount('subjects')
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

    /**
     * Cleans up custom manual slicing arrays into structural Paginator metadata arrays.
     */
    public function searchWithPagination(string $query, array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        $paginator = $this->searchByNameAndEmail($query, $fields, $perPage, $page);

        return [
            'data'         => $paginator->items(),
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'has_more'     => $paginator->hasMorePages()
        ];
    }

    public function findManyByIds(array $ids, array $fields = ['*']): Collection
    {
        return User::select($fields)
            ->role('teacher')
            ->whereIn('id', $ids)
            ->with('subjects')
            ->withCount('subjects')
            ->get();
    }

    public function findByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return User::select($fields)
            ->role('teacher')
            ->with('roles')
            ->withCount('subjects')
            ->where('gender', $gender)
            ->latest()
            ->paginate($perPage);
    }

    public function getUnassignedTeachers(array $fields = ['*']): Collection
    {
        return User::select($fields)
            ->role('teacher')
            ->doesntHave('subjects')
            ->latest()
            ->get();
    }

    public function create(array $data): User
    {
        $teacher = User::create($data);
        $teacher->assignRole('teacher');
        return $teacher;
    }

    public function update(string $id, array $data): User
    {
        $teacher = User::role('teacher')->findOrFail($id);
        $teacher->update($data);
        return $teacher->fresh();
    }

    public function delete(string $id): bool
    {
        $teacher = User::role('teacher')->findOrFail($id);
        return (bool) $teacher->delete($id);
    }
}

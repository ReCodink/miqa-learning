<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TeacherRepositoryInterface
{
    public function getPaginated(
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function getAll(
        array $fields = ['*']
    ): Collection;

    public function findWithSubjects(
        string $id,
        array $fields = ['*']
    ): User;

    public function searchByNameAndEmail(
        string $query,
        array $fields = ['*'],
        int $perPage = 10,
        ?int $page = null
    ): LengthAwarePaginator;

    public function searchForModal(
        string $query,
        array $fields = ['*'],
        int $limit = 6
    ): Collection;

    public function searchWithPagination(
        string $query,
        array $fields = ['*'],
        int $page = 1,
        int $perPage = 10
    ): array;

    public function findManyByIds(
        array $ids,
        array $fields = ['*']
    ): Collection;

    public function findByGender(
        string $gender,
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function getUnassignedTeachers(
        array $fields = ['*']
    ): Collection;

    public function create(
        array $data
    ): User;

    public function update(
        string $id,
        array $data
    ): User;

    public function delete(
        string $id
    ): bool;
}

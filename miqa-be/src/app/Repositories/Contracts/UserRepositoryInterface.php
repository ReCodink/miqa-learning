<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function getPaginated(
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function getAll(
        array $fields = ['*']
    ): Collection;

    public function getById(
        string $id,
        array $fields = ['*']
    ): User;

    public function search(
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

    public function findByCode(
        string $code,
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function findByGender(
        string $gender,
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function findByRole(
        string $role,
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

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

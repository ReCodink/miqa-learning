<?php

namespace App\Repositories\Contracts;

use App\Models\Topic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TopicRepositoryInterface
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
    ): Topic;

    public function searchByNameAndDescription(
        string $query,
        array $fields = ['*'],
        int $perPage = 10
    ): LengthAwarePaginator;

    public function searchWithPagination(
        string $query,
        array $fields = ['*'],
        int $page = 1,
        int $perPage = 10
    ): array;

    public function searchForModal(
        string $query,
        array $fields = ['*'],
        int $limit = 6
    ): Collection;

    public function findManyByIds(
        array $ids,
        array $fields = ['*']
    ): Collection;

    public function create(
        array $data
    ): Topic;

    public function update(
        string $id,
        array $data
    ): Topic;

    public function delete(
        string $id
    ): bool;
}

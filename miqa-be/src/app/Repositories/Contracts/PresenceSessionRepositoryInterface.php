<?php

namespace App\Repositories\Contracts;

use App\Models\PresenceSession;
use Illuminate\Database\Eloquent\Collection;

interface PresenceSessionRepositoryInterface
{
    public function findById(
        string $id
    ): ?PresenceSession;

    public function getActiveSessions(
        string $userId
    ): Collection;

    public function create(
        array $data
    ): PresenceSession;

    public function update(
        string $id,
        array $data
    ): bool;

    public function delete(
        string $id
    ): bool;

    public function activateSession(
        string $id
    ): bool;

    public function deactivateSession(
        string $id
    ): bool;
}

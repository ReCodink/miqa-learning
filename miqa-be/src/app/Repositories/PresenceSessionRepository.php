<?php

namespace App\Repositories;

use App\Models\PresenceSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PresenceSessionRepository
{
    protected array $defaultRelations = ['creator'];

    /**
     * Mengambil daftar sesi dengan paginasi
     */
    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return PresenceSession::with($this->defaultRelations)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Membuat sesi absensi baru
     */
    public function create(array $data): PresenceSession
    {
        return PresenceSession::create($data);
    }

    /**
     * Mencari sesi berdasarkan ID
     */
    public function findById(string $id): ?PresenceSession
    {
        return PresenceSession::with($this->defaultRelations)->find($id);
    }

    /**
     * Mendapatkan sesi aktif pada kelas tertentu
     */
    public function getActiveSessionByClass(string $classRoomId): ?PresenceSession
    {
        return PresenceSession::where('class_room_id', $classRoomId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Menyalakan atau mematikan status aktif sesi
     */
    public function updateStatus(string $id, bool $isActive): bool
    {
        return PresenceSession::where('id', $id)->update([
            'is_active' => $isActive
        ]);
    }
}

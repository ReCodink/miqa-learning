<?php

namespace App\Repositories;

use App\Models\Presence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PresenceRepository
{
    /**
     * Eager loading relasi yang sering digunakan oleh PresenceResource / Controller.
     */
    protected array $defaultRelations = ['user', 'session'];

    /**
     * Ambil data presensi berpaginasi (Asli)
     */
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        if ($fields !== ['*'] && !in_array('user_id', $fields)) {
            $fields[] = 'user_id';
        }
        if ($fields !== ['*'] && !in_array('presence_session_id', $fields)) {
            $fields[] = 'presence_session_id';
        }

        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->latest('checked_in_at')
            ->paginate($perPage);
    }

    /**
     * Ambil semua data presensi tanpa paginasi (Asli)
     */
    public function getAll(array $fields = ['*']): Collection
    {
        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->latest('checked_in_at')
            ->get();
    }

    /**
     * Ambil satu data presensi berdasarkan ID (Asli)
     */
    public function getById(string $id, array $fields = ['*']): Presence
    {
        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->findOrFail($id);
    }

    /**
     * Logika pencarian berdasarkan Nama atau Email User (Asli)
     */
    public function search(string $query, array $fields = ['*'], int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->when(!empty($query), function ($q) use ($query) {
            $q->whereHas('user', function ($sub) use ($query) {
                    $sub->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
            });
            })
            ->latest('checked_in_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Pencarian terbatas untuk komponen Modal komponen UI (Asli)
     */
    public function searchForModal(string $query, array $fields = ['*'], int $limit = 6): Collection
    {
        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->when(!empty($query), function ($q) use ($query) {
            $q->whereHas('user', function ($sub) use ($query) {
                    $sub->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                });
            })
            ->latest('checked_in_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Filter presensi berdasarkan status validasi terbaru (Asli)
     */
    public function findByValidationStatus(string $status, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Presence::select($fields)
            ->with($this->defaultRelations)
            ->where('status', $status)
            ->latest('checked_in_at')
            ->paginate($perPage);
    }

    /**
     * Mengecek apakah user sudah melakukan absen di sesi tertentu (Asli)
     */
    public function hasUserCheckedIn(string $userId, string $sessionId): bool
    {
        return Presence::where('user_id', $userId)
            ->where('presence_session_id', $sessionId)
            ->exists();
    }

    /**
     * Membuat data presensi baru / Check-in (Asli)
     */
    public function create(array $data): Presence
    {
        return Presence::create($data);
    }

    /**
     * Memperbarui data presensi (Asli)
     */
    public function update(string $id, array $data): Presence
    {
        $presence = Presence::findOrFail($id);
        $presence->update($data);

        return $presence->fresh($this->defaultRelations);
    }

    /**
     * Menghapus rekaman presensi (Asli)
     */
    public function delete(string $id): bool
    {
        $presence = Presence::findOrFail($id);
        return (bool) $presence->delete();
    }

    // =========================================================================
    // METHOD TAMBAHAN UNTUK MENUNJANG LOGIKA DI ATTENDANCESERVICE
    // =========================================================================

    /**
     * Mengambil data presensi terakhir milik user
     * Digunakan oleh AttendanceService::checkImpossibleVelocity()
     */
    public function getLastPresenceByUserId(string $userId): ?Presence
    {
        return Presence::where('user_id', $userId)
            ->latest('checked_in_at')
            ->first();
    }

    /**
     * Menghitung jumlah data presensi dalam satu sesi berdasarkan status tertentu
     * Digunakan oleh AttendanceService::getSessionReport()
     */
    public function countBySessionAndStatus(string $sessionId, ?string $status = null): int
    {
        return Presence::where('presence_session_id', $sessionId)
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->count();
    }

    /**
     * Mendapatkan ringkasan total hitung presensi berdasarkan status milik user
     * Digunakan oleh AttendanceService::getUserAttendanceStats()
     */
    public function getUserStatsSummary(string $userId): array
    {
        $baseQuery = Presence::where('user_id', $userId);

        return [
            'total' => (clone $baseQuery)->count(),
            'valid' => (clone $baseQuery)->where('status', 'valid')->count(),
            'suspicious' => (clone $baseQuery)->where('status', 'suspicious')->count(),
        ];
    }
}

<?php

namespace App\Services;

use App\Repositories\PresenceRepository;
use App\Models\PresenceSession;
use App\Models\Presence;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PresenceService
{
    protected PresenceRepository $presenceRepository;

    public function __construct(PresenceRepository $presenceRepository)
    {
        $this->presenceRepository = $presenceRepository;
    }

    /**
     * Mengambil data presensi berdasarkan filter (Pencarian, Status Validasi, atau Default)
     */
    public function getPresences(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        if (!empty($filters['search'])) {
            return $this->presenceRepository->search($filters['search'], ['*'], $perPage);
        }

        if (isset($filters['is_valid'])) {
            // Mengubah string 'true'/'false' dari request menjadi boolean
            $isValid = filter_var($filters['is_valid'], FILTER_VALIDATE_BOOLEAN);
            return $this->presenceRepository->findByValidationStatus($isValid, ['*'], $perPage);
        }

        return $this->presenceRepository->getPaginated(['*'], $perPage);
    }

    /**
     * Pencarian khusus untuk komponen Modal UI
     */
    public function searchForModal(string $query, int $limit = 6): Collection
    {
        return $this->presenceRepository->searchForModal($query, ['*'], $limit);
    }

    /**
     * Ambil detail presensi berdasarkan ID
     */
    public function getPresenceById(string $id): Presence
    {
        return $this->presenceRepository->getById($id);
    }

    /**
     * Proses Check-in / Pembuatan Presensi Baru
     * Di sini kita tambahkan business logic menggunakan PresenceSession
     */
    public function checkIn(array $data): Presence
    {
        // Validasi apakah session ID dikirimkan
        if (isset($data['presence_session_id'])) {
            $session = PresenceSession::findOrFail($data['presence_session_id']);

            // Business Logic: Pastikan sesi presensi masih aktif
            if (!$session->is_active) {
                throw ValidationException::withMessages([
                    'presence_session_id' => ['Sesi presensi ini sudah ditutup atau tidak aktif.'],
                ]);
            }
        }

        // Set default waktu check-in jika tidak dikirim dari device
        if (!isset($data['checked_in_at'])) {
            $data['checked_in_at'] = now();
        }

        return $this->presenceRepository->create($data);
    }

    /**
     * Update data presensi (Koreksi Admin / Validasi)
     */
    public function updatePresence(string $id, array $data): Presence
    {
        return $this->presenceRepository->update($id, $data);
    }

    /**
     * Hapus data rekaman presensi
     */
    public function deletePresence(string $id): bool
    {
        return $this->presenceRepository->delete($id);
    }

    
}

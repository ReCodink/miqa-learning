<?php

namespace App\Services;

use App\Repositories\PresenceSessionRepository;
use App\Repositories\PresenceRepository;
use App\Models\PresenceSession;
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceSessionService
{
    public function __construct(
        protected PresenceSessionRepository $sessionRepo,
        protected PresenceRepository        $presenceRepo
    ) {}

    /**
     * Membuat sesi absensi baru (diinisiasi oleh Dosen)
     */
    public function createSession(array $data, string $creatorId): PresenceSession
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $data['created_by_user_id'] = $creatorId;
            $data['is_active'] = true; // Otomatis aktif saat dibuat

            // Atur default waktu selesai jika tidak diisi (misal: otomatis tutup dalam 2 jam)
            if (!isset($data['end_at'])) {
                $data['start_at'] = now();
                $data['end_at'] = now()->addHours(2);
            }

            return $this->sessionRepo->create($data);
        });
    }

    /**
     * Menutup atau mengaktifkan kembali sesi secara manual
     */
    public function toggleSessionStatus(string $sessionId, bool $isActive): bool
    {
        return DB::transaction(function () use ($sessionId, $isActive) {
            $session = $this->sessionRepo->findById($sessionId);

            if (!$session) {
                throw new Exception("Sesi absensi tidak ditemukan.");
            }

            return $this->sessionRepo->updateStatus($sessionId, $isActive);
        });
    }

    /**
     * Mendapatkan ringkasan laporan kehadiran untuk satu sesi kelas
     */
    public function getSessionReport(string $sessionId): array
    {
        $session = $this->sessionRepo->findById($sessionId);
        if (!$session) {
            throw new Exception("Sesi tidak ditemukan.");
        }

        return [
            'session_name' => $session->session_name,
            'is_active'    => $session->is_active,
            'stats'        => [
                'total_present' => $this->presenceRepo->countBySessionAndStatus($sessionId),
                'valid'         => $this->presenceRepo->countBySessionAndStatus($sessionId, 'valid'),
                'suspicious'    => $this->presenceRepo->countBySessionAndStatus($sessionId, 'suspicious'),
                'invalid'       => $this->presenceRepo->countBySessionAndStatus($sessionId, 'invalid'),
            ]
        ];
    }
}

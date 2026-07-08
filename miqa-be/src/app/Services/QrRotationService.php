<?php

namespace App\Services;

use App\Repositories\PresenceQrTokenRepository;
use App\Repositories\PresenceSessionRepository;
use App\Models\PresenceQrToken;
use Illuminate\Support\Facades\DB;
use Exception;

class QrRotationService
{
    public function __construct(
        protected PresenceQrTokenRepository $qrTokenRepo,
        protected PresenceSessionRepository $sessionRepo
    ) {}

    /**
     * Membuat token baru untuk sesi yang sedang aktif
     */
    public function generateNewToken(string $sessionId, int $durationSeconds = 15): PresenceQrToken
    {
        return DB::transaction(function () use ($sessionId, $durationSeconds) {
            $session = $this->sessionRepo->findById($sessionId);

            // Token tidak boleh digenerate jika sesi sudah dimatikan dosen
            if (!$session || !$session->is_active) {
                throw new Exception("Tidak bisa generate QR. Sesi sudah tidak aktif.");
            }

            return $this->qrTokenRepo->createToken($sessionId, $durationSeconds);
        });
    }

    /**
     * Membersihkan sampah token lama di database agar performa tetap ringan
     * (Panggil fungsi ini di App\Console\Commands atau Laravel Scheduler)
     */
    public function cleanUpExpiredTokens(): int
    {
        return $this->qrTokenRepo->deleteExpiredTokens();
    }
}

<?php

namespace App\Repositories;

use App\Models\PresenceQrToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PresenceQrTokenRepository
{
    /**
     * Eager loading relasi default ke sesi absensi
     */
    protected array $defaultRelations = ['session'];

    /**
     * Mengambil daftar token QR berpaginasi (Berguna untuk log/monitoring admin)
     */
    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return PresenceQrToken::with($this->defaultRelations)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Menemukan token berdasarkan ID primary key-nya (ULID)
     */
    public function findById(string $id): ?PresenceQrToken
    {
        return PresenceQrToken::with($this->defaultRelations)->find($id);
    }

    /**
     * Membuat token QR baru yang dinamis untuk sebuah sesi.
     */
    public function createToken(string $sessionId, int $durationSeconds = 15): PresenceQrToken
    {
        return PresenceQrToken::create([
            'presence_session_id' => $sessionId,
            // Catatan: Pastikan di model PresenceQrToken Anda menambahkan 'token' ke dalam perlindungan $fillable
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addSeconds($durationSeconds),
        ]);
    }

    /**
     * Memvalidasi apakah token QR yang di-scan oleh user itu ada dan belum kedaluwarsa.
     */
    public function validateToken(string $token): ?PresenceQrToken
    {
        return PresenceQrToken::with($this->defaultRelations)
            ->where('token', $token)
            ->isUnexpired() // Memanfaatkan local scope yang sudah dibuat di Model PresenceQrToken
            ->first();
    }

    /**
     * Mengambil riwayat token QR yang pernah digenerate oleh sesi tertentu
     */
    public function getTokensBySessionId(string $sessionId): Collection
    {
        return PresenceQrToken::where('presence_session_id', $sessionId)
            ->latest()
            ->get();
    }

    /**
     * Menghapus token lama yang sudah kedaluwarsa (untuk maintenance performa berkala).
     * Sangat disarankan untuk dieksekusi melalui Laravel Scheduler / Cron Job.
     */
    public function deleteExpiredTokens(): int
    {
        return PresenceQrToken::where('expires_at', '<', now())->delete();
    }
}

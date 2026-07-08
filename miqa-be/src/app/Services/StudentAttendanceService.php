<?php

namespace App\Services;

use App\Repositories\PresenceRepository;
use App\Repositories\PresenceSessionRepository;
use App\Repositories\PresenceQrTokenRepository;
use App\Models\Presence;
use Exception;

class StudentAttendanceService
{
    public function __construct(
        protected PresenceQrTokenRepository $qrTokenRepo,
        protected PresenceSessionRepository $sessionRepo,
        protected PresenceRepository        $presenceRepo
    ) {}

    /**
     * Memproses pemindaian QR Code oleh mahasiswa
     */
    public function scanQrCode(string $userId, string $tokenString, array $studentGps): Presence
    {
        // 1. Validasi keaslian dan masa berlaku token QR
        $qrToken = $this->qrTokenRepo->validateToken($tokenString);
        if (!$qrToken) {
            throw new Exception("QR Code tidak valid atau sudah kadaluarsa. Silahkan scan ulang QR terbaru di layar.");
        }

        // 2. Validasi status sesi kelas
        $session = $this->sessionRepo->findById($qrToken->presence_session_id);
        if (!$session || !$session->is_active) {
            throw new Exception("Gagal absen. Sesi kelas ini telah ditutup oleh pengajar.");
        }

        // 3. Validasi Geofencing (Jika koordinat GPS sesi diset oleh dosen)
        $isWithinGeofence = true;
        if ($session->gps_latitude && $session->gps_longitude) {
            $isWithinGeofence = $this->checkGeofence(
                $studentGps['latitude'],
                $studentGps['longitude'],
                $session->gps_latitude,
                $session->gps_longitude,
                $session->gps_radius_meters
            );

            // Kebijakan LMS: Jika di luar pagar GPS, kunci/gagalkan absensi langsung
            if (!$isWithinGeofence) {
                throw new Exception("Anda berada di luar radius lokasi kelas yang diizinkan.");
            }
        }

        // 4. Proteksi Anti-Duplikat (Double Check-in)
        if ($this->presenceRepo->hasUserCheckedIn($userId, $session->id)) {
            throw new Exception("Anda sudah tercatat hadir dalam sesi kelas ini.");
        }

        // 5. Analisis Forensik Kecurangan (Anti Titip Absen / Impossible Velocity)
        $status = 'valid';
        $lastPresence = $this->presenceRepo->getLastPresenceByUserId($userId);

        if ($lastPresence && $this->isVelocityImpossible($lastPresence, $studentGps)) {
            $status = 'suspicious'; // Ditandai mencurigakan jika lokasi melompat terlalu cepat
        }

        // 6. Eksekusi penyimpanan data melalui PresenceRepository
        return $this->presenceRepo->create([
            'presence_session_id' => $session->id,
            'user_id'             => $userId,
            'gps_latitude'        => $studentGps['latitude'],
            'gps_longitude'       => $studentGps['longitude'],
            'is_within_geofence'  => $isWithinGeofence,
            'ip_address'          => request()->ip(),
            'user_agent'          => request()->userAgent(),
            'status'              => $status,
            'checked_in_at'       => now()
        ]);
    }

    /**
     * Menghitung jarak antara Mahasiswa dan Dosen menggunakan Rumus Haversine
     */
    protected function checkGeofence($userLat, $userLng, $targetLat, $targetLng, $radiusMeters): bool
    {
        $earthRadius = 6371000; // Dalam satuan meter

        $latDiff = deg2rad($targetLat - $userLat);
        $lngDiff = deg2rad($targetLng - $userLng);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($userLat)) * cos(deg2rad($targetLat)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c; // Hasil jarak dalam meter

        return $distance <= $radiusMeters;
    }

    /**
     * Algoritma sederhana untuk mendeteksi manipulasi lokasi GPS (Fake GPS)
     */
    protected function isVelocityImpossible($lastPresence, array $currentGps): bool
    {
        if (!$lastPresence->gps_latitude) return false;

        // Jika absen terakhir kurang dari 5 menit yang lalu, tapi lokasi bergeser > 2 KM, berarti indikasi Fake GPS / akun dipakai orang lain
        $timeDifferenceMinutes = now()->diffInMinutes($lastPresence->checked_in_at);

        if ($timeDifferenceMinutes < 5) {
            // Panggil kembali fungsi hitung jarak di atas
            $isClose = $this->checkGeofence(
                $currentGps['latitude'], $currentGps['longitude'],
                $lastPresence->gps_latitude, $lastPresence->gps_longitude,
                2000 // 2000 meter / 2 KM
            );
            return !$isClose; // True jika jarak terlalu jauh dalam waktu singkat (Impossible Velocity)
        }

        return false;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('presence_session_id')->constrained('presence_sessions')->onDelete('cascade');
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');

            $table->timestamp('checked_in_at')->useCurrent();

            // Lokasi GPS user saat melakukan scan
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->boolean('is_within_geofence')->default(true);

            // Log metadata ringkas (Menghilangkan kebutuhan tabel device & audit log terpisah)
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Status Validasi untuk mengganti fitur security flags di awal
            $table->enum('status', ['valid', 'invalid', 'suspicious'])->default('valid');

            $table->timestamps();

            // Mencegah user melakukan absensi ganda di sesi yang sama
            $table->unique(['user_id', 'presence_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_qr_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('presence_session_id')->constrained('presence_sessions')->onDelete('cascade');

            $table->uuid('token')->unique(); // Token unik untuk di-render jadi QR Code
            $table->timestamp('expires_at');
            $table->timestamps();

            // Indeks performa untuk pengecekan validitas QR secara real-time
            $table->index(['presence_session_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_qr_tokens');
    }
};

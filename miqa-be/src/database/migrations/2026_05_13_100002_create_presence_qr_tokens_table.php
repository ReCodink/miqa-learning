<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presence_qr_tokens', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // UUID v4 for QR code - the actual token to scan
            $table->uuid('uuid')->unique();

            $table->foreignUlid('presence_session_id')->constrained('presence_sessions')->onDelete('cascade');
            $table->foreignUlid('created_by_user_id')->constrained('users')->onDelete('cascade');

            // Token lifecycle
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at'); // 15-30 seconds after generated_at
            $table->boolean('is_used')->default(false);
            $table->boolean('is_revoked')->default(false);

            // Usage tracking
            $table->foreignUlid('used_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('used_at')->nullable();

            // Revocation tracking
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();

            $table->timestamps();

            // Critical indexes
            $table->index('uuid');
            $table->index('presence_session_id');
            $table->index('expires_at');
            $table->index('is_used');
            $table->index('created_by_user_id');

            // Performance index for expiration cleanup
            $table->index(['is_used', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_qr_tokens');
    }
};

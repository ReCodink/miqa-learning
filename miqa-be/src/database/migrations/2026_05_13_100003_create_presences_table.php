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
        Schema::create('presences', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('qr_token_id')->constrained('presence_qr_tokens')->onDelete('cascade');
            $table->uuid('presence_session_id');
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');

            // Check-in/out tracking
            $table->timestamp('checked_in_at')->useCurrent();
            $table->timestamp('checked_out_at')->nullable();
            $table->integer('duration_minutes')->nullable();

            // GPS Location Data
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->decimal('gps_distance_meters', 10, 2)->nullable();
            $table->boolean('is_within_geofence')->nullable();

            // Device & Network Info
            $table->json('device_fingerprint_json')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Validation Status
            $table->boolean('is_valid')->default(false);

            $table->timestamps();

            // Add foreign key constraint for presence_session_id after table creation
            $table->foreign('presence_session_id')
                ->references('id')
                ->on('presence_sessions')
                ->onDelete('cascade');
            $table->unique(['user_id', 'presence_session_id']);

            // Essential indexes
            $table->index('user_id');
            $table->index('presence_session_id');
            $table->index('checked_in_at');
            $table->index('is_valid');
            $table->index('qr_token_id');

            // Performance indexes
            $table->index(['presence_session_id', 'is_valid']);
            $table->index(['user_id', 'checked_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};

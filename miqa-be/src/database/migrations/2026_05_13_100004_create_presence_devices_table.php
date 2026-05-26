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
        Schema::create('presence_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');

            // Device identification
            $table->string('device_fingerprint_hash');
            $table->text('device_name')->nullable();
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('os_name')->nullable(); // iOS, Android, Windows, macOS
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();

            // Trust status
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            // Indexes and constraints
            $table->index('user_id');
            $table->index('device_fingerprint_hash');
            $table->index('is_trusted');
            $table->index('last_seen_at');

            // Composite unique key: same device fingerprint can belong to different users
            $table->unique(['user_id', 'device_fingerprint_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_devices');
    }
};

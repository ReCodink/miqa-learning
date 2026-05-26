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
        Schema::create('presence_audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('presence_id')->nullable()->constrained('presences')->onDelete('set null');
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');

            // Action logging
            $table->string('action'); // Human readable action
            $table->enum('action_type', [
                'qr_generated',
                'qr_scanned',
                'attendance_recorded',
                'attendance_verified',
                'fraud_detected',
                'flag_reviewed',
                'device_trusted',
                'session_started',
                'session_ended'
            ]);
            $table->json('action_details')->nullable();

            // Actor information
            $table->foreignUlid('actor_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('actor_role')->nullable(); // manager, teacher, student, system

            // Request context
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();

            // Essential indexes
            $table->index('presence_id');
            $table->index('user_id');
            $table->index('action_type');
            $table->index('created_at');

            // Audit trail performance
            $table->index(['user_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_audit_logs');
    }
};

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
        Schema::create('presence_security_flags', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('presence_id')->constrained('presences')->onDelete('cascade');
            $table->foreignUlid('user_id')->constrained()->onDelete('cascade');

            // Flag classification
            $table->string('flag_type'); // duplicate_token, outside_geofence, impossible_velocity, etc.
            $table->enum('flag_severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('flag_description');
            $table->json('flag_metadata')->nullable(); // Additional context

            // Review tracking
            $table->boolean('is_reviewed')->default(false);
            $table->foreignUlid('reviewed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->string('action_taken')->nullable(); // approved, rejected, investigate

            $table->timestamps();

            // Indexes for security monitoring
            $table->index('presence_id');
            $table->index('user_id');
            $table->index('flag_severity');
            $table->index('is_reviewed');
            $table->index('flag_type');
            $table->index('created_at');

            // Performance
            $table->index(['flag_severity', 'is_reviewed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_security_flags');
    }
};

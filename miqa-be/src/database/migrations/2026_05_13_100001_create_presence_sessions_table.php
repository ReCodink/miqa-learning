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
        Schema::create('presence_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUlid('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_name');
            $table->enum('session_type', ['class', 'event', 'exam_preparation'])->default('class');
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('actual_start_at')->nullable();
            $table->timestamp('actual_end_at')->nullable();

            // GPS Geofencing
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->integer('gps_radius_meters')->default(50);

            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for frequent queries
            $table->index('class_room_id');
            $table->index('created_by_user_id');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_sessions');
    }
};

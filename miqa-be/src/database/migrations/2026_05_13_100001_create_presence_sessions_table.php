<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('created_by_user_id')->constrained('users')->onDelete('cascade');

            $table->string('session_name');
            $table->enum('session_type', ['class', 'event', 'exam_preparation'])->default('class');

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // GPS Geofencing (Tetap ada untuk masa depan, buat nullable jika opsional)
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->integer('gps_radius_meters')->default(50);

            $table->boolean('is_active')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indeks esensial
            $table->index(['class_room_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_sessions');
    }
};

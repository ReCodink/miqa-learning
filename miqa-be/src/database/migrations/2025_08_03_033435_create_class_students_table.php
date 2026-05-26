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
        Schema::create('class_students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->foreignUlid('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignUlid('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->boolean('has_passed')->default(false);
            $table->string('rapport')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'class_room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_students');
    }
};

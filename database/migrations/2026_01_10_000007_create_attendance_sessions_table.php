<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            // NULL after the session closes -> the old QR can never match again
            $table->string('qr_token', 64)->nullable()->unique();

            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->timestamps();

            // One attendance session per class per day
            $table->unique(['class_id', 'session_date'], 'attendance_sessions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};

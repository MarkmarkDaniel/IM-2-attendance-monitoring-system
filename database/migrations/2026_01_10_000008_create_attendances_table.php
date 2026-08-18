<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_session_id')
                  ->nullable()
                  ->constrained('attendance_sessions')
                  ->cascadeOnDelete();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->date('attendance_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['Present', 'Late', 'Absent'])->default('Absent');
            $table->string('remarks', 255)->nullable();

            // Which user (instructor or admin) saved this row; NULL = QR self scan
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // THE duplicate-prevention rule of the whole system
            $table->unique(
                ['class_id', 'student_id', 'attendance_date'],
                'attendances_unique_record'
            );

            $table->index(['attendance_date', 'status'], 'attendances_date_status_index');
            $table->index('student_id', 'attendances_student_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

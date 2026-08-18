<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                  ->constrained('instructors')
                  ->cascadeOnDelete();

            $table->string('school_year', 20);          // e.g. 2026-2027
            $table->enum('semester', ['1st Semester', '2nd Semester', 'Summer']);
            $table->string('section', 20);
            $table->string('room', 50)->nullable();

            $table->enum('schedule_day', [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday',
                'Friday', 'Saturday', 'Sunday',
            ]);

            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();

            // One offering per subject + section + term
            $table->unique(
                ['subject_id', 'section', 'school_year', 'semester'],
                'classes_offering_unique'
            );

            $table->index('instructor_id', 'classes_instructor_index');
            $table->index('schedule_day', 'classes_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('student_number', 30)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();

            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->restrictOnDelete();

            $table->enum('year_level', ['1st Year', '2nd Year', '3rd Year', '4th Year']);
            $table->string('section', 20);
            $table->enum('status', ['Active', 'Inactive', 'Dropped'])->default('Active');
            $table->timestamps();

            $table->index(['department_id', 'year_level', 'section'], 'students_filter_index');
            $table->index('last_name', 'students_last_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

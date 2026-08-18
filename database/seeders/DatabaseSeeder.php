<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters. Departments must exist before students,
     * subjects before classes, classes before enrollments, and so on.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            AdminSeeder::class,
            InstructorSeeder::class,
            StudentSeeder::class,
            SubjectSeeder::class,
            ClassSeeder::class,
            EnrollmentSeeder::class,
            AttendanceSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}

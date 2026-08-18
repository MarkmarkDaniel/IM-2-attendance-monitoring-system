<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'department_code' => 'BSIT',
                'department_name' => 'Bachelor of Science in Information Technology',
                'description'     => 'Focuses on application development, networking, database management, and IT service management.',
            ],
            [
                'department_code' => 'BSCS',
                'department_name' => 'Bachelor of Science in Computer Science',
                'description'     => 'Focuses on algorithms, computing theory, software engineering, and intelligent systems.',
            ],
            [
                'department_code' => 'BSCE',
                'department_name' => 'Bachelor of Science in Computer Engineering',
                'description'     => 'Focuses on hardware, embedded systems, digital logic, and computer architecture.',
            ],
            [
                'department_code' => 'BSBA',
                'department_name' => 'Bachelor of Science in Business Administration',
                'description'     => 'Focuses on management, marketing, finance, and business operations.',
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['department_code' => $department['department_code']],
                $department
            );
        }
    }
}

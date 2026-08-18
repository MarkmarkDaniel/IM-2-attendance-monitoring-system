<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'department_code');

        $subjects = [
            ['ITP104', 'Information Management 2',            'Advanced database design, SQL, stored procedures, and data administration.', 3, 'BSIT'],
            ['ITP111', 'System Administration and Maintenance','Server setup, user management, backup and recovery, and system monitoring.', 3, 'BSIT'],
            ['ITP102', 'Web Systems and Technologies',        'Front-end and back-end web development using modern frameworks.',            3, 'BSIT'],
            ['ITP105', 'Object Oriented Programming',         'Classes, inheritance, polymorphism, and design patterns.',                   3, 'BSIT'],
            ['CS201',  'Data Structures and Algorithms',      'Arrays, linked lists, trees, graphs, sorting, and searching.',               3, 'BSCS'],
            ['CS202',  'Discrete Structures',                 'Logic, set theory, combinatorics, and graph theory.',                        3, 'BSCS'],
            ['CPE101', 'Digital Logic Design',                'Boolean algebra, logic gates, flip-flops, and sequential circuits.',         3, 'BSCE'],
            ['BA101',  'Principles of Management',            'Planning, organizing, leading, and controlling in organizations.',           3, 'BSBA'],
        ];

        foreach ($subjects as [$code, $name, $description, $units, $dept]) {
            Subject::updateOrCreate(
                ['subject_code' => $code],
                [
                    'subject_name'  => $name,
                    'description'   => $description,
                    'units'         => $units,
                    'department_id' => $departments[$dept],
                ]
            );
        }
    }
}

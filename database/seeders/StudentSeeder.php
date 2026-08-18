<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'department_code');

        $students = [
            // ---- DEMO ACCOUNT ----
            ['2026-00001', 'Juan Miguel', 'Reyes',    'Ramos',      'student@example.com',              '09171234001', 'BSIT', '3rd Year', 'A'],

            // ---- BSIT 3rd Year - Section A ----
            ['2026-00002', 'Andrea Nicole', 'Cruz',   'Villanueva', 'andrea.villanueva@example.com',    '09171234002', 'BSIT', '3rd Year', 'A'],
            ['2026-00003', 'Mark Anthony', null,      'Delos Reyes','mark.delosreyes@example.com',      '09171234003', 'BSIT', '3rd Year', 'A'],
            ['2026-00004', 'Patricia Mae', 'Lim',     'Bautista',   'patricia.bautista@example.com',    '09171234004', 'BSIT', '3rd Year', 'A'],
            ['2026-00005', 'John Carlo', 'Santos',    'Mendoza',    'johncarlo.mendoza@example.com',    '09171234005', 'BSIT', '3rd Year', 'A'],
            ['2026-00006', 'Kristine Joy', null,      'Aguilar',    'kristine.aguilar@example.com',     '09171234006', 'BSIT', '3rd Year', 'A'],
            ['2026-00007', 'Paolo Miguel', 'Tan',     'Cruz',       'paolo.cruz@example.com',           '09171234007', 'BSIT', '3rd Year', 'A'],
            ['2026-00008', 'Angelica Marie', 'Reyes', 'Torres',     'angelica.torres@example.com',      '09171234008', 'BSIT', '3rd Year', 'A'],

            // ---- BSIT 2nd Year - Section A ----
            ['2026-00009', 'Rafael Antonio', null,    'Garcia',     'rafael.garcia@example.com',        '09171234009', 'BSIT', '2nd Year', 'A'],
            ['2026-00010', 'Bea Katrina', 'Ong',      'Domingo',    'bea.domingo@example.com',          '09171234010', 'BSIT', '2nd Year', 'A'],

            // ---- BSIT 2nd Year - Section B ----
            ['2026-00011', 'Christian Dave', 'Uy',    'Navarro',    'christian.navarro@example.com',    '09171234011', 'BSIT', '2nd Year', 'B'],
            ['2026-00012', 'Jasmine Rose', null,      'Fernandez',  'jasmine.fernandez@example.com',    '09171234012', 'BSIT', '2nd Year', 'B'],

            // ---- BSCS 3rd Year - Section A ----
            ['2026-00013', 'Emmanuel Jose', 'Ramos',  'Pascual',    'emmanuel.pascual@example.com',     '09171234013', 'BSCS', '3rd Year', 'A'],
            ['2026-00014', 'Sofia Isabel', 'Chua',    'Lim',        'sofia.lim@example.com',            '09171234014', 'BSCS', '3rd Year', 'A'],

            // ---- BSCE 1st Year - Section A ----
            ['2026-00015', 'Nathaniel Ryan', null,    'Ocampo',     'nathaniel.ocampo@example.com',     '09171234015', 'BSCE', '1st Year', 'A'],
        ];

        foreach ($students as $row) {
            [$number, $first, $middle, $last, $email, $phone, $dept, $year, $section] = $row;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $first . ' ' . $last,
                    'password' => Hash::make('password'),
                    'role'     => User::ROLE_STUDENT,
                ]
            );

            Student::updateOrCreate(
                ['student_number' => $number],
                [
                    'user_id'       => $user->id,
                    'first_name'    => $first,
                    'middle_name'   => $middle,
                    'last_name'     => $last,
                    'email'         => $email,
                    'phone'         => $phone,
                    'department_id' => $departments[$dept],
                    'year_level'    => $year,
                    'section'       => $section,
                    'status'        => 'Active',
                ]
            );
        }
    }
}

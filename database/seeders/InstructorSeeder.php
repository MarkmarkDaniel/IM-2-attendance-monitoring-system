<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        // department_code => id
        $departments = Department::pluck('id', 'department_code');

        $instructors = [
            [
                'employee_number' => 'EMP-2026-001',
                'first_name'      => 'Juan',
                'middle_name'     => 'Cruz',
                'last_name'       => 'Dela Cruz',
                'email'           => 'instructor@example.com',   // DEMO ACCOUNT
                'department'      => 'BSIT',
                'status'          => 'Active',
            ],
            [
                'employee_number' => 'EMP-2026-002',
                'first_name'      => 'Maria',
                'middle_name'     => 'Lopez',
                'last_name'       => 'Santos',
                'email'           => 'maria.santos@example.com',
                'department'      => 'BSIT',
                'status'          => 'Active',
            ],
            [
                'employee_number' => 'EMP-2026-003',
                'first_name'      => 'Roberto',
                'middle_name'     => null,
                'last_name'       => 'Reyes',
                'email'           => 'roberto.reyes@example.com',
                'department'      => 'BSCS',
                'status'          => 'Active',
            ],
            [
                'employee_number' => 'EMP-2026-004',
                'first_name'      => 'Ana',
                'middle_name'     => 'Rivera',
                'last_name'       => 'Bautista',
                'email'           => 'ana.bautista@example.com',
                'department'      => 'BSCE',
                'status'          => 'Active',
            ],
        ];

        foreach ($instructors as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['first_name'] . ' ' . $data['last_name'],
                    'password' => Hash::make('password'),
                    'role'     => User::ROLE_INSTRUCTOR,
                ]
            );

            Instructor::updateOrCreate(
                ['employee_number' => $data['employee_number']],
                [
                    'user_id'       => $user->id,
                    'first_name'    => $data['first_name'],
                    'middle_name'   => $data['middle_name'],
                    'last_name'     => $data['last_name'],
                    'email'         => $data['email'],
                    'department_id' => $departments[$data['department']],
                    'status'        => $data['status'],
                ]
            );
        }
    }
}

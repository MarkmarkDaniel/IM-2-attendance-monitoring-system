<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        // Which student numbers belong to which subject class
        $map = [
            'ITP104' => ['2026-00001', '2026-00002', '2026-00003', '2026-00004',
                         '2026-00005', '2026-00006', '2026-00007', '2026-00008'],

            'ITP111' => ['2026-00001', '2026-00002', '2026-00003', '2026-00004',
                         '2026-00005', '2026-00006', '2026-00007', '2026-00008'],

            'ITP102' => ['2026-00009', '2026-00010'],
            'ITP105' => ['2026-00011', '2026-00012'],
            'CS201'  => ['2026-00013', '2026-00014'],
            'CPE101' => ['2026-00015'],
        ];

        $students = Student::pluck('id', 'student_number');

        foreach ($map as $subjectCode => $studentNumbers) {
            $class = ClassModel::whereHas(
                'subject',
                fn (Builder $q) => $q->where('subject_code', $subjectCode)
            )->first();

            if (! $class) {
                continue;
            }

            foreach ($studentNumbers as $number) {
                Enrollment::updateOrCreate(
                    [
                        'class_id'   => $class->id,
                        'student_id' => $students[$number],
                    ],
                    ['enrollment_status' => 'Enrolled']
                );
            }
        }
    }
}

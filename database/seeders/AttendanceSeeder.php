<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttendanceSeeder extends Seeder
{
    /**
     * Attendance pattern legend:
     *   P = Present   L = Late   A = Absent
     *
     * The pattern array position matches the enrolled student order
     * (2026-00001 first, 2026-00002 second, and so on).
     */
    public function run(): void
    {
        // ==========================================================
        // ITP104 - Monday 8:00 AM - 11:00 AM
        // ==========================================================
        $this->seedHistory(
            subjectCode:  'ITP104',
            presentTime:  '07:52:00',
            lateTime:     '08:14:00',
            sessionStart: '08:00:00',
            sessionEnd:   '11:00:00',
            pattern: [
                '2026-07-06' => ['P', 'P', 'P', 'L', 'P', 'A', 'P', 'P'],
                '2026-07-13' => ['P', 'P', 'L', 'P', 'P', 'P', 'A', 'P'],
                '2026-07-20' => ['P', 'A', 'P', 'P', 'L', 'P', 'P', 'P'],
                '2026-07-27' => ['P', 'P', 'P', 'P', 'P', 'L', 'P', 'A'],
                '2026-08-03' => ['L', 'P', 'P', 'A', 'P', 'P', 'P', 'P'],
                '2026-08-10' => ['P', 'P', 'A', 'P', 'P', 'P', 'L', 'P'],
            ]
        );

        // ==========================================================
        // ITP111 - Tuesday 1:00 PM - 4:00 PM
        // ==========================================================
        $this->seedHistory(
            subjectCode:  'ITP111',
            presentTime:  '12:55:00',
            lateTime:     '13:18:00',
            sessionStart: '13:00:00',
            sessionEnd:   '16:00:00',
            pattern: [
                '2026-07-07' => ['P', 'P', 'A', 'P', 'P', 'P', 'P', 'L'],
                '2026-07-14' => ['P', 'L', 'P', 'P', 'A', 'P', 'P', 'P'],
                '2026-07-21' => ['P', 'P', 'P', 'L', 'P', 'P', 'A', 'P'],
                '2026-07-28' => ['A', 'P', 'P', 'P', 'P', 'L', 'P', 'P'],
                '2026-08-04' => ['P', 'P', 'L', 'P', 'P', 'P', 'P', 'A'],
                '2026-08-11' => ['P', 'A', 'P', 'P', 'L', 'P', 'P', 'P'],
            ]
        );

        // ==========================================================
        // ITP102 - Wednesday 8:00 AM - 11:00 AM (2 students)
        // ==========================================================
        $this->seedHistory(
            subjectCode:  'ITP102',
            presentTime:  '07:50:00',
            lateTime:     '08:09:00',
            sessionStart: '08:00:00',
            sessionEnd:   '11:00:00',
            pattern: [
                '2026-07-08' => ['P', 'P'],
                '2026-07-15' => ['P', 'L'],
                '2026-07-22' => ['A', 'P'],
                '2026-07-29' => ['P', 'P'],
                '2026-08-05' => ['L', 'P'],
                '2026-08-12' => ['P', 'P'],
            ]
        );

        // ==========================================================
        // CS201 - Monday 1:00 PM - 4:00 PM (2 students)
        // ==========================================================
        $this->seedHistory(
            subjectCode:  'CS201',
            presentTime:  '12:58:00',
            lateTime:     '13:22:00',
            sessionStart: '13:00:00',
            sessionEnd:   '16:00:00',
            pattern: [
                '2026-07-06' => ['P', 'P'],
                '2026-07-13' => ['L', 'P'],
                '2026-07-20' => ['P', 'A'],
                '2026-07-27' => ['P', 'P'],
                '2026-08-03' => ['P', 'L'],
                '2026-08-10' => ['P', 'P'],
            ]
        );

        // ==========================================================
        // ONE LIVE SESSION FOR TODAY
        // ----------------------------------------------------------
        // Every other date above is fixed so results are reproducible.
        // This one block uses today's date on purpose, so the
        // dashboards have non-zero "Today" counts the moment you log
        // in, and so the QR page has a live session to open.
        //
        // 4 of the 8 students are already checked in. The demo
        // student (2026-00001) is intentionally NOT checked in, so
        // you can demonstrate a real QR scan.
        // ==========================================================
        $this->seedTodaySession();
    }

    private function seedHistory(
        string $subjectCode,
        string $presentTime,
        string $lateTime,
        string $sessionStart,
        string $sessionEnd,
        array $pattern
    ): void {
        $class = ClassModel::whereHas(
            'subject',
            fn (Builder $q) => $q->where('subject_code', $subjectCode)
        )->first();

        if (! $class) {
            return;
        }

        $studentIds  = $this->enrolledStudentIds($class->id);
        $recordedBy  = $class->instructor?->user_id;

        foreach ($pattern as $date => $codes) {

            $session = AttendanceSession::updateOrCreate(
                [
                    'class_id'     => $class->id,
                    'session_date' => $date,
                ],
                [
                    'qr_token'   => null,          // closed sessions carry no token
                    'start_time' => $sessionStart,
                    'end_time'   => $sessionEnd,
                    'status'     => 'Closed',
                ]
            );

            foreach ($codes as $index => $code) {
                if (! isset($studentIds[$index])) {
                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'class_id'        => $class->id,
                        'student_id'      => $studentIds[$index],
                        'attendance_date' => $date,
                    ],
                    [
                        'attendance_session_id' => $session->id,
                        'time_in'  => match ($code) {
                            'P'     => $presentTime,
                            'L'     => $lateTime,
                            default => null,
                        },
                        'time_out' => null,
                        'status'   => match ($code) {
                            'P'     => 'Present',
                            'L'     => 'Late',
                            default => 'Absent',
                        },
                        'remarks' => match ($code) {
                            'L'     => 'Arrived after class start time.',
                            'A'     => 'No check-in recorded.',
                            default => null,
                        },
                        'recorded_by' => $recordedBy,
                    ]
                );
            }
        }
    }

    private function seedTodaySession(): void
    {
        $class = ClassModel::whereHas(
            'subject',
            fn (Builder $q) => $q->where('subject_code', 'ITP104')
        )->first();

        if (! $class) {
            return;
        }

        $today      = now()->toDateString();
        $studentIds = $this->enrolledStudentIds($class->id);
        $recordedBy = $class->instructor?->user_id;

        $session = AttendanceSession::updateOrCreate(
            [
                'class_id'     => $class->id,
                'session_date' => $today,
            ],
            [
                'qr_token'   => Str::random(config('attendance.qr_token_length', 40)),
                'start_time' => '08:00:00',
                'end_time'   => null,
                'status'     => 'Open',
            ]
        );

        // Index 0 = 2026-00001 (demo student) -> deliberately skipped
        $checkedIn = [
            1 => ['status' => 'Present', 'time_in' => '07:54:00'],
            2 => ['status' => 'Present', 'time_in' => '07:57:00'],
            3 => ['status' => 'Late',    'time_in' => '08:06:00'],
            4 => ['status' => 'Present', 'time_in' => '07:59:00'],
        ];

        foreach ($checkedIn as $index => $record) {
            if (! isset($studentIds[$index])) {
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'class_id'        => $class->id,
                    'student_id'      => $studentIds[$index],
                    'attendance_date' => $today,
                ],
                [
                    'attendance_session_id' => $session->id,
                    'time_in'     => $record['time_in'],
                    'time_out'    => null,
                    'status'      => $record['status'],
                    'remarks'     => $record['status'] === 'Late'
                                        ? 'Arrived after class start time.'
                                        : null,
                    'recorded_by' => $recordedBy,
                ]
            );
        }
    }

    /**
     * Enrolled student IDs in a stable order (by student number),
     * so the pattern arrays always line up with the same students.
     */
    private function enrolledStudentIds(int $classId): array
    {
        return Enrollment::where('class_id', $classId)
            ->where('enrollment_status', 'Enrolled')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->orderBy('students.student_number')
            ->pluck('students.id')
            ->all();
    }
}

<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class AttendanceService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Open a session for a class on a given date, or return the existing one.
    public function openSession(ClassModel $class, ?string $date = null): AttendanceSession
    {
        $date = $date ?? now()->toDateString();

        $session = AttendanceSession::firstOrNew([
            'class_id'     => $class->id,
            'session_date' => $date,
        ]);

        $session->start_time = $class->start_time;
        $session->end_time   = null;
        $session->status     = 'Open';

        // A fresh random token every time a session is opened.
        // Old QR codes stop working immediately.
        $session->qr_token = $this->generateToken();

        $session->save();

        return $session;
    }

    /**
     * Close a session: kill the QR token and mark every enrolled student
     * who never checked in as Absent.
     */
    public function closeSession(AttendanceSession $session): array
    {
        $absentCreated = 0;

        DB::transaction(function () use ($session, &$absentCreated) {

            if (config('attendance.auto_absent_on_close', true)) {
                $absentCreated = $this->markAbsentees($session);
            }

            $session->update([
                'status'   => 'Closed',
                'end_time' => now()->format('H:i:s'),
                'qr_token' => null,   // the QR code is now dead
            ]);
        });

        return [
            'absent_created' => $absentCreated,
            'present'        => $session->presentCount(),
            'late'           => $session->lateCount(),
            'absent'         => $session->absentCount(),
        ];
    }

    /**
     * Create Absent records for enrolled students with no record yet.
     * Never creates duplicates — the UNIQUE key on
     * (class_id, student_id, attendance_date) is the final guard.
     */
    public function markAbsentees(AttendanceSession $session): int
    {
        $date = $session->session_date->toDateString();

        // Students already accounted for on this date
        $recorded = Attendance::where('class_id', $session->class_id)
            ->whereDate('attendance_date', $date)
            ->pluck('student_id')
            ->all();

        // Actively enrolled students who are NOT in that list
        $missing = Enrollment::where('class_id', $session->class_id)
            ->where('enrollment_status', 'Enrolled')
            ->when($recorded, fn ($q) => $q->whereNotIn('student_id', $recorded))
            ->pluck('student_id');

        $created = 0;

        foreach ($missing as $studentId) {
            $attendance = Attendance::firstOrCreate(
                [
                    'class_id'        => $session->class_id,
                    'student_id'      => $studentId,
                    'attendance_date' => $date,
                ],
                [
                    'attendance_session_id' => $session->id,
                    'time_in'               => null,
                    'status'                => 'Absent',
                    'remarks'               => 'No check-in recorded.',
                    'recorded_by'           => auth()->id(),
                ]
            );

            if ($attendance->wasRecentlyCreated) {
                $created++;
                $this->notifyStudent($attendance);
            }
        }

        return $created;
    }

    /**
     * Save one attendance record. Used by BOTH the manual form and the
     * QR scanner, so Present/Late can never disagree between them.
     */
    public function record(
        AttendanceSession $session,
        int $studentId,
        ?string $timeIn,
        ?string $status = null,
        ?string $remarks = null,
        ?int $recordedBy = null
    ): Attendance {
        $class = $session->classModel;
        $date  = $session->session_date->toDateString();

        // When no status is given, work it out from the check-in time
        if ($status === null) {
            $status = $timeIn
                ? $class->determineStatus(Carbon::parse($date . ' ' . $timeIn))
                : 'Absent';
        }

        // Absent records never carry a time
        if ($status === 'Absent') {
            $timeIn = null;
        }

        $attendance = Attendance::updateOrCreate(
            [
                'class_id'        => $class->id,
                'student_id'      => $studentId,
                'attendance_date' => $date,
            ],
            [
                'attendance_session_id' => $session->id,
                'time_in'               => $timeIn,
                'status'                => $status,
                'remarks'               => $remarks,
                'recorded_by'           => $recordedBy,
            ]
        );

          $this->notifyStudent($attendance);

        // An absence may have pushed this student below the minimum
        if ($attendance->status === 'Absent') {
            $student = $attendance->student ?? Student::find($attendance->student_id);

            if ($student) {
                $this->notifications->checkLowAttendance($student, $class);
            }
        }

        return $attendance;
    }

  

    /**
     * Tell the student what was recorded.
     */
    public function notifyStudent(Attendance $attendance): void
    {
        $student = $attendance->student ?? Student::find($attendance->student_id);

        if (! $student || ! $student->user_id) {
            return;
        }

        $class   = $attendance->classModel ?? ClassModel::with('subject')->find($attendance->class_id);
        $subject = $class?->subject?->subject_code ?? 'your class';
        $date    = Carbon::parse($attendance->attendance_date)->format('F j, Y');

        [$title, $message, $type] = match ($attendance->status) {
            'Present' => [
                'Attendance Recorded',
                'You were marked PRESENT in ' . $subject . ' on ' . $date
                    . ' at ' . Carbon::parse($attendance->time_in)->format('g:i A') . '.',
                'success',
            ],
            'Late' => [
                'Late Attendance',
                'You were marked LATE in ' . $subject . ' on ' . $date
                    . ' at ' . Carbon::parse($attendance->time_in)->format('g:i A') . '.',
                'warning',
            ],
            default => [
                'Absence Recorded',
                'You were marked ABSENT in ' . $subject . ' on ' . $date . '.',
                'danger',
            ],
        };

        Notification::send($student->user_id, $title, $message, $type);
    }

    /**
     * Secure random token for the QR code. Contains no personal data.
     */
    public function generateToken(): string
    {
        $length = (int) config('attendance.qr_token_length', 40);

        do {
            $token = Str::random($length);
        } while (AttendanceSession::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Everything that can go wrong with a QR scan, in one place.
     * Returns ['ok' => bool, 'message' => string, 'attendance' => ?Attendance]
     */
    public function processScan(string $token, Student $student): array
    {
        // 1. Does the token match an existing session?
        $session = AttendanceSession::with('classModel.subject')
            ->where('qr_token', $token)
            ->first();

        if (! $session) {
            return $this->scanFail('Invalid or expired QR code. Ask your instructor for the current code.');
        }

        // 2. Is the session still accepting check-ins?
        if (! $session->isOpen()) {
            return $this->scanFail('This attendance session has already been closed.');
        }

        $class = $session->classModel;

        // 3. Is this student actually enrolled in the class?
        $enrolled = Enrollment::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('enrollment_status', 'Enrolled')
            ->exists();

        if (! $enrolled) {
            return $this->scanFail('You are not enrolled in ' . $class->subject->subject_code . '.');
        }

        // 4. Has this student already checked in today?
        $existing = Attendance::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $session->session_date->toDateString())
            ->first();

        if ($existing && in_array($existing->status, ['Present', 'Late'], true)) {
            return $this->scanFail(
                'You already checked in at '
                . Carbon::parse($existing->time_in)->format('g:i A') . '.'
            );
        }

        // 5. All checks passed — record the check-in
        $now    = now();
        $timeIn = $now->format('H:i:s');
        $status = $class->determineStatus($now);

        $attendance = $this->record(
            session:    $session,
            studentId:  $student->id,
            timeIn:     $timeIn,
            status:     $status,
            remarks:    $status === 'Late' ? 'Arrived after class start time.' : null,
            recordedBy: null   // NULL means the student scanned it themselves
        );

        $this->notifyInstructor($session, $student, $status);

        return [
            'ok'         => true,
            'status'     => $status,
            'time_in'    => $now->format('g:i A'),
            'subject'    => $class->subject->subject_code,
            'section'    => $class->section,
            'message'    => 'You were marked ' . strtoupper($status) . ' in '
                            . $class->subject->subject_code . ' at ' . $now->format('g:i A') . '.',
            'attendance' => $attendance,
        ];
    }

    /**
     * Tell the instructor that a student just scanned in.
     */
    private function notifyInstructor(AttendanceSession $session, Student $student, string $status): void
    {
        $instructorUserId = $session->classModel->instructor?->user_id;

        if (! $instructorUserId) {
            return;
        }

        Notification::send(
            $instructorUserId,
            'Student Checked In',
            $student->full_name . ' scanned in for '
                . $session->classModel->class_title . ' and was marked ' . $status . '.',
            $status === 'Late' ? 'warning' : 'success'
        );
    }

    private function scanFail(string $message): array
    {
        return [
            'ok'         => false,
            'message'    => $message,
            'attendance' => null,
        ];
    }
}

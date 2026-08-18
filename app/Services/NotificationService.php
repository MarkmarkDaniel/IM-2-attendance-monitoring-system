<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send the same message to every admin.
     */
    public function notifyAdmins(string $title, string $message, string $type = 'info'): int
    {
        $admins = User::where('role', User::ROLE_ADMIN)->pluck('id');

        foreach ($admins as $adminId) {
            Notification::send($adminId, $title, $message, $type);
        }

        return $admins->count();
    }

    /**
     * Check one student's rate in one class and warn if it fell below
     * the minimum. Returns TRUE when a warning was actually created.
     */
    public function checkLowAttendance(Student $student, ClassModel $class): bool
    {
        $minimum = (int) config('attendance.minimum_rate', 75);

        $row = Attendance::where('student_id', $student->id)
            ->where('class_id', $class->id)
            ->selectRaw("
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count
            ")
            ->first();

        $total = (int) ($row->total_count ?? 0);

        // Too few sessions to judge yet — warning would be noise
        if ($total < 3) {
            return false;
        }

        $rate = Attendance::calculateRate(
            (int) $row->present_count,
            (int) $row->late_count,
            $total
        );

        if ($rate >= $minimum) {
            return false;
        }

        $subject = $class->subject->subject_code;

        // Don't repeat the same warning within the cooldown window
        if ($this->recentlyWarned($student->user_id, $subject)) {
            return false;
        }

        // ---------------- Warn the student ----------------
        Notification::send(
            $student->user_id,
            'Low Attendance Warning',
            'Your attendance in ' . $subject . ' is now ' . $rate . '%, which is below the '
                . $minimum . '% minimum. You have ' . $row->present_count . ' present, '
                . $row->late_count . ' late out of ' . $total . ' sessions.',
            'danger'
        );

        // ---------------- Warn the instructor ----------------
        $instructorUserId = $class->instructor?->user_id;

        if ($instructorUserId) {
            Notification::send(
                $instructorUserId,
                'Student Below Minimum Attendance',
                $student->full_name . ' (' . $student->student_number . ') has fallen to '
                    . $rate . '% in ' . $subject . ' — ' . $class->section . '.',
                'warning'
            );
        }

        return true;
    }

    /**
     * Run the check for every actively enrolled student in a class.
     */
    public function checkClass(ClassModel $class): int
    {
        $warned = 0;

        $students = Student::whereIn(
            'id',
            $class->activeEnrollments()->pluck('student_id')
        )->get();

        foreach ($students as $student) {
            if ($this->checkLowAttendance($student, $class)) {
                $warned++;
            }
        }

        return $warned;
    }

    /**
     * Has this user already been warned about this subject recently?
     * Stops the same warning firing on every single scan.
     */
    private function recentlyWarned(?int $userId, string $subjectCode): bool
    {
        if (! $userId) {
            return true; // no account to notify
        }

        $cooldownDays = (int) config('attendance.warning_cooldown_days', 7);

        return Notification::where('user_id', $userId)
            ->where('title', 'Low Attendance Warning')
            ->where('message', 'like', '%' . $subjectCode . '%')
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->exists();
    }

    /**
     * Students below the minimum, used by the dashboards.
     */
    public function lowAttendanceStudents(?int $instructorId = null): Collection
    {
        $minimum = (int) config('attendance.minimum_rate', 75);

        $query = Attendance::query()
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->join('classes', 'classes.id', '=', 'attendances.class_id')
            ->join('subjects', 'subjects.id', '=', 'classes.subject_id')
            ->selectRaw("
                students.id AS student_id,
                students.student_number,
                CONCAT(students.last_name, ', ', students.first_name) AS student_name,
                subjects.subject_code,
                classes.section,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status IN ('Present','Late') THEN 1 ELSE 0 END) AS attended_count
            ")
            ->groupBy(
                'students.id', 'students.student_number',
                'students.last_name', 'students.first_name',
                'subjects.subject_code', 'classes.section'
            )
            ->havingRaw('COUNT(*) >= 3');

        if ($instructorId !== null) {
            $query->where('classes.instructor_id', $instructorId);
        }

        return $query->get()
            ->map(function ($row) {
                $row->attendance_rate = $row->total_count > 0
                    ? round(($row->attended_count / $row->total_count) * 100, 2)
                    : 0;

                return $row;
            })
            ->filter(fn ($row) => $row->attendance_rate < $minimum)
            ->sortBy('attendance_rate')
            ->values();
    }
}

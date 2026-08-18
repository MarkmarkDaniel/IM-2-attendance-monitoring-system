<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorClassController extends Controller
{
    public function index(Request $request): View
    {
        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        $classes = ClassModel::with('subject')
            ->where('instructor_id', $instructor->id)
            ->withCount(['activeEnrollments', 'attendanceSessions'])
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get();

        return view('instructor.classes.index', compact('classes'));
    }

    public function show(Request $request, ClassModel $class): View
    {
        // An instructor may only open their OWN class
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $class->load(['subject', 'instructor']);

        // Roster with each student's attendance rate in THIS class
        $enrollments = Enrollment::with('student.department')
            ->where('class_id', $class->id)
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->orderBy('students.last_name')
            ->select('enrollments.*')
            ->get();

        $stats = Attendance::where('class_id', $class->id)
            ->selectRaw("
                student_id,
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $sessions = AttendanceSession::where('class_id', $class->id)
            ->withCount([
                'attendances as present_total' => fn ($q) => $q->where('status', 'Present'),
                'attendances as late_total'    => fn ($q) => $q->where('status', 'Late'),
                'attendances as absent_total'  => fn ($q) => $q->where('status', 'Absent'),
            ])
            ->orderBy('session_date', 'desc')
            ->limit(15)
            ->get();

        return view('instructor.classes.show', compact('class', 'enrollments', 'stats', 'sessions'));
    }
}

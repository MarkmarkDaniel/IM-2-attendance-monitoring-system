<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        $student->load('department');

        // Classes this student is actively enrolled in
        $classIds = Enrollment::where('student_id', $student->id)
            ->where('enrollment_status', 'Enrolled')
            ->pluck('class_id');

        // ---------------- Attendance summary ----------------
        $summaryRow = Attendance::where('student_id', $student->id)
            ->selectRaw("
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $summary = [
            'total'   => (int) ($summaryRow->total_count ?? 0),
            'present' => (int) ($summaryRow->present_count ?? 0),
            'late'    => (int) ($summaryRow->late_count ?? 0),
            'absent'  => (int) ($summaryRow->absent_count ?? 0),
        ];

        $summary['rate'] = Attendance::calculateRate(
            $summary['present'],
            $summary['late'],
            $summary['total']
        );

        // Individual percentages — all guarded against division by zero
        $summary['present_pct'] = $summary['total'] > 0
            ? round(($summary['present'] / $summary['total']) * 100, 2) : 0;
        $summary['late_pct'] = $summary['total'] > 0
            ? round(($summary['late'] / $summary['total']) * 100, 2) : 0;
        $summary['absent_pct'] = $summary['total'] > 0
            ? round(($summary['absent'] / $summary['total']) * 100, 2) : 0;

        // ---------------- Chart: rate per subject ----------------
        $perClass = Attendance::where('student_id', $student->id)
            ->selectRaw("
                class_id,
                COUNT(*) AS total_count,
                SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) AS attended_count
            ")
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        $classes = ClassModel::with('subject')
            ->whereIn('id', $classIds)
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get();

        $subjectChart = ['labels' => [], 'rates' => []];

        foreach ($classes as $class) {
            $row   = $perClass->get($class->id);
            $total = (int) ($row->total_count ?? 0);

            $subjectChart['labels'][] = $class->subject->subject_code;
            $subjectChart['rates'][]  = $total > 0
                ? round(((int) $row->attended_count / $total) * 100, 2)
                : 0;
        }

        // ---------------- Open sessions the student can scan ----------------
        $openSessions = AttendanceSession::with('classModel.subject')
            ->whereIn('class_id', $classIds)
            ->where('status', 'Open')
            ->whereDate('session_date', now()->toDateString())
            ->get()
            ->filter(function ($session) use ($student) {
                // Hide the ones already checked into
                return ! Attendance::where('class_id', $session->class_id)
                    ->where('student_id', $student->id)
                    ->whereDate('attendance_date', $session->session_date->toDateString())
                    ->whereIn('status', ['Present', 'Late'])
                    ->exists();
            });

        // ---------------- Recent records ----------------
        $recentAttendance = Attendance::with('classModel.subject')
            ->where('student_id', $student->id)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('student.dashboard', compact(
            'student', 'summary', 'subjectChart', 'classes', 'openSessions', 'recentAttendance'
        ));
    }
}

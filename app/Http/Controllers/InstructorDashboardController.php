<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        $today   = now()->toDateString();
        $dayName = now()->format('l');

        // Every class this instructor teaches
        $classes = ClassModel::with('subject')
            ->where('instructor_id', $instructor->id)
            ->withCount('activeEnrollments')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get();

        $classIds = $classes->pluck('id');

        // Classes scheduled for today
        $todayClasses = $classes->where('schedule_day', $dayName);

        // Which of today's classes already have a session
        $todaySessions = AttendanceSession::whereIn('class_id', $classIds)
            ->whereDate('session_date', $today)
            ->get()
            ->keyBy('class_id');

        // ---------------- Today's numbers ----------------
        $todayRow = Attendance::whereIn('class_id', $classIds)
            ->whereDate('attendance_date', $today)
            ->selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $todayStats = [
            'present' => (int) ($todayRow->present_count ?? 0),
            'late'    => (int) ($todayRow->late_count ?? 0),
            'absent'  => (int) ($todayRow->absent_count ?? 0),
        ];

        // ---------------- Overall numbers ----------------
        $overallRow = Attendance::whereIn('class_id', $classIds)
            ->selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $overview = [
            'present' => (int) ($overallRow->present_count ?? 0),
            'late'    => (int) ($overallRow->late_count ?? 0),
            'absent'  => (int) ($overallRow->absent_count ?? 0),
        ];

        $overviewTotal = array_sum($overview);

        $attendanceRate = Attendance::calculateRate(
            $overview['present'],
            $overview['late'],
            $overviewTotal
        );

        // ---------------- Chart: rate per class ----------------
        $perClass = Attendance::whereIn('class_id', $classIds)
            ->selectRaw("
                class_id,
                COUNT(*) AS total_count,
                SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) AS attended_count
            ")
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        $classChart = ['labels' => [], 'rates' => []];

        foreach ($classes as $class) {
            $row   = $perClass->get($class->id);
            $total = (int) ($row->total_count ?? 0);

            $classChart['labels'][] = $class->subject->subject_code . ' ' . $class->section;
            $classChart['rates'][]  = $total > 0
                ? round(((int) $row->attended_count / $total) * 100, 2)
                : 0;
        }

        // ---------------- Recent attendance ----------------
        $recentAttendance = Attendance::with(['student', 'classModel.subject'])
            ->whereIn('class_id', $classIds)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('instructor.dashboard', compact(
            'instructor',
            'classes',
            'todayClasses',
            'todaySessions',
            'todayStats',
            'overview',
            'overviewTotal',
            'attendanceRate',
            'classChart',
            'recentAttendance',
            'dayName'
        ));
    }
}

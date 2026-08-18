<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\NotificationService;

class AdminDashboardController extends Controller
{
        public function index(NotificationService $notifications): View
    {
        $today = now()->toDateString();

        // ---------------- Count cards ----------------
        $counts = [
            'students'    => Student::count(),
            'instructors' => Instructor::count(),
            'departments' => Department::count(),
            'subjects'    => Subject::count(),
            'classes'     => ClassModel::count(),
        ];

        // ---------------- Today's attendance ----------------
        $todayRows = Attendance::whereDate('attendance_date', $today)
            ->selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $todayStats = [
            'present' => (int) ($todayRows->present_count ?? 0),
            'late'    => (int) ($todayRows->late_count ?? 0),
            'absent'  => (int) ($todayRows->absent_count ?? 0),
        ];

        // ---------------- Chart 1: overall attendance overview ----------------
        $overallRows = Attendance::selectRaw("
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $overview = [
            'present' => (int) ($overallRows->present_count ?? 0),
            'late'    => (int) ($overallRows->late_count ?? 0),
            'absent'  => (int) ($overallRows->absent_count ?? 0),
        ];

        $overviewTotal = array_sum($overview);

        // ---------------- Chart 2: attendance trend ----------------
        // Uses the last 7 dates that actually have records, so the chart is
        // never blank just because there were no classes this week.
        $trend = Attendance::selectRaw("
                attendance_date,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy('attendance_date')
            ->orderBy('attendance_date', 'desc')
            ->limit(7)
            ->get()
            ->sortBy('attendance_date')
            ->values();

        $trendChart = [
            'labels'  => $trend->map(fn ($r) => \Carbon\Carbon::parse($r->attendance_date)->format('M j'))->all(),
            'present' => $trend->map(fn ($r) => (int) $r->present_count)->all(),
            'late'    => $trend->map(fn ($r) => (int) $r->late_count)->all(),
            'absent'  => $trend->map(fn ($r) => (int) $r->absent_count)->all(),
        ];

        // ---------------- Chart 3: attendance rate per department ----------------
        $deptRows = DB::table('attendances')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->join('departments', 'departments.id', '=', 'students.department_id')
            ->selectRaw("
                departments.department_code,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status IN ('Present','Late') THEN 1 ELSE 0 END) AS attended_count
            ")
            ->groupBy('departments.id', 'departments.department_code')
            ->orderBy('departments.department_code')
            ->get();

        $deptChart = [
            'labels' => $deptRows->pluck('department_code')->all(),
            'rates'  => $deptRows->map(function ($row) {
                // Guard against division by zero
                return $row->total_count > 0
                    ? round(($row->attended_count / $row->total_count) * 100, 2)
                    : 0;
            })->all(),
        ];

        // ---------------- Recent activity ----------------
        $recentAttendance = Attendance::with(['student', 'classModel.subject'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        $recentNotifications = auth()->user()->notifications()->limit(5)->get();

        // ---------------- Overall attendance rate ----------------
        $overallRate = Attendance::calculateRate(
            $overview['present'],
            $overview['late'],
            $overviewTotal
        );
                // ---------------- Students below the minimum rate ----------------
        $lowAttendance = $notifications->lowAttendanceStudents(null)->take(8);

                return view('admin.dashboard', compact(
            'counts',
            'todayStats',
            'overview',
            'overviewTotal',
            'overallRate',
            'trendChart',
            'deptChart',
            'recentAttendance',
            'recentNotifications',
            'lowAttendance'
        ));
    }
}

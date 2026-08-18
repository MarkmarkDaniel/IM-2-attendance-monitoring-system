<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    /**
     * Manual attendance sheet for one class on one date.
     */
    public function record(Request $request, ClassModel $class): View
    {
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $class->load('subject');

        $date = $request->query('date', now()->toDateString());

        $session = AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', $date)
            ->first();

        // Actively enrolled students, ordered by surname
        $enrollments = Enrollment::with('student')
            ->where('class_id', $class->id)
            ->where('enrollment_status', 'Enrolled')
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->orderBy('students.last_name')
            ->select('enrollments.*')
            ->get();

        // Existing records for that date, keyed by student
        $existing = Attendance::where('class_id', $class->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        return view('instructor.attendance.record', compact(
            'class', 'session', 'date', 'enrollments', 'existing'
        ));
    }

    /**
     * Save the whole attendance sheet in one submit.
     */
    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        abort_unless($class->belongsToInstructor($request->user()), 403,
            'You are not assigned to this class.');

        $validated = $request->validate([
            'session_id'            => ['required', 'integer', 'exists:attendance_sessions,id'],
            'records'               => ['required', 'array', 'min:1'],
            'records.*.student_id'  => ['required', 'integer', 'exists:students,id'],
            'records.*.status'      => ['required', Rule::in(['Present', 'Late', 'Absent'])],
            'records.*.time_in'     => ['nullable', 'date_format:H:i,H:i:s'],
            'records.*.remarks'     => ['nullable', 'string', 'max:255'],
        ], [
            'records.required' => 'There are no enrolled students to record.',
        ]);

        $session = AttendanceSession::findOrFail($validated['session_id']);

        // The session must belong to THIS class — never trust the form's ID
        abort_unless($session->class_id === $class->id, 403, 'Session mismatch.');

        // Only students actually enrolled in this class may be recorded
        $enrolledIds = Enrollment::where('class_id', $class->id)
            ->where('enrollment_status', 'Enrolled')
            ->pluck('student_id')
            ->all();

        $saved = 0;

        DB::transaction(function () use ($validated, $session, $enrolledIds, $request, &$saved) {
            foreach ($validated['records'] as $row) {

                if (! in_array((int) $row['student_id'], $enrolledIds, true)) {
                    continue; // silently skip anything tampered with
                }

                $this->attendance->record(
                    session:    $session,
                    studentId:  (int) $row['student_id'],
                    timeIn:     $row['time_in'] ?? null,
                    status:     $row['status'],
                    remarks:    $row['remarks'] ?? null,
                    recordedBy: $request->user()->id
                );

                $saved++;
            }
        });

        return redirect()->route('instructor.attendance.record', [
                'class' => $class->id,
                'date'  => $session->session_date->toDateString(),
            ])
            ->with('success', $saved . ' attendance record(s) saved.');
    }

    /**
     * Daily attendance monitoring across all of the instructor's classes.
     */
    public function daily(Request $request): View
    {
        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        $classes = ClassModel::with('subject')
            ->where('instructor_id', $instructor->id)
            ->orderBy('schedule_day')
            ->get();

        $classIds = $classes->pluck('id');

        $date    = $request->query('date', now()->toDateString());
        $classId = $request->query('class_id');
        $status  = $request->query('status');

        $records = Attendance::with(['student.department', 'classModel.subject'])
            ->whereIn('class_id', $classIds)
            ->whereDate('attendance_date', $date)
            ->when($classId, fn ($q, $v) => $q->where('class_id', $v))
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->orderBy('students.last_name')
            ->select('attendances.*')
            ->get();

        // Summary ignores the status filter so the totals stay meaningful
        $summaryQuery = Attendance::whereIn('class_id', $classIds)
            ->whereDate('attendance_date', $date)
            ->when($classId, fn ($q, $v) => $q->where('class_id', $v));

        $summaryRow = (clone $summaryQuery)->selectRaw("
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")->first();

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

        return view('instructor.attendance.daily', compact(
            'classes', 'records', 'summary', 'date', 'classId', 'status'
        ));
    }
}

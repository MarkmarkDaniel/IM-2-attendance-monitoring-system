<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Subject;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    /**
     * Report picker.
     */
    public function index(Request $request): View
    {
        return view('reports.index', [
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    /* ==================================================================
     | 1. DAILY ATTENDANCE REPORT
     ================================================================== */

    public function daily(Request $request): View
    {
        $filters = [
            'date'       => $request->query('date', now()->toDateString()),
            'class_id'   => $request->query('class_id'),
            'subject_id' => $request->query('subject_id'),
            'status'     => $request->query('status'),
        ];

        $instructorId = $this->instructorScope($request);

        $records = $this->reports->baseQuery($filters, $instructorId)
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->orderBy('students.last_name')
            ->select('attendances.*')
            ->get();

        // Summary ignores the status filter so the totals stay meaningful
        $summaryFilters = $filters;
        unset($summaryFilters['status']);

        $summary = $this->reports->summarize(
            $this->reports->baseQuery($summaryFilters, $instructorId)
        );

        return view('reports.daily', array_merge(
            $this->filterOptions($request),
            compact('records', 'summary', 'filters')
        ));
    }

    /* ==================================================================
     | 2. STUDENT ATTENDANCE REPORT
     ================================================================== */

    public function student(Request $request): View
    {
        $filters = [
            'student_id' => $request->query('student_id'),
            'subject_id' => $request->query('subject_id'),
            'date_from'  => $request->query('date_from'),
            'date_to'    => $request->query('date_to'),
        ];

        $instructorId = $this->instructorScope($request);

        $student = $filters['student_id']
            ? Student::with('department')->find($filters['student_id'])
            : null;

        $records  = collect();
        $summary  = null;
        $perClass = collect();

        if ($student) {
            $records = $this->reports->baseQuery($filters, $instructorId)
                ->orderBy('attendance_date', 'desc')
                ->get();

            $summary = $this->reports->summarize(
                $this->reports->baseQuery($filters, $instructorId)
            );

            $perClass = $this->reports->perClass($filters, $instructorId);
        }

        return view('reports.student', array_merge(
            $this->filterOptions($request),
            compact('records', 'summary', 'perClass', 'filters', 'student')
        ));
    }

    /* ==================================================================
     | Shared helpers
     ================================================================== */

    /**
     * NULL for admins (see everything), the instructor's own ID otherwise.
     * This is what stops an instructor from reporting on another
     * instructor's classes.
     */
    private function instructorScope(Request $request): ?int
    {
        if ($request->user()->isAdmin()) {
            return null;
        }

        $instructor = $request->user()->instructor;

        abort_if(! $instructor, 403, 'No instructor profile is linked to this account.');

        return $instructor->id;
    }

    /**
     * Dropdown data. Instructors only see their own classes and students.
     */
    private function filterOptions(Request $request): array
    {
        $isAdmin      = $request->user()->isAdmin();
        $instructorId = $isAdmin ? null : $request->user()->instructor?->id;

        $classQuery = ClassModel::with(['subject', 'instructor'])
            ->when($instructorId, fn ($q, $v) => $q->where('instructor_id', $v))
            ->orderBy('schedule_day')
            ->orderBy('start_time');

        $classes  = $classQuery->get();
        $classIds = $classes->pluck('id');

        // Students visible to this user
        $studentQuery = Student::with('department')->orderBy('last_name');

        if (! $isAdmin) {
            $studentIds = \App\Models\Enrollment::whereIn('class_id', $classIds)
                ->pluck('student_id')
                ->unique();

            $studentQuery->whereIn('id', $studentIds);
        }

        return [
            'classes'     => $classes,
            'students'    => $studentQuery->get(),
            'subjects'    => Subject::orderBy('subject_code')->get(),
            'departments' => Department::orderBy('department_code')->get(),
            'instructors' => Instructor::orderBy('last_name')->get(),
            'isAdmin'     => $isAdmin,
            'routePrefix' => $isAdmin ? 'admin' : 'instructor',
        ];
    }


        /* ==================================================================
     | 3. CLASS ATTENDANCE REPORT
     ================================================================== */

    public function class(Request $request): View
    {
        $filters = [
            'class_id'      => $request->query('class_id'),
            'subject_id'    => $request->query('subject_id'),
            'instructor_id' => $request->query('instructor_id'),
            'date_from'     => $request->query('date_from'),
            'date_to'       => $request->query('date_to'),
        ];

        $instructorId = $this->instructorScope($request);

        $rows = $this->reports->perClass($filters, $instructorId);

        $summary = $this->reports->summarize(
            $this->reports->baseQuery($filters, $instructorId)
        );

        // Per-student breakdown when a single class is selected
        $perStudent = $filters['class_id']
            ? $this->reports->perStudent($filters, $instructorId)
            : collect();

        $selectedClass = $filters['class_id']
            ? ClassModel::with(['subject', 'instructor'])->find($filters['class_id'])
            : null;

        return view('reports.class', array_merge(
            $this->filterOptions($request),
            compact('rows', 'summary', 'perStudent', 'filters', 'selectedClass')
        ));
    }

    /* ==================================================================
     | 4. DEPARTMENT ATTENDANCE REPORT (admin only)
     ================================================================== */

    public function department(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403,
            'Only administrators can view department reports.');

        $filters = [
            'department_id' => $request->query('department_id'),
            'date_from'     => $request->query('date_from'),
            'date_to'       => $request->query('date_to'),
        ];

        $rows = $this->reports->perDepartment($filters, null);

        $summary = $this->reports->summarize(
            $this->reports->baseQuery($filters, null)
        );

        return view('reports.department', array_merge(
            $this->filterOptions($request),
            compact('rows', 'summary', 'filters')
        ));
    }

    /* ==================================================================
     | 5. MONTHLY ATTENDANCE REPORT
     ================================================================== */

    public function monthly(Request $request): View
    {
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        // Guard against nonsense values typed into the URL
        $month = max(1, min(12, $month));
        $year  = max(2000, min(2100, $year));

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $filters = [
            'date_from'     => $start->toDateString(),
            'date_to'       => $end->toDateString(),
            'class_id'      => $request->query('class_id'),
            'department_id' => $request->query('department_id'),
        ];

        $instructorId = $this->instructorScope($request);

        $rows = $this->reports->perDate($filters, $instructorId);

        $summary = $this->reports->summarize(
            $this->reports->baseQuery($filters, $instructorId)
        );

        $perClass = $this->reports->perClass($filters, $instructorId);

        // Chart data — one point per date that has records
        $chart = [
            'labels'  => $rows->map(fn ($r) => \Carbon\Carbon::parse($r->attendance_date)->format('M j'))->all(),
            'present' => $rows->map(fn ($r) => (int) $r->present_count)->all(),
            'late'    => $rows->map(fn ($r) => (int) $r->late_count)->all(),
            'absent'  => $rows->map(fn ($r) => (int) $r->absent_count)->all(),
            'rate'    => $rows->map(fn ($r) => (float) $r->attendance_rate)->all(),
        ];

        return view('reports.monthly', array_merge(
            $this->filterOptions($request),
            compact('rows', 'summary', 'perClass', 'chart', 'filters', 'month', 'year')
        ));
    }
}

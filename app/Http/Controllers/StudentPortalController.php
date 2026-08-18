<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    /**
     * My Subjects — every class the student is enrolled in,
     * with their own attendance rate in each.
     */
    public function subjects(Request $request): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        $enrollments = Enrollment::with(['classModel.subject', 'classModel.instructor'])
            ->where('student_id', $student->id)
            ->get();

        $stats = Attendance::where('student_id', $student->id)
            ->selectRaw("
                class_id,
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy('class_id')
            ->get()
            ->keyBy('class_id');

        return view('student.subjects', compact('student', 'enrollments', 'stats'));
    }

    /**
     * My Attendance — the student's own records, with filters.
     */
    public function attendance(Request $request): View
    {
        $student = $request->user()->student;

        abort_if(! $student, 403, 'No student profile is linked to this account.');

        $filters = [
            'class_id'  => $request->query('class_id'),
            'status'    => $request->query('status'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
        ];

        // The student_id filter is hardcoded, never taken from the request.
        // A student can only ever see their own records.
        $records = Attendance::with('classModel.subject')
            ->where('student_id', $student->id)
            ->filter($filters)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Summary respects the filters except pagination
        $summaryRow = Attendance::where('student_id', $student->id)
            ->filter($filters)
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

        $classIds = Enrollment::where('student_id', $student->id)->pluck('class_id');

        $classes = ClassModel::with('subject')
            ->whereIn('id', $classIds)
            ->orderBy('schedule_day')
            ->get();

        return view('student.attendance', compact(
            'student', 'records', 'summary', 'classes', 'filters'
        ));
    }
}

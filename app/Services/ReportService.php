<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Restrict a query to one instructor's classes.
     * Admins pass NULL and see everything.
     */
    public function scopeToInstructor(Builder $query, ?int $instructorId): Builder
    {
        if ($instructorId === null) {
            return $query;
        }

        $classIds = ClassModel::where('instructor_id', $instructorId)->pluck('id');

        return $query->whereIn('class_id', $classIds);
    }

    /**
     * Base attendance query with every filter applied.
     */
    public function baseQuery(array $filters, ?int $instructorId = null): Builder
    {
        $query = Attendance::query()
            ->with(['student.department', 'classModel.subject', 'classModel.instructor'])
            ->filter($filters);

        return $this->scopeToInstructor($query, $instructorId);
    }

    /**
     * Present / Late / Absent counts plus the attendance rate.
     * Works on any attendance query.
     */
    public function summarize(Builder $query): array
    {
        $row = (clone $query)
            ->getQuery()   // drop eager loads so the aggregate stays cheap
            ->selectRaw("
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->first();

        $summary = [
            'total'   => (int) ($row->total_count ?? 0),
            'present' => (int) ($row->present_count ?? 0),
            'late'    => (int) ($row->late_count ?? 0),
            'absent'  => (int) ($row->absent_count ?? 0),
        ];

        $summary['rate'] = Attendance::calculateRate(
            $summary['present'],
            $summary['late'],
            $summary['total']
        );

        $summary['present_pct'] = $summary['total'] > 0
            ? round(($summary['present'] / $summary['total']) * 100, 2) : 0;
        $summary['late_pct'] = $summary['total'] > 0
            ? round(($summary['late'] / $summary['total']) * 100, 2) : 0;
        $summary['absent_pct'] = $summary['total'] > 0
            ? round(($summary['absent'] / $summary['total']) * 100, 2) : 0;

        return $summary;
    }

    /**
     * Per-student breakdown, used by the class and student reports.
     */
    public function perStudent(array $filters, ?int $instructorId = null): Collection
    {
        $query = DB::table('attendances')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->join('departments', 'departments.id', '=', 'students.department_id')
            ->selectRaw("
                students.id AS student_id,
                students.student_number,
                students.first_name,
                students.middle_name,
                students.last_name,
                students.year_level,
                students.section,
                departments.department_code,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN attendances.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN attendances.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy(
                'students.id', 'students.student_number',
                'students.first_name', 'students.middle_name', 'students.last_name',
                'students.year_level', 'students.section', 'departments.department_code'
            )
            ->orderBy('students.last_name');

        $this->applyRawFilters($query, $filters, $instructorId);

        return collect($query->get())->map(function ($row) {
            $row->attendance_rate = Attendance::calculateRate(
                (int) $row->present_count,
                (int) $row->late_count,
                (int) $row->total_count
            );

            $middle = $row->middle_name ? ' ' . $row->middle_name : '';
            $row->full_name   = $row->first_name . $middle . ' ' . $row->last_name;
            $row->formal_name = $row->last_name . ', ' . $row->first_name;

            return $row;
        });
    }

    /**
     * Per-department breakdown.
     */
    public function perDepartment(array $filters, ?int $instructorId = null): Collection
    {
        $query = DB::table('attendances')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->join('departments', 'departments.id', '=', 'students.department_id')
            ->selectRaw("
                departments.id AS department_id,
                departments.department_code,
                departments.department_name,
                COUNT(DISTINCT students.id) AS student_count,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN attendances.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN attendances.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy('departments.id', 'departments.department_code', 'departments.department_name')
            ->orderBy('departments.department_code');

        $this->applyRawFilters($query, $filters, $instructorId);

        return collect($query->get())->map(function ($row) {
            $row->attendance_rate = Attendance::calculateRate(
                (int) $row->present_count,
                (int) $row->late_count,
                (int) $row->total_count
            );

            return $row;
        });
    }

    /**
     * Per-class breakdown.
     */
    public function perClass(array $filters, ?int $instructorId = null): Collection
    {
        $query = DB::table('attendances')
            ->join('classes', 'classes.id', '=', 'attendances.class_id')
            ->join('subjects', 'subjects.id', '=', 'classes.subject_id')
            ->join('instructors', 'instructors.id', '=', 'classes.instructor_id')
            ->selectRaw("
                classes.id AS class_id,
                subjects.subject_code,
                subjects.subject_name,
                classes.section,
                classes.schedule_day,
                classes.start_time,
                CONCAT(instructors.first_name, ' ', instructors.last_name) AS instructor_name,
                COUNT(DISTINCT attendances.student_id) AS student_count,
                COUNT(DISTINCT attendances.attendance_date) AS session_count,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN attendances.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN attendances.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy(
                'classes.id', 'subjects.subject_code', 'subjects.subject_name',
                'classes.section', 'classes.schedule_day', 'classes.start_time',
                'instructors.first_name', 'instructors.last_name'
            )
            ->orderBy('subjects.subject_code');

        $this->applyRawFilters($query, $filters, $instructorId);

        return collect($query->get())->map(function ($row) {
            $row->attendance_rate = Attendance::calculateRate(
                (int) $row->present_count,
                (int) $row->late_count,
                (int) $row->total_count
            );

            return $row;
        });
    }

    /**
     * Day-by-day totals for a month, used by the monthly report chart.
     */
    public function perDate(array $filters, ?int $instructorId = null): Collection
    {
        $query = DB::table('attendances')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->selectRaw("
                attendances.attendance_date,
                COUNT(*) AS total_count,
                SUM(CASE WHEN attendances.status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN attendances.status = 'Late'    THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN attendances.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_count
            ")
            ->groupBy('attendances.attendance_date')
            ->orderBy('attendances.attendance_date');

        $this->applyRawFilters($query, $filters, $instructorId);

        return collect($query->get())->map(function ($row) {
            $row->attendance_rate = Attendance::calculateRate(
                (int) $row->present_count,
                (int) $row->late_count,
                (int) $row->total_count
            );

            return $row;
        });
    }

    /**
     * The same filters as Attendance::scopeFilter, but for query builder
     * joins where Eloquent scopes are not available.
     */
    private function applyRawFilters($query, array $filters, ?int $instructorId): void
    {
        if (! empty($filters['date'])) {
            $query->whereDate('attendances.attendance_date', $filters['date']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('attendances.attendance_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('attendances.attendance_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('attendances.class_id', $filters['class_id']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('attendances.student_id', $filters['student_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('attendances.status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('students.department_id', $filters['department_id']);
        }

        if (! empty($filters['subject_id'])) {
            $query->whereIn(
                'attendances.class_id',
                ClassModel::where('subject_id', $filters['subject_id'])->pluck('id')
            );
        }

        if (! empty($filters['instructor_id'])) {
            $query->whereIn(
                'attendances.class_id',
                ClassModel::where('instructor_id', $filters['instructor_id'])->pluck('id')
            );
        }

        // Instructors are always restricted to their own classes
        if ($instructorId !== null) {
            $query->whereIn(
                'attendances.class_id',
                ClassModel::where('instructor_id', $instructorId)->pluck('id')
            );
        }
    }
}

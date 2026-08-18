@extends('layouts.app')

@section('title', 'Class Details')
@section('page-title', $class->subject->subject_code . ' — Section ' . $class->section)
@section('page-subtitle', $class->subject->subject_name)

@section('page-actions')
    <a href="{{ route('instructor.classes.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <a href="{{ route('instructor.attendance.record', $class) }}" class="btn btn-brand btn-sm">
        <i class="bi bi-clipboard-check me-1"></i>Record Attendance
    </a>
@endsection

@section('content')

{{-- ==================== CLASS INFO ==================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted">Schedule</div>
                <div class="fw-medium">{{ $class->schedule_text }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted">Room</div>
                <div class="fw-medium">{{ $class->room ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Term</div>
                <div class="fw-medium">{{ $class->semester }}, {{ $class->school_year }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted">Enrolled</div>
                <div class="fw-medium">{{ $enrollments->where('enrollment_status', 'Enrolled')->count() }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted">Sessions Held</div>
                <div class="fw-medium">{{ $sessions->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ==================== STUDENT ROSTER ==================== --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Enrolled Students</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th class="text-center">P</th>
                            <th class="text-center">L</th>
                            <th class="text-center">A</th>
                            <th class="text-end">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            @php
                                $row     = $stats->get($enrollment->student_id);
                                $total   = (int) ($row->total_count ?? 0);
                                $present = (int) ($row->present_count ?? 0);
                                $late    = (int) ($row->late_count ?? 0);
                                $absent  = (int) ($row->absent_count ?? 0);
                                $rate    = \App\Models\Attendance::calculateRate($present, $late, $total);
                                $minimum = config('attendance.minimum_rate', 75);
                            @endphp
                            <tr class="{{ $enrollment->enrollment_status !== 'Enrolled' ? 'opacity-50' : '' }}">
                                <td class="small">{{ $enrollment->student->student_number }}</td>
                                <td>
                                    <div class="small fw-medium">{{ $enrollment->student->formal_name }}</div>
                                    @if ($enrollment->enrollment_status !== 'Enrolled')
                                        <span class="badge bg-secondary">{{ $enrollment->enrollment_status }}</span>
                                    @endif
                                </td>
                                <td class="text-center small text-success">{{ $present }}</td>
                                <td class="text-center small text-warning">{{ $late }}</td>
                                <td class="text-center small text-danger">{{ $absent }}</td>
                                <td class="text-end">
                                    @if ($total === 0)
                                        <span class="text-muted small">—</span>
                                    @else
                                        <span class="badge bg-{{ $rate >= $minimum ? 'success' : 'danger' }}">
                                            {{ $rate }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small py-4">
                                    No students enrolled in this class yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                Rate = ((Present + Late) / Total Sessions) × 100.
                Red means below the {{ config('attendance.minimum_rate', 75) }}% minimum.
            </div>
        </div>
    </div>

    {{-- ==================== SESSION HISTORY ==================== --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Attendance History</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-center">P</th>
                            <th class="text-center">L</th>
                            <th class="text-center">A</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            <tr>
                                <td class="small">{{ $session->session_date->format('M j, Y') }}</td>
                                <td class="text-center small text-success">{{ $session->present_total }}</td>
                                <td class="text-center small text-warning">{{ $session->late_total }}</td>
                                <td class="text-center small text-danger">{{ $session->absent_total }}</td>
                                <td>
                                    <span class="badge bg-{{ $session->isOpen() ? 'success' : 'secondary' }}">
                                        {{ $session->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-4">
                                    No sessions held yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection

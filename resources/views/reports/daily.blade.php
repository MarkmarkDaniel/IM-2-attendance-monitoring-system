@extends('layouts.app')

@section('title', 'Daily Attendance Report')
@section('page-title', 'Daily Attendance Report')
@section('page-subtitle', \Carbon\Carbon::parse($filters['date'])->format('l, F j, Y'))

@section('page-actions')
    <a href="{{ route($routePrefix . '.reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
    </button>
    <a href="{{ route($routePrefix . '.reports.csv', array_merge(['report' => 'daily'], array_filter($filters))) }}"
       class="btn btn-brand btn-sm">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </a>
    <a href="{{ route($routePrefix . '.reports.pdf', array_merge(['report' => 'daily'], array_filter($filters))) }}"
       class="btn btn-danger btn-sm">
        <i class="bi bi-filetype-pdf me-1"></i>Export PDF
    </a>
@endsection

@section('content')

{{-- ==================== FILTERS ==================== --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.reports.daily') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="date" class="form-label small">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control form-control-sm"
                       id="date" name="date" value="{{ $filters['date'] }}" required>
            </div>
            <div class="col-md-3">
                <label for="class_id" class="form-label small">Class</label>
                <select class="form-select form-select-sm" id="class_id" name="class_id">
                    <option value="">All Classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}"
                            {{ $filters['class_id'] == $class->id ? 'selected' : '' }}>
                            {{ $class->subject->subject_code }} &mdash; {{ $class->section }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="subject_id" class="form-label small">Subject</label>
                <select class="form-select form-select-sm" id="subject_id" name="subject_id">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}"
                            {{ $filters['subject_id'] == $subject->id ? 'selected' : '' }}>
                            {{ $subject->subject_code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All</option>
                    @foreach (['Present', 'Late', 'Absent'] as $state)
                        <option value="{{ $state }}"
                            {{ $filters['status'] === $state ? 'selected' : '' }}>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Generate</button>
                <a href="{{ route($routePrefix . '.reports.daily') }}"
                   class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@php
    $appliedLabels = [];
    $appliedLabels['Date'] = \Carbon\Carbon::parse($filters['date'])->format('F j, Y');

    if ($filters['class_id']) {
        $selected = $classes->firstWhere('id', (int) $filters['class_id']);
        if ($selected) {
            $appliedLabels['Class'] = $selected->subject->subject_code . ' — ' . $selected->section;
        }
    }
    if ($filters['subject_id']) {
        $selected = $subjects->firstWhere('id', (int) $filters['subject_id']);
        if ($selected) $appliedLabels['Subject'] = $selected->subject_code;
    }
    if ($filters['status']) {
        $appliedLabels['Status'] = $filters['status'];
    }
@endphp

@include('reports._header', ['reportTitle' => 'Daily Attendance Report'])
@include('reports._summary')

{{-- ==================== CHART ==================== --}}
@if ($summary['total'] > 0)
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white fw-medium">Status Breakdown</div>
                <div class="card-body">
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white fw-medium">Notes</div>
                <div class="card-body small">
                    <p class="mb-2">
                        This report shows every attendance record saved for
                        <strong>{{ \Carbon\Carbon::parse($filters['date'])->format('F j, Y') }}</strong>.
                    </p>
                    <p class="mb-2">
                        Records with a blank Time In are absences &mdash; the student never
                        checked in, so the system created the record when the session closed.
                    </p>
                    <p class="mb-0 text-muted">
                        The summary above always covers every status, even when the table
                        below is filtered to one status.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ==================== TABLE ==================== --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Department</th>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Time In</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="small">{{ $record->student->student_number }}</td>
                        <td class="small fw-medium">{{ $record->student->formal_name }}</td>
                        <td class="small">{{ $record->student->department->department_code }}</td>
                        <td class="small">{{ $record->classModel->subject->subject_code }}</td>
                        <td class="small">{{ $record->classModel->section }}</td>
                        <td class="small">
                            {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : '—' }}
                        </td>
                        <td><span class="badge bg-{{ $record->status_badge }}">{{ $record->status }}</span></td>
                        <td class="small text-muted">{{ $record->remarks ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                            No attendance records for this date and filter combination.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($records->isNotEmpty())
        <div class="card-footer bg-white small text-muted">
            {{ $records->count() }} record(s) shown.
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    const dailySummary = @json($summary);

    const dailyCanvas = document.getElementById('dailyChart');
    if (dailyCanvas) {
        new Chart(dailyCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [dailySummary.present, dailySummary.late, dailySummary.absent],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
</script>
@endpush

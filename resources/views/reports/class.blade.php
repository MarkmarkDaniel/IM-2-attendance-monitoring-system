@extends('layouts.app')

@section('title', 'Class Attendance Report')
@section('page-title', 'Class Attendance Report')
@section('page-subtitle', $selectedClass ? $selectedClass->class_title : 'All classes')

@section('page-actions')
    <a href="{{ route($routePrefix . '.reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
    </button>
    <a href="{{ route($routePrefix . '.reports.csv', array_merge(['report' => 'class'], array_filter($filters))) }}"
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
        <form method="GET" action="{{ route($routePrefix . '.reports.class') }}" class="row g-2 align-items-end">
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

            @if ($isAdmin)
                <div class="col-md-2">
                    <label for="instructor_id" class="form-label small">Instructor</label>
                    <select class="form-select form-select-sm" id="instructor_id" name="instructor_id">
                        <option value="">All Instructors</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}"
                                {{ $filters['instructor_id'] == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2">
                <label for="date_from" class="form-label small">From</label>
                <input type="date" class="form-control form-control-sm"
                       id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label small">To</label>
                <input type="date" class="form-control form-control-sm"
                       id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Generate</button>
                <a href="{{ route($routePrefix . '.reports.class') }}"
                   class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@php
    $appliedLabels = [];

    if ($selectedClass) {
        $appliedLabels['Class'] = $selectedClass->class_title;
    }
    if ($filters['subject_id']) {
        $selected = $subjects->firstWhere('id', (int) $filters['subject_id']);
        if ($selected) $appliedLabels['Subject'] = $selected->subject_code;
    }
    if ($filters['instructor_id']) {
        $selected = $instructors->firstWhere('id', (int) $filters['instructor_id']);
        if ($selected) $appliedLabels['Instructor'] = $selected->full_name;
    }
    if ($filters['date_from']) {
        $appliedLabels['From'] = \Carbon\Carbon::parse($filters['date_from'])->format('M j, Y');
    }
    if ($filters['date_to']) {
        $appliedLabels['To'] = \Carbon\Carbon::parse($filters['date_to'])->format('M j, Y');
    }
@endphp

@include('reports._header', ['reportTitle' => 'Class Attendance Report'])
@include('reports._summary')

{{-- ==================== CHART ==================== --}}
@if ($rows->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header bg-white fw-medium">Attendance Rate per Class</div>
        <div class="card-body">
            <canvas id="classReportChart" height="{{ max(80, $rows->count() * 22) }}"></canvas>
        </div>
    </div>
@endif

{{-- ==================== PER CLASS TABLE ==================== --}}
<div class="card mb-3">
    <div class="card-header bg-white fw-medium">Class Summary</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th class="text-center">Students</th>
                    <th class="text-center">Sessions</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">Absent</th>
                    <th class="text-end">Rate</th>
                </tr>
            </thead>
            <tbody>
                @php $minimum = config('attendance.minimum_rate', 75); @endphp
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <div class="small fw-medium">{{ $row->subject_code }}</div>
                            <div class="text-muted" style="font-size: .78rem;">
                                {{ Str::limit($row->subject_name, 30) }}
                            </div>
                        </td>
                        <td class="small">{{ $row->section }}</td>
                        <td class="small">{{ $row->instructor_name }}</td>
                        <td class="small">
                            {{ $row->schedule_day }}
                            {{ \Carbon\Carbon::parse($row->start_time)->format('g:i A') }}
                        </td>
                        <td class="text-center small">{{ $row->student_count }}</td>
                        <td class="text-center small">{{ $row->session_count }}</td>
                        <td class="text-center small text-success">{{ $row->present_count }}</td>
                        <td class="text-center small text-warning">{{ $row->late_count }}</td>
                        <td class="text-center small text-danger">{{ $row->absent_count }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $row->attendance_rate >= $minimum ? 'success' : 'danger' }}">
                                {{ $row->attendance_rate }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                            No attendance records match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ==================== PER STUDENT (single class only) ==================== --}}
@if ($perStudent->isNotEmpty())
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-medium">Student Breakdown</span>
            <span class="small text-muted">{{ $perStudent->count() }} student(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Department</th>
                        <th>Year / Section</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Absent</th>
                        <th class="text-end">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perStudent as $row)
                        <tr>
                            <td class="small">{{ $row->student_number }}</td>
                            <td class="small fw-medium">{{ $row->formal_name }}</td>
                            <td class="small">{{ $row->department_code }}</td>
                            <td class="small">{{ $row->year_level }} &mdash; {{ $row->section }}</td>
                            <td class="text-center small">{{ $row->total_count }}</td>
                            <td class="text-center small text-success">{{ $row->present_count }}</td>
                            <td class="text-center small text-warning">{{ $row->late_count }}</td>
                            <td class="text-center small text-danger">{{ $row->absent_count }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $row->attendance_rate >= $minimum ? 'success' : 'danger' }}">
                                    {{ $row->attendance_rate }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            Red badges mark students below the {{ $minimum }}% minimum attendance rate.
        </div>
    </div>
@elseif (! $filters['class_id'])
    <div class="alert alert-light border small">
        <i class="bi bi-info-circle me-1"></i>
        Select a single class above to see the per-student breakdown.
    </div>
@endif

@endsection

@push('scripts')
<script>
    const classRows = @json($rows->map(fn ($r) => [
        'label' => $r->subject_code . ' ' . $r->section,
        'rate'  => (float) $r->attendance_rate,
    ])->values());

    const classCanvas = document.getElementById('classReportChart');
    if (classCanvas && classRows.length > 0) {
        new Chart(classCanvas, {
            type: 'bar',
            data: {
                labels: classRows.map(r => r.label),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: classRows.map(r => r.rate),
                    backgroundColor: '#14304f',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, max: 100 } }
            }
        });
    }
</script>
@endpush

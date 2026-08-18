@extends('layouts.app')

@section('title', 'Student Attendance Report')
@section('page-title', 'Student Attendance Report')
@section('page-subtitle', $student ? $student->student_number . ' — ' . $student->full_name : 'Select a student to begin')

@section('page-actions')
    <a href="{{ route($routePrefix . '.reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    @if ($student)
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        <a href="{{ route($routePrefix . '.reports.csv', array_merge(['report' => 'student'], array_filter($filters))) }}"
           class="btn btn-brand btn-sm">
            <i class="bi bi-filetype-csv me-1"></i>Export CSV
        </a>
        <a href="{{ route($routePrefix . '.reports.pdf', array_merge(['report' => 'student'], array_filter($filters))) }}"
           class="btn btn-danger btn-sm">
            <i class="bi bi-filetype-pdf me-1"></i>Export PDF
        </a>
    @endif
@endsection

@section('content')

{{-- ==================== FILTERS ==================== --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.reports.student') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="student_id" class="form-label small">Student <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="student_id" name="student_id" required>
                    <option value="">-- Select Student --</option>
                    @foreach ($students as $option)
                        <option value="{{ $option->id }}"
                            {{ $filters['student_id'] == $option->id ? 'selected' : '' }}>
                            {{ $option->student_number }} &mdash; {{ $option->formal_name }}
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
                <a href="{{ route($routePrefix . '.reports.student') }}"
                   class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if (! $student)

    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-person-lines-fill d-block fs-1 mb-3"></i>
            <h2 class="h6">Select a student above to generate the report</h2>
            <p class="small mb-0">
                You can narrow the results further by subject and date range.
            </p>
        </div>
    </div>

@else

    @php
        $appliedLabels = ['Student' => $student->student_number . ' — ' . $student->full_name];

        if ($filters['subject_id']) {
            $selected = $subjects->firstWhere('id', (int) $filters['subject_id']);
            if ($selected) $appliedLabels['Subject'] = $selected->subject_code;
        }
        if ($filters['date_from']) {
            $appliedLabels['From'] = \Carbon\Carbon::parse($filters['date_from'])->format('M j, Y');
        }
        if ($filters['date_to']) {
            $appliedLabels['To'] = \Carbon\Carbon::parse($filters['date_to'])->format('M j, Y');
        }
    @endphp

    @include('reports._header', ['reportTitle' => 'Student Attendance Report'])

    {{-- ==================== STUDENT INFO ==================== --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 small">
                <div class="col-md-3">
                    <div class="text-muted">Student Number</div>
                    <div class="fw-medium">{{ $student->student_number }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Full Name</div>
                    <div class="fw-medium">{{ $student->full_name }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Department</div>
                    <div class="fw-medium">
                        {{ $student->department->department_code }} &mdash;
                        {{ $student->department->department_name }}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted">Year Level</div>
                    <div class="fw-medium">{{ $student->year_level }}</div>
                </div>
                <div class="col-md-1">
                    <div class="text-muted">Section</div>
                    <div class="fw-medium">{{ $student->section }}</div>
                </div>
            </div>
        </div>
    </div>

    @include('reports._summary')

    {{-- ==================== PER SUBJECT BREAKDOWN ==================== --}}
    @if ($perClass->isNotEmpty())
        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-white fw-medium">Breakdown by Subject</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th class="text-center">Sessions</th>
                                    <th class="text-center">P</th>
                                    <th class="text-center">L</th>
                                    <th class="text-center">A</th>
                                    <th class="text-end">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($perClass as $row)
                                    @php $minimum = config('attendance.minimum_rate', 75); @endphp
                                    <tr>
                                        <td class="small fw-medium">{{ $row->subject_code }}</td>
                                        <td class="small">{{ $row->section }}</td>
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-white fw-medium">Rate per Subject</div>
                    <div class="card-body">
                        <canvas id="studentChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== FULL RECORD LIST ==================== --}}
    <div class="card">
        <div class="card-header bg-white fw-medium">Attendance History</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
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
                            <td class="small">{{ $record->attendance_date->format('M j, Y') }}</td>
                            <td class="small">
                                <span class="fw-medium">{{ $record->classModel->subject->subject_code }}</span>
                                <span class="text-muted">
                                    {{ Str::limit($record->classModel->subject->subject_name, 30) }}
                                </span>
                            </td>
                            <td class="small">{{ $record->classModel->section }}</td>
                            <td class="small">
                                {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : '—' }}
                            </td>
                            <td><span class="badge bg-{{ $record->status_badge }}">{{ $record->status }}</span></td>
                            <td class="small text-muted">{{ $record->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                                No attendance records match these filters.
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

@endif

@endsection

@push('scripts')
@if ($student && $perClass->isNotEmpty())
<script>
    const studentPerClass = @json($perClass->map(fn ($r) => [
        'label' => $r->subject_code,
        'rate'  => $r->attendance_rate,
    ])->values());

    const studentCanvas = document.getElementById('studentChart');
    if (studentCanvas) {
        new Chart(studentCanvas, {
            type: 'bar',
            data: {
                labels: studentPerClass.map(r => r.label),
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: studentPerClass.map(r => r.rate),
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
@endif
@endpush

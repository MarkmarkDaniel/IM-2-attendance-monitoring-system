@extends('layouts.app')

@section('title', 'Monthly Attendance Report')
@section('page-title', 'Monthly Attendance Report')
@section('page-subtitle', \Carbon\Carbon::create($year, $month, 1)->format('F Y'))

@section('page-actions')
    <a href="{{ route($routePrefix . '.reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
    </button>
    <a href="{{ route($routePrefix . '.reports.csv', array_merge(['report' => 'monthly'], array_filter($filters))) }}"
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
        <form method="GET" action="{{ route($routePrefix . '.reports.monthly') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="month" class="form-label small">Month <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="month" name="month" required>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label small">Year <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="year" name="year" required>
                    @for ($y = now()->year - 3; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
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

            @if ($isAdmin)
                <div class="col-md-3">
                    <label for="department_id" class="form-label small">Department</label>
                    <select class="form-select form-select-sm" id="department_id" name="department_id">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}"
                                {{ $filters['department_id'] == $department->id ? 'selected' : '' }}>
                                {{ $department->department_code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Generate</button>
                <a href="{{ route($routePrefix . '.reports.monthly') }}"
                   class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@php
    $appliedLabels = ['Month' => \Carbon\Carbon::create($year, $month, 1)->format('F Y')];

    if ($filters['class_id']) {
        $selected = $classes->firstWhere('id', (int) $filters['class_id']);
        if ($selected) {
            $appliedLabels['Class'] = $selected->subject->subject_code . ' — ' . $selected->section;
        }
    }
    if ($filters['department_id']) {
        $selected = $departments->firstWhere('id', (int) $filters['department_id']);
        if ($selected) $appliedLabels['Department'] = $selected->department_code;
    }
@endphp

@include('reports._header', ['reportTitle' => 'Monthly Attendance Report'])
@include('reports._summary')

{{-- ==================== EXTRA STATS ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Days with Records</div>
                <div class="stat-value">{{ $rows->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Best Day</div>
                @if ($rows->isNotEmpty())
                    @php $best = $rows->sortByDesc('attendance_rate')->first(); @endphp
                    <div class="stat-value text-success">{{ $best->attendance_rate }}%</div>
                    <div class="small text-muted">
                        {{ \Carbon\Carbon::parse($best->attendance_date)->format('M j') }}
                    </div>
                @else
                    <div class="stat-value text-muted">—</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Lowest Day</div>
                @if ($rows->isNotEmpty())
                    @php $worst = $rows->sortBy('attendance_rate')->first(); @endphp
                    <div class="stat-value text-danger">{{ $worst->attendance_rate }}%</div>
                    <div class="small text-muted">
                        {{ \Carbon\Carbon::parse($worst->attendance_date)->format('M j') }}
                    </div>
                @else
                    <div class="stat-value text-muted">—</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Classes Covered</div>
                <div class="stat-value">{{ $perClass->count() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== CHARTS ==================== --}}
@if ($rows->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header bg-white fw-medium">Daily Attendance Trend</div>
        <div class="card-body">
            <canvas id="monthlyTrendChart" height="110"></canvas>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-white fw-medium">Daily Attendance Rate</div>
        <div class="card-body">
            <canvas id="monthlyRateChart" height="90"></canvas>
        </div>
    </div>
@endif

{{-- ==================== DAY BY DAY TABLE ==================== --}}
<div class="card mb-3">
    <div class="card-header bg-white fw-medium">Day-by-Day Breakdown</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th class="text-center">Total Records</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">Absent</th>
                    <th class="text-end">Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @php $minimum = config('attendance.minimum_rate', 75); @endphp
                @forelse ($rows as $row)
                    <tr>
                        <td class="small">
                            {{ \Carbon\Carbon::parse($row->attendance_date)->format('M j, Y') }}
                        </td>
                        <td class="small text-muted">
                            {{ \Carbon\Carbon::parse($row->attendance_date)->format('l') }}
                        </td>
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
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                            No attendance records for
                            {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ==================== PER CLASS FOR THE MONTH ==================== --}}
@if ($perClass->isNotEmpty())
    <div class="card">
        <div class="card-header bg-white fw-medium">Class Totals for This Month</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Instructor</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Absent</th>
                        <th class="text-end">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perClass as $row)
                        <tr>
                            <td class="small fw-medium">{{ $row->subject_code }}</td>
                            <td class="small">{{ $row->section }}</td>
                            <td class="small">{{ $row->instructor_name }}</td>
                            <td class="text-center small">{{ $row->session_count }}</td>
                            <td class="text-center small text-success">{{ $row->present_count }}</td>
                            <td class="text-center small text-warning">{{ $row->late_count }}</td>
                            <td class="text-center small text-danger">{{ $row->absent_count }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $row->attendance_rate >= config('attendance.minimum_rate', 75) ? 'success' : 'danger' }}">
                                    {{ $row->attendance_rate }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    const monthlyChart = @json($chart);

    // ---------- Stacked bar: daily counts ----------
    const trendCanvas = document.getElementById('monthlyTrendChart');
    if (trendCanvas && monthlyChart.labels.length > 0) {
        new Chart(trendCanvas, {
            type: 'bar',
            data: {
                labels: monthlyChart.labels,
                datasets: [
                    { label: 'Present', data: monthlyChart.present, backgroundColor: '#198754' },
                    { label: 'Late',    data: monthlyChart.late,    backgroundColor: '#ffc107' },
                    { label: 'Absent',  data: monthlyChart.absent,  backgroundColor: '#dc3545' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // ---------- Line: daily rate ----------
    const rateCanvas = document.getElementById('monthlyRateChart');
    if (rateCanvas && monthlyChart.labels.length > 0) {
        new Chart(rateCanvas, {
            type: 'line',
            data: {
                labels: monthlyChart.labels,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: monthlyChart.rate,
                    borderColor: '#14304f',
                    backgroundColor: 'rgba(20, 48, 79, .1)',
                    fill: true,
                    tension: .3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    }
</script>
@endpush

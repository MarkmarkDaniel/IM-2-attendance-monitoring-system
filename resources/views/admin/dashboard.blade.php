@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')
@section('page-subtitle', 'System-wide attendance overview')

@section('content')

{{-- ==================== COUNT CARDS ==================== --}}
<div class="row g-3 mb-3">
    @foreach ([
        ['label' => 'Students',    'value' => $counts['students'],    'icon' => 'bi-people',            'color' => 'primary'],
        ['label' => 'Instructors', 'value' => $counts['instructors'], 'icon' => 'bi-person-badge',      'color' => 'info'],
        ['label' => 'Departments', 'value' => $counts['departments'], 'icon' => 'bi-building',          'color' => 'secondary'],
        ['label' => 'Subjects',    'value' => $counts['subjects'],    'icon' => 'bi-journal-bookmark',  'color' => 'dark'],
        ['label' => 'Classes',     'value' => $counts['classes'],     'icon' => 'bi-easel',             'color' => 'success'],
    ] as $card)
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-{{ $card['color'] }} bg-opacity-10 text-{{ $card['color'] }}">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ number_format($card['value']) }}</div>
                        <div class="stat-label">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ==================== TODAY ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card stat-card h-100 border-success border-2">
            <div class="card-body">
                <div class="stat-label">Today's Present</div>
                <div class="stat-value text-success">{{ $todayStats['present'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-warning border-2">
            <div class="card-body">
                <div class="stat-label">Today's Late</div>
                <div class="stat-value text-warning">{{ $todayStats['late'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-danger border-2">
            <div class="card-body">
                <div class="stat-label">Today's Absent</div>
                <div class="stat-value text-danger">{{ $todayStats['absent'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100 border-primary border-2">
            <div class="card-body">
                <div class="stat-label">Overall Attendance Rate</div>
                <div class="stat-value text-primary">{{ $overallRate }}%</div>
                <div class="small text-muted">{{ number_format($overviewTotal) }} total records</div>
            </div>
        </div>
    </div>
</div>

@if ($todayStats['present'] + $todayStats['late'] + $todayStats['absent'] === 0)
    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>
        No attendance has been recorded for {{ now()->format('F j, Y') }} yet.
        The charts below use all historical records.
    </div>
@endif

{{-- ==================== CHARTS ==================== --}}
<div class="row g-3 mb-3">

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Attendance Overview</div>
            <div class="card-body">
                @if ($overviewTotal > 0)
                    <canvas id="overviewChart" height="220"></canvas>
                @else
                    <p class="text-muted text-center small py-5 mb-0">No attendance records yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Attendance Trend</div>
            <div class="card-body">
                @if (count($trendChart['labels']) > 0)
                    <canvas id="trendChart" height="110"></canvas>
                @else
                    <p class="text-muted text-center small py-5 mb-0">No attendance records yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white fw-medium">Attendance Rate by Department</div>
            <div class="card-body">
                @if (count($deptChart['labels']) > 0)
                    <canvas id="deptChart" height="80"></canvas>
                @else
                    <p class="text-muted text-center small py-4 mb-0">No department data yet.</p>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ==================== RECENT ACTIVITY ==================== --}}
<div class="row g-3">

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Recent Attendance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendance as $record)
                            <tr>
                                <td>
                                    <div class="small fw-medium">{{ $record->student->full_name }}</div>
                                    <div class="text-muted" style="font-size: .75rem;">
                                        {{ $record->student->student_number }}
                                    </div>
                                </td>
                                <td class="small">{{ $record->classModel->subject->subject_code }}</td>
                                <td class="small">{{ $record->attendance_date->format('M j, Y') }}</td>
                                <td class="small">
                                    {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $record->status_badge }}">{{ $record->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-4">
                                    No attendance records yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">

        <div class="card mb-3">
            <div class="card-header bg-white fw-medium">Quick Actions</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.departments.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-building-add me-2"></i>Add Department
                </a>
                <a href="{{ route('admin.subjects.create') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-journal-plus me-2"></i>Add Subject
                </a>
                <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-list-ul me-2"></i>Manage Departments
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-medium">Recent Notifications</div>
            <div class="list-group list-group-flush">
                @forelse ($recentNotifications as $notification)
                    <div class="list-group-item {{ $notification->is_read ? '' : 'bg-light' }}">
                        <div class="d-flex gap-2">
                            <i class="bi {{ $notification->icon }} text-{{ $notification->type }}"></i>
                            <div>
                                <div class="small fw-medium">{{ $notification->title }}</div>
                                <div class="small text-muted">{{ Str::limit($notification->message, 80) }}</div>
                                <div class="text-muted" style="font-size: .72rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted small py-4">
                        No notifications yet.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ==================== LOW ATTENDANCE ==================== --}}
@if ($lowAttendance->isNotEmpty())
    <div class="card mt-3 border-danger">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-medium text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Students Below {{ config('attendance.minimum_rate', 75) }}% Attendance
            </span>
            <span class="badge bg-danger">{{ $lowAttendance->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th class="text-center">Sessions</th>
                        <th class="text-end">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lowAttendance as $row)
                        <tr>
                            <td class="small">{{ $row->student_number }}</td>
                            <td class="small fw-medium">{{ $row->student_name }}</td>
                            <td class="small">{{ $row->subject_code }}</td>
                            <td class="small">{{ $row->section }}</td>
                            <td class="text-center small">{{ $row->total_count }}</td>
                            <td class="text-end">
                                <span class="badge bg-danger">{{ $row->attendance_rate }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            Only students with at least 3 recorded sessions are listed.
            Run <code>php artisan attendance:check-warnings</code> to notify them.
        </div>
    </div>
@endif


@endsection

@push('scripts')
<script>
    // All chart data comes from MySQL through the controller.
    const overviewData = @json($overview);
    const trendData    = @json($trendChart);
    const deptData     = @json($deptChart);

    const COLORS = {
        present: '#198754',
        late:    '#ffc107',
        absent:  '#dc3545',
        brand:   '#14304f'
    };

    // ---------- Chart 1: doughnut ----------
    const overviewCanvas = document.getElementById('overviewChart');
    if (overviewCanvas) {
        new Chart(overviewCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [overviewData.present, overviewData.late, overviewData.absent],
                    backgroundColor: [COLORS.present, COLORS.late, COLORS.absent],
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

    // ---------- Chart 2: line trend ----------
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    { label: 'Present', data: trendData.present, borderColor: COLORS.present, backgroundColor: COLORS.present, tension: .3 },
                    { label: 'Late',    data: trendData.late,    borderColor: COLORS.late,    backgroundColor: COLORS.late,    tension: .3 },
                    { label: 'Absent',  data: trendData.absent,  borderColor: COLORS.absent,  backgroundColor: COLORS.absent,  tension: .3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // ---------- Chart 3: department bars ----------
    const deptCanvas = document.getElementById('deptChart');
    if (deptCanvas) {
        new Chart(deptCanvas, {
            type: 'bar',
            data: {
                labels: deptData.labels,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: deptData.rates,
                    backgroundColor: COLORS.brand,
                    borderRadius: 4
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

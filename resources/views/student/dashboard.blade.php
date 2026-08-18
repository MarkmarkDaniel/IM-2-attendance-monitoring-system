@extends('layouts.app')

@section('title', 'Student Dashboard')
@section('page-title', 'Welcome, ' . $student->first_name)
@section('page-subtitle', $student->student_number)

@section('content')

{{-- ==================== SCAN QR CALL TO ACTION ==================== --}}
<div class="card mb-3 border-0" style="background: #14304f;">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 text-white p-4">
        <div>
            <h2 class="h5 mb-1">
                <i class="bi bi-qr-code-scan me-2"></i>Ready to check in?
            </h2>
            @if ($openSessions->isNotEmpty())
                <p class="mb-0 small opacity-75">
                    {{ $openSessions->count() }} open session(s):
                    {{ $openSessions->map(fn ($s) => $s->classModel->subject->subject_code)->implode(', ') }}
                </p>
            @else
                <p class="mb-0 small opacity-75">
                    No open sessions right now. Your instructor will start one during class.
                </p>
            @endif
        </div>
        <a href="{{ route('student.qr.scanner') }}" class="btn btn-light btn-lg px-4">
            <i class="bi bi-camera me-2"></i>SCAN QR ATTENDANCE
        </a>
    </div>
</div>

{{-- ==================== PROFILE STRIP ==================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted">Student Name</div>
                <div class="fw-medium">{{ $student->full_name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Student Number</div>
                <div class="fw-medium">{{ $student->student_number }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Department</div>
                <div class="fw-medium">{{ $student->department->department_code }}</div>
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

{{-- ==================== ATTENDANCE SUMMARY ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $summary['total'] }}</div>
                    <div class="stat-label">Total Sessions</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-success border-2">
            <div class="card-body">
                <div class="stat-label">Present</div>
                <div class="stat-value text-success">{{ $summary['present'] }}</div>
                <div class="small text-muted">{{ $summary['present_pct'] }}%</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-warning border-2">
            <div class="card-body">
                <div class="stat-label">Late</div>
                <div class="stat-value text-warning">{{ $summary['late'] }}</div>
                <div class="small text-muted">{{ $summary['late_pct'] }}%</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-danger border-2">
            <div class="card-body">
                <div class="stat-label">Absent</div>
                <div class="stat-value text-danger">{{ $summary['absent'] }}</div>
                <div class="small text-muted">{{ $summary['absent_pct'] }}%</div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== OVERALL RATE ==================== --}}
@php $minimum = config('attendance.minimum_rate', 75); @endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-medium">Overall Attendance Rate</span>
            <span class="badge bg-{{ $summary['rate'] >= $minimum ? 'success' : 'danger' }} fs-6">
                {{ $summary['rate'] }}%
            </span>
        </div>

        <div class="progress" style="height: 22px;">
            <div class="progress-bar bg-success" role="progressbar"
                 style="width: {{ $summary['present_pct'] }}%">
                @if ($summary['present_pct'] >= 10) Present {{ $summary['present_pct'] }}% @endif
            </div>
            <div class="progress-bar bg-warning" role="progressbar"
                 style="width: {{ $summary['late_pct'] }}%">
                @if ($summary['late_pct'] >= 10) Late {{ $summary['late_pct'] }}% @endif
            </div>
            <div class="progress-bar bg-danger" role="progressbar"
                 style="width: {{ $summary['absent_pct'] }}%">
                @if ($summary['absent_pct'] >= 10) Absent {{ $summary['absent_pct'] }}% @endif
            </div>
        </div>

        <p class="small text-muted mt-2 mb-0">
            Rate = ((Present + Late) ÷ Total Sessions) × 100.
            The minimum required is {{ $minimum }}%.
        </p>

        @if ($summary['total'] > 0 && $summary['rate'] < $minimum)
            <div class="alert alert-warning small mt-3 mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Your attendance is below the {{ $minimum }}% minimum. Talk to your instructor.
            </div>
        @endif
    </div>
</div>

{{-- ==================== CHARTS + RECENT ==================== --}}
<div class="row g-3">

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">My Attendance</div>
            <div class="card-body">
                @if ($summary['total'] > 0)
                    <canvas id="myChart" height="230"></canvas>
                @else
                    <p class="text-muted text-center small py-5 mb-0">
                        No attendance records yet.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Attendance Rate per Subject</div>
            <div class="card-body">
                @if (count($subjectChart['labels']) > 0)
                    <canvas id="subjectChart" height="130"></canvas>
                @else
                    <p class="text-muted text-center small py-5 mb-0">
                        You are not enrolled in any subjects yet.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">Recent Attendance</span>
                <a href="{{ route('student.attendance') }}" class="small text-decoration-none">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
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
                        @forelse ($recentAttendance as $record)
                            <tr>
                                <td class="small">{{ $record->attendance_date->format('M j, Y') }}</td>
                                <td class="small fw-medium">
                                    {{ $record->classModel->subject->subject_code }}
                                </td>
                                <td class="small">{{ $record->classModel->section }}</td>
                                <td class="small">
                                    {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $record->status_badge }}">{{ $record->status }}</span>
                                </td>
                                <td class="small text-muted">{{ $record->remarks ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small py-4">
                                    No attendance records yet. Scan a QR code to check in.
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

@push('scripts')
<script>
    const mySummary    = @json($summary);
    const subjectData  = @json($subjectChart);

    const myCanvas = document.getElementById('myChart');
    if (myCanvas) {
        new Chart(myCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Late', 'Absent'],
                datasets: [{
                    data: [mySummary.present, mySummary.late, mySummary.absent],
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

    const subjectCanvas = document.getElementById('subjectChart');
    if (subjectCanvas) {
        new Chart(subjectCanvas, {
            type: 'bar',
            data: {
                labels: subjectData.labels,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: subjectData.rates,
                    backgroundColor: '#14304f',
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

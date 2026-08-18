@extends('layouts.app')

@section('title', 'Instructor Dashboard')
@section('page-title', 'Welcome, ' . $instructor->full_name)
@section('page-subtitle', $instructor->department->department_code . ' — ' . $instructor->employee_number)

@section('content')

{{-- ==================== STAT CARDS ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-easel"></i></div>
                <div>
                    <div class="stat-value">{{ $classes->count() }}</div>
                    <div class="stat-label">Assigned Classes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-day"></i></div>
                <div>
                    <div class="stat-value">{{ $todayClasses->count() }}</div>
                    <div class="stat-label">Classes Today</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-value">{{ $classes->sum('active_enrollments_count') }}</div>
                    <div class="stat-label">Enrolled Students</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-dark bg-opacity-10 text-dark"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="stat-value">{{ $attendanceRate }}%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ==================== TODAY ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card h-100 border-success border-2">
            <div class="card-body">
                <div class="stat-label">Present Today</div>
                <div class="stat-value text-success">{{ $todayStats['present'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100 border-warning border-2">
            <div class="card-body">
                <div class="stat-label">Late Today</div>
                <div class="stat-value text-warning">{{ $todayStats['late'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100 border-danger border-2">
            <div class="card-body">
                <div class="stat-label">Absent Today</div>
                <div class="stat-value text-danger">{{ $todayStats['absent'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">

    {{-- ==================== TODAY'S SCHEDULE ==================== --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">
                Today's Schedule &mdash; {{ $dayName }}
            </div>
            <div class="list-group list-group-flush">
                @forelse ($todayClasses as $class)
                    @php $session = $todaySessions->get($class->id); @endphp
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-medium small">
                                    {{ $class->subject->subject_code }} &mdash; {{ $class->section }}
                                </div>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ \Carbon\Carbon::parse($class->start_time)->format('g:i A') }} –
                                    {{ \Carbon\Carbon::parse($class->end_time)->format('g:i A') }}
                                    &middot; {{ $class->room ?? 'No room' }}
                                    &middot; {{ $class->active_enrollments_count }} students
                                </div>
                            </div>
                            <div class="text-end no-print">
                                @if ($session && $session->isOpen())
                                    <span class="badge bg-success mb-1 d-block">Session Open</span>
                                    <a href="{{ route('instructor.attendance.record', $class) }}"
                                       class="btn btn-sm btn-brand">Record</a>
                                @elseif ($session)
                                    <span class="badge bg-secondary mb-1 d-block">Closed</span>
                                    <a href="{{ route('instructor.attendance.record', $class) }}"
                                       class="btn btn-sm btn-outline-secondary">View</a>
                                @else
                                    <form method="POST" action="{{ route('instructor.sessions.open', $class) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-brand">
                                            <i class="bi bi-play-fill me-1"></i>Start
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted small py-4">
                        <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                        No classes scheduled for {{ $dayName }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ==================== CHART ==================== --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Attendance Rate per Class</div>
            <div class="card-body">
                @if (count($classChart['labels']) > 0)
                    <canvas id="classChart" height="200"></canvas>
                @else
                    <p class="text-muted text-center small py-5 mb-0">No classes assigned yet.</p>
                @endif
            </div>
        </div>
    </div>

</div>

<div class="row g-3">

    {{-- ==================== CLASS LIST ==================== --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">My Classes</span>
                <a href="{{ route('instructor.classes.index') }}" class="small text-decoration-none">View all</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($classes as $class)
                    <a href="{{ route('instructor.classes.show', $class) }}"
                       class="list-group-item list-group-item-action">
                        <div class="fw-medium small">
                            {{ $class->subject->subject_code }} &mdash; {{ $class->section }}
                        </div>
                        <div class="text-muted" style="font-size: .78rem;">
                            {{ $class->schedule_text }} &middot; {{ $class->active_enrollments_count }} students
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-center text-muted small py-4">
                        No classes assigned yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ==================== RECENT ATTENDANCE ==================== --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Recent Attendance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendance as $record)
                            <tr>
                                <td class="small">{{ $record->student->formal_name }}</td>
                                <td class="small">{{ $record->classModel->subject->subject_code }}</td>
                                <td class="small">{{ $record->attendance_date->format('M j') }}</td>
                                <td class="small">
                                    {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : '—' }}
                                </td>
                                <td><span class="badge bg-{{ $record->status_badge }}">{{ $record->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-4">
                                    No attendance recorded yet.
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
    const classChartData = @json($classChart);

    const canvas = document.getElementById('classChart');
    if (canvas) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: classChartData.labels,
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: classChartData.rates,
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

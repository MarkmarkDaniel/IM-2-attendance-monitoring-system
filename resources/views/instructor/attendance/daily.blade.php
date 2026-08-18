@extends('layouts.app')

@section('title', 'Daily Attendance')
@section('page-title', 'Daily Attendance Monitoring')
@section('page-subtitle', \Carbon\Carbon::parse($date)->format('l, F j, Y'))

@section('page-actions')
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
    </button>
@endsection

@section('content')

{{-- ==================== FILTERS ==================== --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('instructor.attendance.daily') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label for="date" class="form-label small">Date</label>
                <input type="date" class="form-control form-control-sm"
                       id="date" name="date" value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <label for="class_id" class="form-label small">Class</label>
                <select class="form-select form-select-sm" id="class_id" name="class_id">
                    <option value="">All My Classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                            {{ $class->subject->subject_code }} &mdash; {{ $class->section }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="">All</option>
                    <option value="Present" {{ $status === 'Present' ? 'selected' : '' }}>Present</option>
                    <option value="Late"    {{ $status === 'Late'    ? 'selected' : '' }}>Late</option>
                    <option value="Absent"  {{ $status === 'Absent'  ? 'selected' : '' }}>Absent</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
                <a href="{{ route('instructor.attendance.daily') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- ==================== SUMMARY ==================== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Total Records</div>
                <div class="stat-value">{{ $summary['total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-success border-2">
            <div class="card-body">
                <div class="stat-label">Present</div>
                <div class="stat-value text-success">{{ $summary['present'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-warning border-2">
            <div class="card-body">
                <div class="stat-label">Late</div>
                <div class="stat-value text-warning">{{ $summary['late'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-danger border-2">
            <div class="card-body">
                <div class="stat-label">Absent</div>
                <div class="stat-value text-danger">{{ $summary['absent'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border small">
    <i class="bi bi-graph-up me-1"></i>
    Attendance rate for this view:
    <strong>{{ $summary['rate'] }}%</strong>
    &mdash; ((Present + Late) ÷ Total) × 100
</div>

{{-- ==================== TABLE ==================== --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student Number</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Time In</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="small">{{ $record->student->student_number }}</td>
                        <td>
                            <div class="small fw-medium">{{ $record->student->formal_name }}</div>
                            <div class="text-muted" style="font-size: .78rem;">
                                {{ $record->student->department->department_code }} &middot;
                                {{ $record->student->year_level }}
                            </div>
                        </td>
                        <td class="small">
                            {{ $record->classModel->subject->subject_code }} &mdash;
                            {{ $record->classModel->section }}
                        </td>
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
                            No attendance records for this date and filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

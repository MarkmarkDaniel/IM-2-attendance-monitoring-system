@extends('layouts.app')

@section('title', 'My Classes')
@section('page-title', 'My Classes')
@section('page-subtitle', 'Classes assigned to you this term')

@section('content')

<div class="row g-3">
    @forelse ($classes as $class)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-dark">{{ $class->subject->subject_code }}</span>
                        <span class="badge bg-{{ $class->status === 'Active' ? 'success' : 'secondary' }}">
                            {{ $class->status }}
                        </span>
                    </div>

                    <h2 class="h6 mb-1">{{ $class->subject->subject_name }}</h2>
                    <p class="text-muted small mb-3">Section {{ $class->section }}</p>

                    <table class="table table-sm small mb-3">
                        <tbody>
                            <tr>
                                <td class="ps-0 text-muted" style="width: 90px;">Schedule</td>
                                <td>{{ $class->schedule_text }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-muted">Room</td>
                                <td>{{ $class->room ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-muted">Students</td>
                                <td>{{ $class->active_enrollments_count }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-muted">Sessions</td>
                                <td>{{ $class->attendance_sessions_count }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-grid gap-2">
                        <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-people me-1"></i>View Students
                        </a>
                        <a href="{{ route('instructor.attendance.record', $class) }}" class="btn btn-sm btn-brand">
                            <i class="bi bi-clipboard-check me-1"></i>Record Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-inbox d-block fs-1 mb-2"></i>
                    No classes are assigned to you yet. Contact the administrator.
                </div>
            </div>
        </div>
    @endforelse
</div>

@endsection

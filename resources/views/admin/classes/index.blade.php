@extends('layouts.app')

@section('title', 'Classes')
@section('page-title', 'Classes')
@section('page-subtitle', 'Manage class offerings and schedules')

@section('page-actions')
    <a href="{{ route('admin.classes.create') }}" class="btn btn-brand btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Class
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.classes.index') }}" class="row g-2">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Subject or section">
                </div>
            </div>
            <div class="col-md-3">
                <select name="instructor_id" class="form-select form-select-sm">
                    <option value="">All Instructors</option>
                    @foreach ($instructors as $instructor)
                        <option value="{{ $instructor->id }}"
                            {{ $instructorId == $instructor->id ? 'selected' : '' }}>
                            {{ $instructor->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="schedule_day" class="form-select form-select-sm">
                    <option value="">All Days</option>
                    @foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $dayOption)
                        <option value="{{ $dayOption }}" {{ $day === $dayOption ? 'selected' : '' }}>
                            {{ $dayOption }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                @if ($search || $instructorId || $day)
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Room</th>
                    <th class="text-center">Enrolled</th>
                    <th class="text-center">Sessions</th>
                    <th>Status</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td>
                            <div class="fw-medium small">{{ $class->subject->subject_code }}</div>
                            <div class="text-muted" style="font-size: .78rem;">
                                {{ Str::limit($class->subject->subject_name, 30) }}
                            </div>
                        </td>
                        <td class="small">{{ $class->section }}</td>
                        <td class="small">{{ $class->instructor->full_name }}</td>
                        <td class="small">{{ $class->schedule_text }}</td>
                        <td class="small">{{ $class->room ?? '—' }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.enrollments.manage', $class) }}"
                               class="badge bg-primary text-decoration-none">
                                {{ $class->enrollments_count }}
                            </a>
                        </td>
                        <td class="text-center">{{ $class->attendance_sessions_count }}</td>
                        <td>
                            <span class="badge bg-{{ $class->status === 'Active' ? 'success' : 'secondary' }}">
                                {{ $class->status }}
                            </span>
                        </td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.enrollments.manage', $class) }}"
                               class="btn btn-sm btn-outline-secondary" title="Manage enrollment">
                                <i class="bi bi-people"></i>
                            </a>
                            <a href="{{ route('admin.classes.edit', $class) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}"
                                  class="d-inline"
                                  data-confirm="Delete {{ $class->class_title }}? This also removes its enrollments.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>No classes found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($classes->hasPages())
        <div class="card-footer bg-white no-print">{{ $classes->links() }}</div>
    @endif

</div>

@endsection

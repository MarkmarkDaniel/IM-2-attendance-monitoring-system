@extends('layouts.app')

@section('title', 'Enrollments')
@section('page-title', 'Enrollments')
@section('page-subtitle', 'Select a class to manage its student roster')

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.enrollments.index') }}" class="row g-2">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Subject or section">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
                @if ($search)
                    <a href="{{ route('admin.enrollments.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                    <th class="text-center">Enrolled</th>
                    <th class="text-end no-print">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td>
                            <div class="fw-medium small">{{ $class->subject->subject_code }}</div>
                            <div class="text-muted" style="font-size: .78rem;">
                                {{ Str::limit($class->subject->subject_name, 35) }}
                            </div>
                        </td>
                        <td class="small">{{ $class->section }}</td>
                        <td class="small">{{ $class->instructor->full_name }}</td>
                        <td class="small">{{ $class->schedule_text }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ $class->enrollments_count }}</span>
                        </td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.enrollments.manage', $class) }}"
                               class="btn btn-sm btn-brand">
                                <i class="bi bi-people me-1"></i>Manage Roster
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
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

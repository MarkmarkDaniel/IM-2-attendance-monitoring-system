@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')
@section('page-subtitle', 'Manage academic departments')

@section('page-actions')
    <a href="{{ route('admin.departments.create') }}" class="btn btn-brand btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Department
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.departments.index') }}" class="row g-2">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Search code or name...">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
                @if ($search)
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Department Name</th>
                    <th class="text-center">Students</th>
                    <th class="text-center">Instructors</th>
                    <th class="text-center">Subjects</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td><span class="badge bg-dark">{{ $department->department_code }}</span></td>
                        <td>
                            <div class="fw-medium small">{{ $department->department_name }}</div>
                            @if ($department->description)
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ Str::limit($department->description, 80) }}
                                </div>
                            @endif
                        </td>
                        <td class="text-center">{{ $department->students_count }}</td>
                        <td class="text-center">{{ $department->instructors_count }}</td>
                        <td class="text-center">{{ $department->subjects_count }}</td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.departments.edit', $department) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('admin.departments.destroy', $department) }}"
                                  class="d-inline"
                                  data-confirm="Delete {{ $department->department_code }}? This cannot be undone.">
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                            @if ($search)
                                No departments matched "{{ $search }}".
                            @else
                                No departments yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($departments->hasPages())
        <div class="card-footer bg-white no-print">
            {{ $departments->links() }}
        </div>
    @endif

</div>

@endsection

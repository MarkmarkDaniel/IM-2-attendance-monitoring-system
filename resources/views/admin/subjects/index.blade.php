@extends('layouts.app')

@section('title', 'Subjects')
@section('page-title', 'Subjects')
@section('page-subtitle', 'Manage the subject catalog')

@section('page-actions')
    <a href="{{ route('admin.subjects.create') }}" class="btn btn-brand btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Subject
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.subjects.index') }}" class="row g-2">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Search code or name...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ $departmentId == $department->id ? 'selected' : '' }}>
                            {{ $department->department_code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                @if ($search || $departmentId)
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Department</th>
                    <th class="text-center">Units</th>
                    <th class="text-center">Classes</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subjects as $subject)
                    <tr>
                        <td><span class="badge bg-dark">{{ $subject->subject_code }}</span></td>
                        <td>
                            <div class="fw-medium small">{{ $subject->subject_name }}</div>
                            @if ($subject->description)
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ Str::limit($subject->description, 70) }}
                                </div>
                            @endif
                        </td>
                        <td class="small">{{ $subject->department->department_code }}</td>
                        <td class="text-center">{{ $subject->units }}</td>
                        <td class="text-center">{{ $subject->classes_count }}</td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.subjects.edit', $subject) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('admin.subjects.destroy', $subject) }}"
                                  class="d-inline"
                                  data-confirm="Delete {{ $subject->subject_code }}? This cannot be undone.">
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
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>No subjects found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($subjects->hasPages())
        <div class="card-footer bg-white no-print">
            {{ $subjects->links() }}
        </div>
    @endif

</div>

@endsection

@extends('layouts.app')

@section('title', 'Manage Roster')
@section('page-title', 'Manage Roster')
@section('page-subtitle', $class->subject->subject_code . ' — Section ' . $class->section)

@section('page-actions')
    <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Classes
    </a>
@endsection

@section('content')

{{-- ==================== CLASS INFO ==================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted">Subject</div>
                <div class="fw-medium">{{ $class->subject->subject_name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Instructor</div>
                <div class="fw-medium">{{ $class->instructor->full_name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Schedule</div>
                <div class="fw-medium">{{ $class->schedule_text }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Room</div>
                <div class="fw-medium">{{ $class->room ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ==================== ENROLLED STUDENTS ==================== --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">Enrolled Students</span>
                <span class="badge bg-primary">{{ $enrollments->count() }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th class="text-end no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="small">{{ $enrollment->student->student_number }}</td>
                                <td>
                                    <div class="small fw-medium">{{ $enrollment->student->formal_name }}</div>
                                    <div class="text-muted" style="font-size: .75rem;">
                                        {{ $enrollment->student->department->department_code }} &middot;
                                        {{ $enrollment->student->year_level }}
                                    </div>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('admin.enrollments.status', $enrollment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="enrollment_status"
                                                class="form-select form-select-sm"
                                                style="width: 120px;"
                                                onchange="this.form.submit()">
                                            @foreach (['Enrolled', 'Dropped', 'Completed'] as $state)
                                                <option value="{{ $state }}"
                                                    {{ $enrollment->enrollment_status === $state ? 'selected' : '' }}>
                                                    {{ $state }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end no-print">
                                    <form method="POST"
                                          action="{{ route('admin.enrollments.destroy', $enrollment) }}"
                                          data-confirm="Remove {{ $enrollment->student->full_name }} from this class?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-4">
                                    No students enrolled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== ADD STUDENTS ==================== --}}
    <div class="col-lg-5">
        <div class="card h-100 no-print">
            <div class="card-header bg-white fw-medium">Add Students</div>

            <div class="card-body">

                {{-- Filter the available list --}}
                <form method="GET" action="{{ route('admin.enrollments.manage', $class) }}" class="row g-2 mb-3">
                    <div class="col-7">
                        <input type="text" name="available_search" class="form-control form-control-sm"
                               value="{{ $availableSearch }}" placeholder="Search students...">
                    </div>
                    <div class="col-5">
                        <select name="available_department" class="form-select form-select-sm">
                            <option value="">All Depts</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                    {{ $availableDept == $department->id ? 'selected' : '' }}>
                                    {{ $department->department_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-secondary w-100">Filter Available</button>
                    </div>
                </form>

                {{-- Enrollment form --}}
                <form method="POST" action="{{ route('admin.enrollments.store', $class) }}">
                    @csrf

                    @if ($available->isEmpty())
                        <p class="text-muted small text-center py-4 mb-0">
                            No available students match your filter.
                        </p>
                    @else
                        <div class="form-check mb-2 pb-2 border-bottom">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label small fw-medium" for="checkAll">
                                Select all ({{ $available->count() }})
                            </label>
                        </div>

                        <div style="max-height: 320px; overflow-y: auto;">
                            @foreach ($available as $student)
                                <div class="form-check">
                                    <input class="form-check-input student-check" type="checkbox"
                                           name="student_ids[]" value="{{ $student->id }}"
                                           id="student{{ $student->id }}">
                                    <label class="form-check-label small" for="student{{ $student->id }}">
                                        {{ $student->formal_name }}
                                        <span class="text-muted d-block" style="font-size: .75rem;">
                                            {{ $student->student_number }} &middot;
                                            {{ $student->department->department_code }} &middot;
                                            {{ $student->year_level }} {{ $student->section }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-brand w-100 mt-3">
                            <i class="bi bi-person-plus me-1"></i>Enroll Selected Students
                        </button>
                    @endif
                </form>

            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('.student-check').forEach(box => {
            box.checked = this.checked;
        });
    });
</script>
@endpush

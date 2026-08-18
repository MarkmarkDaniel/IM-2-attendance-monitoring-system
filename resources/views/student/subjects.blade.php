@extends('layouts.app')

@section('title', 'My Subjects')
@section('page-title', 'My Subjects')
@section('page-subtitle', 'Subjects you are enrolled in this term')

@section('content')

<div class="row g-3">
    @forelse ($enrollments as $enrollment)
        @php
            $class   = $enrollment->classModel;
            $row     = $stats->get($class->id);
            $total   = (int) ($row->total_count ?? 0);
            $present = (int) ($row->present_count ?? 0);
            $late    = (int) ($row->late_count ?? 0);
            $absent  = (int) ($row->absent_count ?? 0);
            $rate    = \App\Models\Attendance::calculateRate($present, $late, $total);
            $minimum = config('attendance.minimum_rate', 75);
        @endphp

        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-dark">{{ $class->subject->subject_code }}</span>
                        <span class="badge bg-{{ $enrollment->enrollment_status === 'Enrolled' ? 'success' : 'secondary' }}">
                            {{ $enrollment->enrollment_status }}
                        </span>
                    </div>

                    <h2 class="h6 mb-1">{{ $class->subject->subject_name }}</h2>
                    <p class="text-muted small mb-3">
                        Section {{ $class->section }} &middot; {{ $class->subject->units }} units
                    </p>

                    <table class="table table-sm small mb-3">
                        <tbody>
                            <tr>
                                <td class="ps-0 text-muted" style="width: 90px;">Instructor</td>
                                <td>{{ $class->instructor->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-muted">Schedule</td>
                                <td>{{ $class->schedule_text }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-muted">Room</td>
                                <td>{{ $class->room ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">My Attendance</span>
                            @if ($total === 0)
                                <span class="badge bg-secondary">No records</span>
                            @else
                                <span class="badge bg-{{ $rate >= $minimum ? 'success' : 'danger' }}">
                                    {{ $rate }}%
                                </span>
                            @endif
                        </div>

                        <div class="d-flex gap-3 small">
                            <span class="text-success"><i class="bi bi-check-circle me-1"></i>{{ $present }} Present</span>
                            <span class="text-warning"><i class="bi bi-clock me-1"></i>{{ $late }} Late</span>
                            <span class="text-danger"><i class="bi bi-x-circle me-1"></i>{{ $absent }} Absent</span>
                        </div>
                    </div>

                    <a href="{{ route('student.attendance', ['class_id' => $class->id]) }}"
                       class="btn btn-sm btn-outline-secondary w-100 mt-3">
                        <i class="bi bi-list-check me-1"></i>View My Records
                    </a>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-inbox d-block fs-1 mb-2"></i>
                    You are not enrolled in any subjects yet. Contact the registrar.
                </div>
            </div>
        </div>
    @endforelse
</div>

@endsection

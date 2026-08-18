@extends('layouts.app')

@section('title', 'QR Attendance')
@section('page-title', 'QR Attendance')
@section('page-subtitle', 'Start a session and display the QR code for students to scan')

@section('content')

<div class="row g-3">
    @forelse ($classes as $class)
        @php $session = $sessions->get($class->id); @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-dark">{{ $class->subject->subject_code }}</span>
                        @if ($session && $session->isOpen())
                            <span class="badge bg-success">
                                <i class="bi bi-broadcast me-1"></i>Live
                            </span>
                        @elseif ($session)
                            <span class="badge bg-secondary">Closed today</span>
                        @else
                            <span class="badge bg-light text-dark border">No session</span>
                        @endif
                    </div>

                    <h2 class="h6 mb-1">{{ $class->subject->subject_name }}</h2>
                    <p class="text-muted small mb-3">
                        Section {{ $class->section }} &middot; {{ $class->schedule_text }}
                    </p>

                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span><i class="bi bi-people me-1"></i>{{ $class->active_enrollments_count }} enrolled</span>
                        <span><i class="bi bi-door-open me-1"></i>{{ $class->room ?? 'No room' }}</span>
                    </div>

                    @if ($session)
                        <a href="{{ route('instructor.qr.show', $class) }}" class="btn btn-brand w-100">
                            <i class="bi bi-qr-code me-1"></i>
                            {{ $session->isOpen() ? 'Open QR Display' : 'View Session' }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('instructor.sessions.open', $class) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-play-fill me-1"></i>Start Attendance
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-inbox d-block fs-1 mb-2"></i>
                    No active classes are assigned to you.
                </div>
            </div>
        </div>
    @endforelse
</div>

@endsection

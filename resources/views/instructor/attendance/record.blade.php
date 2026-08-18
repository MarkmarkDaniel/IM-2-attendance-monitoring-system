@extends('layouts.app')

@section('title', 'Record Attendance')
@section('page-title', 'Record Attendance')
@section('page-subtitle', $class->subject->subject_code . ' — Section ' . $class->section)

@section('page-actions')
    <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Class
    </a>
@endsection

@section('content')

{{-- ==================== DATE PICKER ==================== --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('instructor.attendance.record', $class) }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="date" class="form-label small">Attendance Date</label>
                <input type="date" class="form-control form-control-sm"
                       id="date" name="date" value="{{ $date }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Load Date</button>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="small text-muted">
                    Class starts at
                    <strong>{{ \Carbon\Carbon::parse($class->start_time)->format('g:i A') }}</strong>
                    &middot; grace period
                    <strong>{{ config('attendance.late_grace_minutes') }} min</strong>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ==================== NO SESSION YET ==================== --}}
@if (! $session)
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-calendar-plus fs-1 text-muted d-block mb-3"></i>
            <h2 class="h5">No session for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</h2>
            <p class="text-muted small">
                Start a session before recording attendance. This also generates the QR code.
            </p>
            <form method="POST" action="{{ route('instructor.sessions.open', $class) }}" class="d-inline">
                @csrf
                <input type="hidden" name="session_date" value="{{ $date }}">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-play-fill me-1"></i>Start Attendance Session
                </button>
            </form>
        </div>
    </div>

@else

    {{-- ==================== SESSION STATUS BAR ==================== --}}
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap gap-4 small">
                <div>
                    <div class="text-muted">Session Date</div>
                    <div class="fw-medium">{{ $session->session_date->format('F j, Y') }}</div>
                </div>
                <div>
                    <div class="text-muted">Status</div>
                    <span class="badge bg-{{ $session->isOpen() ? 'success' : 'secondary' }}">
                        {{ $session->status }}
                    </span>
                </div>
                <div>
                    <div class="text-muted">Checked In</div>
                    <div class="fw-medium">
                        {{ $session->checkedInCount() }} / {{ $enrollments->count() }}
                    </div>
                </div>
            </div>

            <div class="no-print d-flex gap-2">
                @if ($session->isOpen())
                    <form method="POST" action="{{ route('instructor.sessions.close', $session) }}"
                          data-confirm="Close this session? Every student without a record will be marked Absent.">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-stop-circle me-1"></i>Close Session
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('instructor.sessions.open', $class) }}"
                          data-confirm="Reopen this session? A new QR token will be generated.">
                        @csrf
                        <input type="hidden" name="session_date" value="{{ $date }}">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reopen Session
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ==================== ATTENDANCE SHEET ==================== --}}
    <form method="POST" action="{{ route('instructor.attendance.store', $class) }}">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        <div class="card">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-medium">Attendance Sheet</span>
                <div class="no-print d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" id="markAllPresent">
                        <i class="bi bi-check-all me-1"></i>Mark All Present
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAll">
                        Clear All
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Student No.</th>
                            <th>Student Name</th>
                            <th style="width: 130px;">Time In</th>
                            <th style="width: 240px;">Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enrollment)
                            @php
                                $student = $enrollment->student;
                                $record  = $existing->get($student->id);
                                $timeIn  = $record?->time_in
                                            ? \Carbon\Carbon::parse($record->time_in)->format('H:i')
                                            : '';
                            @endphp
                            <tr>
                                <input type="hidden" name="records[{{ $index }}][student_id]"
                                       value="{{ $student->id }}">

                                <td class="small">{{ $student->student_number }}</td>

                                <td>
                                    <div class="small fw-medium">{{ $student->formal_name }}</div>
                                    @if ($record)
                                        <span class="badge bg-{{ $record->status_badge }}"
                                              style="font-size: .68rem;">Saved: {{ $record->status }}</span>
                                    @endif
                                </td>

                                <td>
                                    <input type="time"
                                           class="form-control form-control-sm time-input"
                                           name="records[{{ $index }}][time_in]"
                                           value="{{ $timeIn }}"
                                           data-row="{{ $index }}">
                                </td>

                                <td>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        @foreach (['Present' => 'success', 'Late' => 'warning', 'Absent' => 'danger'] as $state => $color)
                                            <input type="radio" class="btn-check status-radio"
                                                   name="records[{{ $index }}][status]"
                                                   id="s{{ $index }}{{ $state }}"
                                                   value="{{ $state }}"
                                                   data-row="{{ $index }}"
                                                   data-status="{{ $state }}"
                                                   {{ ($record?->status ?? 'Present') === $state ? 'checked' : '' }}>
                                            <label class="btn btn-outline-{{ $color }}"
                                                   for="s{{ $index }}{{ $state }}">{{ $state }}</label>
                                        @endforeach
                                    </div>
                                </td>

                                <td>
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           name="records[{{ $index }}][remarks]"
                                           value="{{ $record->remarks ?? '' }}"
                                           placeholder="Optional"
                                           maxlength="255">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No students are enrolled in this class yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($enrollments->isNotEmpty())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center no-print">
                    <span class="small text-muted">
                        Times entered are checked against the class start time automatically.
                    </span>
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-save me-1"></i>Save Attendance
                    </button>
                </div>
            @endif
        </div>
    </form>

@endif

@endsection

@push('scripts')
<script>
    // The late rule, mirrored from config/attendance.php so the form can
    // preview it. The server recomputes on save — this is only a helper.
    const CLASS_START  = '{{ \Carbon\Carbon::parse($class->start_time)->format('H:i') }}';
    const GRACE_MIN    = {{ (int) config('attendance.late_grace_minutes') }};

    function toMinutes(hhmm) {
        if (!hhmm) return null;
        const [h, m] = hhmm.split(':').map(Number);
        return h * 60 + m;
    }

    const cutoff = toMinutes(CLASS_START) + GRACE_MIN;

    function pickStatus(row, status) {
        const radio = document.querySelector(
            `.status-radio[data-row="${row}"][data-status="${status}"]`
        );
        if (radio) radio.checked = true;
    }

    // Typing a time auto-selects Present or Late
    document.querySelectorAll('.time-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const row = this.dataset.row;
            const mins = toMinutes(this.value);

            if (mins === null) return;

            pickStatus(row, mins <= cutoff ? 'Present' : 'Late');
        });
    });

    // Mark All Present: fill the current time and select Present
    document.getElementById('markAllPresent')?.addEventListener('click', function () {
        const now = new Date();
        const hh  = String(now.getHours()).padStart(2, '0');
        const mm  = String(now.getMinutes()).padStart(2, '0');
        const timeValue = `${hh}:${mm}`;
        const isLate = toMinutes(timeValue) > cutoff;

        document.querySelectorAll('.time-input').forEach(function (input) {
            input.value = timeValue;
            pickStatus(input.dataset.row, isLate ? 'Late' : 'Present');
        });
    });

    // Clear All: blank the times and select Absent
    document.getElementById('clearAll')?.addEventListener('click', function () {
        document.querySelectorAll('.time-input').forEach(function (input) {
            input.value = '';
            pickStatus(input.dataset.row, 'Absent');
        });
    });
</script>
@endpush

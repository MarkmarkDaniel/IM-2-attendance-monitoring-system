@extends('layouts.app')

@section('title', 'QR Attendance Display')
@section('page-title', $class->subject->subject_code . ' — Section ' . $class->section)
@section('page-subtitle', $class->subject->subject_name)

@section('page-actions')
    <a href="{{ route('instructor.qr.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
@endsection

@section('content')

@if (! $session)

    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-qr-code fs-1 text-muted d-block mb-3"></i>
            <h2 class="h5">No session for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</h2>
            <p class="text-muted small">Start a session to generate the QR code.</p>
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

<div class="row g-3">

    {{-- ==================== QR CODE ==================== --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">Attendance QR Code</span>
                <span class="badge bg-{{ $session->isOpen() ? 'success' : 'secondary' }}" id="sessionBadge">
                    {{ $session->status }}
                </span>
            </div>

            <div class="card-body text-center">

                @if ($session->isOpen() && $scanUrl)
                    <div class="d-inline-block p-3 bg-white border rounded mb-3">
                        {!! QrCode::size(280)->margin(1)->errorCorrection('M')->generate($scanUrl) !!}
                    </div>

                    <p class="small text-muted mb-1">
                        Students scan this with their phone camera or the in-app scanner.
                    </p>
                    <p class="small text-muted mb-0">
                        The code contains only a random token &mdash; no personal information.
                    </p>
                @else
                    <div class="py-5">
                        <i class="bi bi-qr-code-scan fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted mb-3">
                            This session is closed. The QR code has expired and can no longer be scanned.
                        </p>
                        <form method="POST" action="{{ route('instructor.sessions.open', $class) }}"
                              data-confirm="Reopen this session? A new QR token will be generated.">
                            @csrf
                            <input type="hidden" name="session_date" value="{{ $date }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reopen Session
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ==================== SESSION INFO + COUNTERS ==================== --}}
    <div class="col-lg-6">

        <div class="card mb-3">
            <div class="card-header bg-white fw-medium">Session Details</div>
            <div class="card-body">
                <table class="table table-sm small mb-0">
                    <tbody>
                        <tr>
                            <td class="ps-0 text-muted" style="width: 130px;">Subject</td>
                            <td>{{ $class->subject->subject_code }} &mdash; {{ $class->subject->subject_name }}</td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">Class / Section</td>
                            <td>{{ $class->section }} &middot; {{ $class->room ?? 'No room' }}</td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">Date</td>
                            <td>{{ $session->session_date->format('l, F j, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">Class Starts</td>
                            <td>{{ \Carbon\Carbon::parse($class->start_time)->format('g:i A') }}
                                <span class="text-muted">(+{{ config('attendance.late_grace_minutes') }} min grace)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">Current Time</td>
                            <td><span id="liveClockQr" class="fw-medium"></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100 text-center">
                    <div class="card-body py-3">
                        <div class="stat-value" id="statCheckedIn">{{ $session->checkedInCount() }}</div>
                        <div class="stat-label">Checked In</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100 text-center border-success border-2">
                    <div class="card-body py-3">
                        <div class="stat-value text-success" id="statPresent">{{ $session->presentCount() }}</div>
                        <div class="stat-label">Present</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100 text-center border-warning border-2">
                    <div class="card-body py-3">
                        <div class="stat-value text-warning" id="statLate">{{ $session->lateCount() }}</div>
                        <div class="stat-label">Late</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card h-100 text-center border-danger border-2">
                    <div class="card-body py-3">
                        <div class="stat-value text-danger" id="statRemaining">
                            {{ max(0, $enrolledCount - $session->checkedInCount()) }}
                        </div>
                        <div class="stat-label">Remaining</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">Recent Check-Ins</span>
                @if ($session->isOpen())
                    <span class="small text-muted">
                        <i class="bi bi-arrow-repeat me-1"></i>Auto-refreshing
                    </span>
                @endif
            </div>
            <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                <table class="table table-sm mb-0">
                    <tbody id="recentBody">
                        <tr>
                            <td class="text-center text-muted small py-4">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if ($session->isOpen())
            <form method="POST" action="{{ route('instructor.sessions.close', $session) }}"
                  class="mt-3 no-print"
                  data-confirm="Close this session? The QR code will stop working and every student without a record will be marked Absent.">
                @csrf
                <button type="submit" class="btn btn-danger w-100 py-2">
                    <i class="bi bi-stop-circle me-1"></i>Close Attendance Session
                </button>
            </form>
        @endif

    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
    // ---------- Live clock ----------
    function tickQrClock() {
        const el = document.getElementById('liveClockQr');
        if (!el) return;
        el.textContent = new Date().toLocaleTimeString('en-PH', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }
    tickQrClock();
    setInterval(tickQrClock, 1000);

    @if ($session)
    // ---------- Live counters ----------
    const STATS_URL = '{{ route('instructor.qr.stats', $session) }}';

    const badgeClass = {
        Present: 'bg-success',
        Late:    'bg-warning',
        Absent:  'bg-danger'
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function refreshStats() {
        try {
            const response = await fetch(STATS_URL, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) return;

            const data = await response.json();

            document.getElementById('statCheckedIn').textContent = data.checkedIn;
            document.getElementById('statPresent').textContent   = data.present;
            document.getElementById('statLate').textContent      = data.late;
            document.getElementById('statRemaining').textContent = data.remaining;

            const body = document.getElementById('recentBody');

            if (data.recent.length === 0) {
                body.innerHTML =
                    '<tr><td class="text-center text-muted small py-4">No check-ins yet.</td></tr>';
                return;
            }

            body.innerHTML = data.recent.map(function (row) {
                return `<tr>
                    <td>
                        <div class="small fw-medium">${escapeHtml(row.name)}</div>
                        <div class="text-muted" style="font-size:.75rem;">${escapeHtml(row.number)}</div>
                    </td>
                    <td class="small text-end">${escapeHtml(row.time)}</td>
                    <td class="text-end" style="width:90px;">
                        <span class="badge ${badgeClass[row.status] || 'bg-secondary'}">${escapeHtml(row.status)}</span>
                    </td>
                </tr>`;
            }).join('');

        } catch (error) {
            console.error('Stats refresh failed:', error);
        }
    }

    refreshStats();

    @if ($session->isOpen())
        setInterval(refreshStats, 5000);
    @endif
    @endif
</script>
@endpush

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

                    <div class="d-inline-block p-3 bg-white border rounded mb-3" id="qrHolder">
                        {!! QrCode::size(280)->margin(1)->errorCorrection('M')->generate($scanUrl) !!}
                    </div>

                    {{-- ---------- Rotation countdown ---------- --}}
                    @if (config('attendance.qr_rotate_seconds') > 0)
                        <div class="mb-3">
                            <div class="progress mx-auto" style="max-width: 280px; height: 6px;">
                                <div class="progress-bar bg-success" id="qrTimer" style="width: 100%;"></div>
                            </div>
                            <p class="small text-muted mt-2 mb-0">
                                <i class="bi bi-shield-lock me-1"></i>
                                This code changes every
                                <strong>{{ config('attendance.qr_rotate_seconds') }} seconds</strong>.
                                A screenshot stops working almost immediately.
                            </p>
                        </div>
                    @endif

                    <p class="small text-muted mb-1">
                        Students scan this with their phone camera or the in-app scanner.
                    </p>
                    <p class="small text-muted mb-3">
                        The code contains only a random token &mdash; no personal information.
                    </p>

                    {{-- ---------- Manual entry fallback ---------- --}}
                    <div class="border-top pt-3">
                        <label class="form-label small text-muted">
                            Manual entry code (changes with the QR)
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace"
                                   id="tokenField" value="{{ $session->qr_token }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyToken">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            For students whose camera will not open in the browser.
                        </div>
                    </div>

                @else

                    <div class="py-5">
                        <i class="bi bi-qr-code-scan fs-1 text-muted d-block mb-3"></i>
                        <p class="text-muted mb-3">
                            This session is closed. The QR code has expired and can no longer be scanned.
                        </p>
                        <form method="POST" action="{{ route('instructor.sessions.open', $class) }}"
                              data-confirm="Reopen this session? A new QR token will be generated."
                              data-confirm-title="Reopen Attendance Session"
                              data-confirm-style="btn-secondary">
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
                            <td>
                                {{ \Carbon\Carbon::parse($class->start_time)->format('g:i A') }}
                                <span class="text-muted">
                                    (+{{ config('attendance.late_grace_minutes') }} min grace)
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">Current Time</td>
                            <td><span id="liveClockQr" class="fw-medium"></span></td>
                        </tr>
                        <tr>
                            <td class="ps-0 text-muted">QR Security</td>
                            <td>
                                @if (config('attendance.qr_rotate_seconds') > 0)
                                    <span class="badge bg-success">
                                        Rotating every {{ config('attendance.qr_rotate_seconds') }}s
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Static token</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ---------- Live counters ---------- --}}
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

        {{-- ---------- Recent check-ins ---------- --}}
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
                  data-confirm="Close this session? The QR code will stop working and every student without a record will be marked Absent."
                  data-confirm-title="Close Attendance Session">
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


    // ---------- Rotating QR code ----------
    @if ($session && $session->isOpen() && config('attendance.qr_rotate_seconds') > 0)
    (function () {
        const REFRESH_URL = '{{ route('instructor.qr.refresh', $session) }}';
        const ROTATE_SECS = {{ (int) config('attendance.qr_rotate_seconds') }};

        const holder  = document.getElementById('qrHolder');
        const timer   = document.getElementById('qrTimer');
        const tokenEl = document.getElementById('tokenField');

        if (!holder) return;

        let remaining = ROTATE_SECS;
        let busy      = false;

        async function refreshQr() {
            if (busy) return;
            busy = true;

            try {
                const response = await fetch(REFRESH_URL, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    // 410 means the session was closed from another tab
                    if (response.status === 410) location.reload();
                    return;
                }

                const data = await response.json();

                if (!data.ok) return;

                holder.innerHTML = data.svg;
                if (tokenEl) tokenEl.value = data.token;

                remaining = data.expires_in ?? ROTATE_SECS;

            } catch (error) {
                console.error('QR refresh failed:', error);
            } finally {
                busy = false;
            }
        }

        // Countdown bar, ticking once per second
        setInterval(function () {
            remaining -= 1;

            if (timer) {
                const pct = Math.max(0, (remaining / ROTATE_SECS) * 100);
                timer.style.width = pct + '%';
                timer.className = 'progress-bar ' +
                    (pct > 50 ? 'bg-success' : pct > 20 ? 'bg-warning' : 'bg-danger');
            }

            if (remaining <= 0) {
                refreshQr();
                remaining = ROTATE_SECS;
            }
        }, 1000);
    })();
    @endif


    // ---------- Copy the manual entry token ----------
    document.getElementById('copyToken')?.addEventListener('click', function () {
        const field = document.getElementById('tokenField');
        if (!field) return;

        field.select();
        field.setSelectionRange(0, 99999);
        navigator.clipboard?.writeText(field.value);

        this.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(() => { this.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
    });
</script>
@endpush

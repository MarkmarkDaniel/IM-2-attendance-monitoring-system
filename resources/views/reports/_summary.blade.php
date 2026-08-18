<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-label">Total Records</div>
                <div class="stat-value">{{ number_format($summary['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-success border-2">
            <div class="card-body">
                <div class="stat-label">Present</div>
                <div class="stat-value text-success">{{ number_format($summary['present']) }}</div>
                <div class="small text-muted">{{ $summary['present_pct'] }}%</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-warning border-2">
            <div class="card-body">
                <div class="stat-label">Late</div>
                <div class="stat-value text-warning">{{ number_format($summary['late']) }}</div>
                <div class="small text-muted">{{ $summary['late_pct'] }}%</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100 border-danger border-2">
            <div class="card-body">
                <div class="stat-label">Absent</div>
                <div class="stat-value text-danger">{{ number_format($summary['absent']) }}</div>
                <div class="small text-muted">{{ $summary['absent_pct'] }}%</div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border small">
    <i class="bi bi-graph-up me-1"></i>
    <strong>Attendance Rate: {{ $summary['rate'] }}%</strong>
    &mdash; ((Present + Late) ÷ Total Records) × 100
</div>

@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Attendance Reports')
@section('page-subtitle', 'Generate, print, and export attendance reports')

@section('content')

@php
    $prefix = $isAdmin ? 'admin' : 'instructor';

    $reports = [
        [
            'route' => $prefix . '.reports.daily',
            'icon'  => 'bi-calendar-day',
            'color' => 'primary',
            'title' => 'Daily Attendance Report',
            'desc'  => 'All attendance records for a single date. Filter by class, subject, and status.',
            'admin' => false,
        ],
        [
            'route' => $prefix . '.reports.student',
            'icon'  => 'bi-person-lines-fill',
            'color' => 'info',
            'title' => 'Student Attendance Report',
            'desc'  => 'One student\'s full attendance history, filtered by subject and date range.',
            'admin' => false,
        ],
        [
            'route' => $prefix . '.reports.class',
            'icon'  => 'bi-easel',
            'color' => 'success',
            'title' => 'Class Attendance Report',
            'desc'  => 'Totals and attendance rate per class offering, with a per-student breakdown.',
            'admin' => false,
        ],
        [
            'route' => 'admin.reports.department',
            'icon'  => 'bi-building',
            'color' => 'dark',
            'title' => 'Department Attendance Report',
            'desc'  => 'Attendance statistics grouped by department.',
            'admin' => true,
        ],
        [
            'route' => $prefix . '.reports.monthly',
            'icon'  => 'bi-calendar-month',
            'color' => 'secondary',
            'title' => 'Monthly Attendance Report',
            'desc'  => 'Day-by-day totals and trend charts for a selected month and year.',
            'admin' => false,
        ],
    ];
@endphp

<div class="row g-3">
    @foreach ($reports as $report)
        @continue($report['admin'] && ! $isAdmin)

        <div class="col-md-6 col-xl-4">
            <a href="{{ route($report['route']) }}" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="stat-icon bg-{{ $report['color'] }} bg-opacity-10 text-{{ $report['color'] }} mb-3">
                            <i class="bi {{ $report['icon'] }}"></i>
                        </div>
                        <h2 class="h6 text-dark mb-2">{{ $report['title'] }}</h2>
                        <p class="small text-muted mb-0">{{ $report['desc'] }}</p>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0">
                        <span class="small text-primary">
                            Open report <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="alert alert-light border small mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Every report supports <strong>Print</strong>, <strong>CSV export</strong>, and
    <strong>PDF export</strong>. All figures are queried live from MySQL &mdash;
    nothing is cached or hardcoded.
</div>

@endsection

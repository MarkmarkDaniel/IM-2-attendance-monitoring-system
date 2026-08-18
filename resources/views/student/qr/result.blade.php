@extends('layouts.app')

@section('title', 'Attendance Result')
@section('page-title', 'Attendance Result')

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6">

        <div class="card">
            <div class="card-body text-center p-5">

                @if ($result['ok'])
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="h4 mt-3 mb-2">Attendance Recorded</h2>

                    <span class="badge bg-{{ $result['status'] === 'Present' ? 'success' : 'warning' }} fs-6 mb-3">
                        {{ strtoupper($result['status']) }}
                    </span>

                    <table class="table table-sm small mx-auto mb-4" style="max-width: 320px;">
                        <tbody>
                            <tr>
                                <td class="text-muted text-start">Subject</td>
                                <td class="text-end fw-medium">{{ $result['subject'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-start">Section</td>
                                <td class="text-end fw-medium">{{ $result['section'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-start">Time In</td>
                                <td class="text-end fw-medium">{{ $result['time_in'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-start">Date</td>
                                <td class="text-end fw-medium">{{ now()->format('F j, Y') }}</td>
                            </tr>
                        </tbody>
                    </table>

                @else
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                    <h2 class="h4 mt-3 mb-2">Scan Failed</h2>
                    <p class="text-muted">{{ $result['message'] }}</p>
                @endif

                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('student.dashboard') }}" class="btn btn-brand">
                        <i class="bi bi-house me-1"></i>Back to Dashboard
                    </a>
                    <a href="{{ route('student.qr.scanner') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-camera me-1"></i>Scan Again
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') &mdash; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: #f1f4f9;
            display: flex;
            align-items: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(16, 32, 62, .08);
        }
        .auth-header {
            background: #14304f;
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 1.75rem;
        }
        .auth-header i { font-size: 2rem; }
        .form-label { font-weight: 500; font-size: .9rem; }
        .btn-brand { background: #14304f; color: #fff; }
        .btn-brand:hover { background: #1d4570; color: #fff; }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 @yield('column-width', 'col-md-6 col-lg-5')">

                <div class="card auth-card">
                    <div class="auth-header text-center">
                        <i class="bi bi-qr-code-scan"></i>
                        <h1 class="h5 mt-2 mb-1">Attendance Monitoring System</h1>
                        <p class="small mb-0 opacity-75">@yield('subtitle', 'Sign in to your account')</p>
                    </div>

                    <div class="card-body p-4">

                        {{-- Flash messages --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @yield('content')

                    </div>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">
                    {{ config('app.name') }} &copy; {{ date('Y') }}
                </p>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>

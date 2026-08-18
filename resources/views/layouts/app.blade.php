<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #14304f;
            --brand-light: #1d4570;
        }
        body { background: #f1f4f9; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            background: var(--brand);
            width: 250px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
        }
        .sidebar .brand {
            padding: 1.15rem 1.25rem;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, .12);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, .72);
            padding: .6rem 1.25rem;
            font-size: .92rem;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .06);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .1);
            border-left-color: #4da3ff;
            font-weight: 500;
        }
        .sidebar .nav-link i { width: 20px; }
        .sidebar .nav-heading {
            color: rgba(255, 255, 255, .4);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 1rem 1.25rem .35rem;
        }

        /* ---------- Main area ---------- */
        .main-wrap { margin-left: 250px; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform .25s; }
            .sidebar.show { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e3e8ef;
            padding: .7rem 1.5rem;
        }

        /* ---------- Cards ---------- */
        .card { border: 1px solid #e3e8ef; border-radius: 10px; }
        .stat-card .stat-icon {
            width: 46px; height: 46px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 600; line-height: 1.1; }
        .stat-card .stat-label { font-size: .82rem; color: #6b7a90; }

        .notif-dropdown { width: 340px; max-height: 400px; overflow-y: auto; }
        .notif-item { border-bottom: 1px solid #f0f2f5; }
        .notif-item.unread { background: #f5f9ff; }

        .table > :not(caption) > * > * { padding: .65rem .75rem; }
        .btn-brand { background: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-light); color: #fff; }

        /* ---------- Mobile refinements ---------- */
        @media (max-width: 767.98px) {
            main.p-4 { padding: 1rem !important; }
            .stat-card .stat-value { font-size: 1.35rem; }
            .table { font-size: .85rem; }
            .card-header .btn { font-size: .78rem; }
            h1.h4 { font-size: 1.15rem; }
        }

        /* Backdrop when the sidebar is open on mobile */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .4);
            z-index: 1029;
            display: none;
        }
        .sidebar-backdrop.show { display: block; }

        /* ---------- Accessibility ---------- */
        .nav-link:focus-visible,
        .btn:focus-visible,
        .form-control:focus-visible {
            outline: 2px solid #4da3ff;
            outline-offset: 2px;
        }

        /* Sticky table headers on long report tables */
        .table-responsive { max-height: none; }
        .table-sticky thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* ---------- Print ---------- */
        @media print {
            .sidebar, .topbar, .no-print, .card-footer { display: none !important; }
            .main-wrap { margin-left: 0 !important; }
            body { background: #fff; }
            .card { border: 1px solid #ccc !important; box-shadow: none !important; break-inside: avoid; }
            .card-header { background: #f5f5f5 !important; }
            .badge { border: 1px solid #999; color: #000 !important; background: #fff !important; }
            .table { font-size: 11px; }
            canvas { max-height: 200px !important; }
            a[href]:after { content: none !important; }
            @page { margin: 1.5cm; size: landscape; }
        }
        .d-print-block { display: none; }
        @media print { .d-print-block { display: block !important; } }
    </style>
    @stack('styles')
</head>
<body>

    @include('partials.sidebar')

    <div class="main-wrap">

        @include('partials.navbar')

        <main class="p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h4 mb-0">@yield('page-title')</h1>
                    <p class="text-muted small mb-0">@yield('page-subtitle')</p>
                </div>
                <div class="no-print">@yield('page-actions')</div>
            </div>

            @include('partials.alerts')

            @yield('content')
        </main>

    </div>

    @include('partials.confirm-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <script>
        // ---------- Mobile sidebar with backdrop ----------
        (function () {
            const sidebar = document.getElementById('sidebar');
            const toggle  = document.getElementById('sidebarToggle');
            if (!sidebar || !toggle) return;

            const backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);

            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            }

            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });

            backdrop.addEventListener('click', closeSidebar);

            // Close on Escape
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeSidebar();
            });

            // Close after tapping a link
            sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
        })();

        // ---------- Live clock in the navbar ----------
        function tickClock() {
            const el = document.getElementById('liveClock');
            if (!el) return;
            el.textContent = new Date().toLocaleTimeString('en-PH', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
        tickClock();
        setInterval(tickClock, 1000);

        // ---------- Bootstrap confirm modal ----------
        (function () {
            const modalEl = document.getElementById('confirmModal');
            if (!modalEl) return;

            const modal     = new bootstrap.Modal(modalEl);
            const bodyEl    = document.getElementById('confirmModalBody');
            const titleEl   = document.getElementById('confirmModalTitle');
            const okBtn     = document.getElementById('confirmModalOk');
            const spinnerEl = document.getElementById('confirmModalSpinner');

            let pendingForm = null;

            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === 'yes') return;

                    event.preventDefault();

                    pendingForm         = form;
                    bodyEl.textContent  = form.dataset.confirm;
                    titleEl.textContent = form.dataset.confirmTitle || 'Please Confirm';

                    okBtn.className = 'btn ' + (form.dataset.confirmStyle || 'btn-danger');
                    spinnerEl.classList.add('d-none');
                    okBtn.disabled = false;

                    modal.show();
                });
            });

            okBtn.addEventListener('click', function () {
                if (!pendingForm) return;

                spinnerEl.classList.remove('d-none');
                okBtn.disabled = true;

                pendingForm.dataset.confirmed = 'yes';
                pendingForm.submit();
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                pendingForm = null;
            });
        })();

        // ---------- Disable submit buttons after click ----------
        document.querySelectorAll('form:not([data-confirm])').forEach(function (form) {
            form.addEventListener('submit', function () {
                const button = form.querySelector('button[type="submit"]');
                if (!button || button.dataset.noSpinner) return;

                button.disabled = true;
                button.insertAdjacentHTML('afterbegin',
                    '<span class="spinner-border spinner-border-sm me-1"></span>');

                // Re-enable if the browser restores the page from cache
                setTimeout(() => { button.disabled = false; }, 8000);
            });
        });

        // ---------- Auto-dismiss success alerts ----------
        setTimeout(function () {
            document.querySelectorAll('.alert-success.alert-dismissible').forEach(function (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 6000);
    </script>

    @stack('scripts')
</body>
</html>

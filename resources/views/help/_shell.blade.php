<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8"><title>@yield('title') — BOMWH PMMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <script>
        (function () {
            const saved = localStorage.getItem('pmms-theme')
                || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', saved);
        })();
    </script>
    <style>
        body { font-family: 'Barlow', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .help-hero { background: linear-gradient(135deg, #0A4A3A, #0F6E56); color: #fff; }
        @media print { .help-hero, .app-footer, .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="help-hero py-4 px-3 no-print">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-white-50">Home</a></li>
            <li class="breadcrumb-item active text-white">@yield('title')</li>
        </ol></nav>
        <h1 class="fs-3 mb-0">@yield('title')</h1>
        <p class="mb-0 text-white-50 small">Borno State Ministry of Works and Housing — Project Monitoring &amp; Management System</p>
    </div>
</div>
<div class="container py-4" style="max-width: 960px">
    @yield('body')
    <div class="text-end mt-3 no-print">
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print this page</button>
    </div>
</div>
@include('partials.footer')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

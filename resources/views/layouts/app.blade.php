<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'PMMS') — BOMWH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <script>
        // Apply saved theme before paint to avoid a flash
        (function () {
            const saved = localStorage.getItem('pmms-theme')
                || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', saved);
            if (localStorage.getItem('pmms-sidebar') === 'collapsed') {
                document.addEventListener('DOMContentLoaded', () => document.body.classList.add('sidebar-collapsed'));
            }
        })();
    </script>
    @stack('head')
    <style>
        :root {
            --bs-body-font-family: 'Barlow', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --pmms-green: #0F6E56;
            --pmms-green-dark: #0A4A3A;
            --pmms-gold: #C9A227;
            --pmms-radius: 12px;
        }
        body { min-height: 100vh; display: flex; flex-direction: column; font-family: 'Barlow', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .app-shell { display: flex; flex: 1 0 auto; }
        .app-shell > main { min-width: 0; } /* lets charts/tables shrink back when the sidebar re-expands */
        .sidebar { width: 240px; flex-shrink: 0; background: linear-gradient(180deg, var(--pmms-green-dark) 0%, #062E24 100%); }
        .sidebar .nav-link { color: rgba(255,255,255,.72); border-radius: 8px; transition: background .18s ease, color .18s ease, transform .18s ease; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); transform: translateX(2px); }
        .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.14); }
        .card { border-radius: var(--pmms-radius); border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04); transition: box-shadow .22s ease, transform .22s ease; animation: fadeUp .4s ease both; }
        .card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.10); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .card { animation: none; } * { transition: none !important; } }
        .btn { border-radius: 8px; transition: transform .12s ease, box-shadow .12s ease; }
        .btn:active { transform: scale(.97); }
        .form-control, .form-select { border-radius: 8px; transition: border-color .18s ease, box-shadow .18s ease; }
        .form-control:focus, .form-select:focus { border-color: var(--pmms-green); box-shadow: 0 0 0 .2rem rgba(15,110,86,.15); }
        .table-hover tbody tr { transition: background .15s ease; }
        [data-bs-theme="dark"] .sidebar { background: linear-gradient(180deg, #0B1F1A 0%, #06110E 100%); }
        [data-bs-theme="dark"] .card { box-shadow: 0 1px 3px rgba(0,0,0,.5); }
        /* Stat cards (dashboard) */
        .stat-card { color: #fff; border: none; position: relative; overflow: hidden; }
        .stat-card .text-muted, .stat-card .small { color: rgba(255,255,255,.85) !important; }
        .stat-card::after { content: ''; position: absolute; inset: 0; background: linear-gradient(120deg, rgba(255,255,255,.14) 0%, transparent 45%); pointer-events: none; }
        .stat-card:hover { transform: translateY(-2px); }
        /* Chart cards: the Total Projects treatment applied to every chart (colours untouched) */
        .chart-card { position: relative; overflow: hidden; border: 1px solid var(--bs-border-color-translucent); box-shadow: 0 4px 14px rgba(10,74,58,.10), 0 1px 3px rgba(0,0,0,.06); }
        .chart-card::after { content: ''; position: absolute; inset: 0; background: linear-gradient(160deg, rgba(255,255,255,.10) 0%, transparent 40%); pointer-events: none; }
        .chart-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(10,74,58,.16); }
        [data-bs-theme="dark"] .chart-card { box-shadow: 0 4px 14px rgba(0,0,0,.45); }
        [data-bs-theme="dark"] .chart-card::after { background: linear-gradient(160deg, rgba(255,255,255,.04) 0%, transparent 40%); }
        /* Tables everywhere: zebra striping from the theme green, refined hover,
           consistent typography, rounded corners, subtle depth */
        .table { font-size: .92rem; }
        .table > :not(caption) > * > * { padding: .55rem .75rem; background-color: transparent; }
        .table thead th { font-weight: 600; letter-spacing: .02em; white-space: nowrap; border-bottom-width: 2px; }
        .table tbody tr { transition: background-color .15s ease; }
        .table tbody tr:nth-child(even) { background-color: rgba(15,110,86,.05); }
        [data-bs-theme="dark"] .table tbody tr:nth-child(even) { background-color: rgba(255,255,255,.04); }
        .table tbody tr:hover { background-color: rgba(15,110,86,.11); }
        [data-bs-theme="dark"] .table tbody tr:hover { background-color: rgba(255,255,255,.08); }
        .table-responsive { border-radius: var(--pmms-radius); box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .card .table-responsive { box-shadow: none; }
        .card > .table-responsive:first-child { border-radius: var(--pmms-radius) var(--pmms-radius) 0 0; }
        .card > .table-responsive:first-child .table { margin-bottom: 0; }
        /* Collapsible sidebar */
        .sidebar { transition: width .25s ease; }
        body.sidebar-collapsed .sidebar { width: 68px; }
        body.sidebar-collapsed .sidebar .nav-label,
        body.sidebar-collapsed .sidebar .sidebar-brand-text,
        body.sidebar-collapsed .sidebar .sidebar-user,
        body.sidebar-collapsed .sidebar .action-label { display: none; }
        body.sidebar-collapsed .sidebar .nav-link { text-align: center; }
        body.sidebar-collapsed .sidebar .nav-link i,
        body.sidebar-collapsed .sidebar .sidebar-actions i { margin-right: 0 !important; }
        .sidebar-actions .btn { text-align: left; }
        body.sidebar-collapsed .sidebar-actions .btn { text-align: center; padding-left: .25rem; padding-right: .25rem; }
        .sidebar-toggle { color: rgba(255,255,255,.72); background: none; border: 1px solid rgba(255,255,255,.25); border-radius: 8px; transition: color .15s ease, background .15s ease; }
        .sidebar-toggle:hover { color: #fff; background: rgba(255,255,255,.1); }
        @media (max-width: 767px) {
            .sidebar { width: 100%; }
            .app-shell { flex-direction: column; }
            body.sidebar-collapsed .sidebar { width: 100%; }
            body.sidebar-collapsed .sidebar .nav-label, body.sidebar-collapsed .sidebar .sidebar-brand-text,
            body.sidebar-collapsed .sidebar .sidebar-user, body.sidebar-collapsed .sidebar .action-label { display: revert; }
            .sidebar-toggle { display: none; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <nav class="sidebar p-3">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <div class="sidebar-brand-text">
                <div class="text-white fw-bold">BOMWH · PMMS</div>
                <div class="text-white-50 small" style="font-size:.72rem">Borno State Ministry of Works &amp; Housing</div>
            </div>
            <button type="button" id="sidebarToggle" class="sidebar-toggle btn btn-sm px-2" aria-label="Collapse navigation" aria-expanded="true" title="Collapse menu">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        </div>
        <div class="mb-2"></div>
        @auth
        @php $u = auth()->user(); @endphp
        <ul class="nav nav-pills flex-column gap-1">
            @if ($u->hasPermission('dashboard.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i><span class="nav-label">Dashboard</span></a></li>
            @endif
            @if ($u->hasPermission('projects.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}"><i class="bi bi-kanban me-2"></i><span class="nav-label">Projects</span></a></li>
            @endif
            @if ($u->hasPermission('contractors.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('contractors.*') ? 'active' : '' }}" href="{{ route('contractors.index') }}"><i class="bi bi-building me-2"></i><span class="nav-label">Contractors</span></a></li>
            @endif
            @if ($u->hasPermission('gis.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('map') ? 'active' : '' }}" href="{{ route('map') }}"><i class="bi bi-geo-alt me-2"></i><span class="nav-label">GIS map</span></a></li>
            @endif
            @if ($u->hasPermission('dashboard.executive'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('analytics') ? 'active' : '' }}" href="{{ route('analytics') }}"><i class="bi bi-graph-up me-2"></i><span class="nav-label">Analytics</span></a></li>
            @endif
            @if ($u->hasPermission('reports.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-file-earmark-text me-2"></i><span class="nav-label">Reports</span></a></li>
            @endif
            @if ($u->hasPermission('audit.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}"><i class="bi bi-shield-check me-2"></i><span class="nav-label">Audit log</span></a></li>
            @endif
            @if ($u->hasPermission('users.view'))
                <li><a title-from-label class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-2"></i><span class="nav-label">Users</span></a></li>
            @endif
            <li><a title-from-label class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell me-2"></i><span class="nav-label">Notifications</span></a></li>
        </ul>
        <hr class="text-secondary">
        <div class="text-white-50 small mb-2 sidebar-user">{{ $u->name }}<br>{{ $u->role->display_name }}</div>
        <div class="d-flex flex-column gap-2 sidebar-actions">
            <button id="themeToggle" type="button" class="btn btn-sm btn-outline-light" title="Dark mode"><i class="bi bi-moon-stars me-1"></i><span class="action-label">Dark mode</span></button>
            <a href="{{ route('profile.password.edit') }}" class="btn btn-sm btn-outline-light" title="Change password"><i class="bi bi-key me-1"></i><span class="action-label">Change password</span></a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-sm btn-outline-light w-100" title="Sign out"><i class="bi bi-box-arrow-right me-1"></i><span class="action-label">Sign out</span></button>
            </form>
        </div>
        @endauth
    </nav>
    <main class="flex-grow-1 p-3 p-md-4">
        {{-- Directors / Resident Engineers only ever see their own directorate: say so on the pages where it matters --}}
        @auth
        @if (! auth()->user()->seesAllDirectorates() && auth()->user()->directorate() && request()->routeIs('dashboard', 'projects.index', 'map', 'analytics', 'reports.*'))
            <div class="alert alert-info py-2 small d-flex align-items-center gap-2">
                <i class="bi bi-funnel"></i>
                <span>Showing <strong>{{ \App\Models\Project::DIRECTORATES[auth()->user()->directorate()] }}</strong> directorate projects only.</span>
            </div>
        @endif
        @endauth
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
@include('partials.footer')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---- Collapsible sidebar (persisted) ----
(function () {
    const btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    // native tooltips for icon-only mode
    document.querySelectorAll('[title-from-label]').forEach(a => {
        const label = a.querySelector('.nav-label');
        if (label) a.setAttribute('title', label.textContent.trim());
        a.removeAttribute('title-from-label');
    });
    function paint() {
        const collapsed = document.body.classList.contains('sidebar-collapsed');
        btn.querySelector('i').className = collapsed ? 'bi bi-chevron-double-right' : 'bi bi-chevron-double-left';
        btn.setAttribute('aria-expanded', String(!collapsed));
        btn.setAttribute('title', collapsed ? 'Expand menu' : 'Collapse menu');
    }
    btn.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('pmms-sidebar', document.body.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
        paint();
        // Reflow charts/maps DURING and AFTER the 250ms width transition,
        // otherwise canvases keep the mid-transition width and overflow.
        [80, 180, 300, 420].forEach(t => setTimeout(() => window.dispatchEvent(new Event('resize')), t));
    });
    // Also reflow once the sidebar's CSS transition definitively ends
    document.querySelector('.sidebar')?.addEventListener('transitionend', e => {
        if (e.propertyName === 'width') window.dispatchEvent(new Event('resize'));
    });
    if (localStorage.getItem('pmms-sidebar') === 'collapsed') document.body.classList.add('sidebar-collapsed');
    paint();
})();

// ---- Theme toggle (persisted) ----
(function () {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    const icon = btn.querySelector('i'), label = btn.querySelector('.action-label');
    function paint() {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        icon.className = dark ? 'bi bi-sun me-1' : 'bi bi-moon-stars me-1';
        label.textContent = dark ? 'Light mode' : 'Dark mode';
        btn.setAttribute('title', label.textContent);
    }
    btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('pmms-theme', next);
        paint();
    });
    paint();
})();

// ---- Animated counters for elements with data-count ----
(function () {
    const els = document.querySelectorAll('[data-count]');
    if (!els.length || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            io.unobserve(el);
            const target = parseFloat(el.dataset.count) || 0;
            const dur = 700, t0 = performance.now();
            (function tick(t) {
                const p = Math.min(1, (t - t0) / dur);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        });
    }, { threshold: .4 });
    els.forEach(el => io.observe(el));
})();

// ---- Live currency formatting for .money inputs ----
document.addEventListener('input', function (e) {
    if (!e.target.classList || !e.target.classList.contains('money')) return;
    const caretFromEnd = e.target.value.length - e.target.selectionStart;
    let raw = e.target.value.replace(/[^0-9.]/g, '');
    const firstDot = raw.indexOf('.');
    if (firstDot !== -1) raw = raw.slice(0, firstDot + 1) + raw.slice(firstDot + 1).replace(/\./g, '');
    const [intPart, decPart] = raw.split('.');
    let formatted = intPart ? Number(intPart).toLocaleString('en-US') : '';
    if (decPart !== undefined) formatted += '.' + decPart.slice(0, 2);
    e.target.value = formatted;
    const pos = Math.max(0, e.target.value.length - caretFromEnd);
    e.target.setSelectionRange(pos, pos);
});
document.addEventListener('submit', function (e) {
    e.target.querySelectorAll('.money').forEach(i => { i.value = i.value.replace(/,/g, ''); });
});
</script>
@stack('scripts')
</body>
</html>

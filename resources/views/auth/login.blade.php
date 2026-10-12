<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8"><title>Sign in — BOMWH PMMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Barlow', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        :root { --pmms-green: #0F6E56; --pmms-green-dark: #0A4A3A; --pmms-gold: #C9A227; }
        html, body { height: 100%; }
        body {
            display: flex; flex-direction: column;
            background:
                radial-gradient(1100px 500px at 85% -10%, rgba(201,162,39,.18), transparent 60%),
                radial-gradient(900px 600px at -10% 110%, rgba(15,110,86,.25), transparent 55%),
                linear-gradient(160deg, #06231C 0%, #0A4A3A 55%, #0F6E56 100%);
        }
        .login-wrap { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem 3rem; }
        .brand { text-align: center; color: #fff; animation: fadeDown .6s ease both; }
        .brand .logo { width: 92px; height: 92px; margin-bottom: 1rem; filter: drop-shadow(0 4px 10px rgba(0,0,0,.35)); }
        .brand .state { font-size: 1rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; opacity: .92; }
        .brand .ministry { font-size: 1.6rem; font-weight: 800; letter-spacing: .01em; margin: .15rem 0; }
        .brand .acronym { font-size: .85rem; font-weight: 400; color: var(--pmms-gold); letter-spacing: .3em; }
        .brand .system { font-size: .95rem; font-weight: 300; opacity: .85; margin-top: .35rem; letter-spacing: .04em; }
        .login-card {
            max-width: 420px; width: 100%; margin-top: 1.75rem;
            border: none; border-radius: 16px;
            box-shadow: 0 18px 50px rgba(0,0,0,.35);
            animation: fadeUp .6s .15s ease both;
        }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-14px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .brand, .login-card { animation: none; } }
        .input-group-text { background: transparent; }
        .form-control:focus { border-color: var(--pmms-green); box-shadow: 0 0 0 .2rem rgba(15,110,86,.15); }
        .btn-signin { background: var(--pmms-green); border: none; padding: .65rem; font-weight: 600; transition: background .18s ease, transform .12s ease; }
        .btn-signin:hover { background: var(--pmms-green-dark); }
        .btn-signin:active { transform: scale(.98); }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="w-100" style="max-width:420px">
        <div class="brand">
            {{-- Official logo: drop the Ministry's file at public/images/ministry-logo.png to replace this emblem --}}
            @if (file_exists(public_path('images/ministry-logo.png')))
                <img src="{{ asset('images/ministry-logo.png') }}" alt="Borno State Ministry of Works and Housing logo" class="logo">
            @else
                <svg class="logo" viewBox="0 0 100 100" role="img" aria-label="Ministry emblem placeholder">
                    <path d="M50 4 L90 20 V52 C90 74 72 90 50 97 C28 90 10 74 10 52 V20 Z" fill="#0F6E56" stroke="#C9A227" stroke-width="3"/>
                    <path d="M30 62 V44 L50 30 L70 44 V62" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M42 62 V50 H58 V62" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M24 68 H76" stroke="#C9A227" stroke-width="4" stroke-linecap="round"/>
                </svg>
            @endif
            <div class="state">Borno State</div>
            <div class="ministry">Ministry of Works and Housing</div>
            <div class="acronym">(BOMWH)</div>
            <div class="system">Project Monitoring &amp; Management System</div>
        </div>

        <div class="card login-card">
            <div class="card-body p-4">
                <h1 class="fs-5 mb-1">Welcome back</h1>
                <p class="text-muted small mb-3">Sign in to continue</p>
                @if ($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="name@works.gov" required autofocus autocomplete="username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="pwField" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="pwToggle" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('help.faqs') }}#forgot-password" class="small text-decoration-none">Forgot password?</a>
                    </div>
                    <button class="btn btn-signin text-white w-100" id="signinBtn">
                        <span class="btn-label">Sign in</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@include('partials.footer')
<script>
document.getElementById('pwToggle').addEventListener('click', function () {
    const f = document.getElementById('pwField');
    const show = f.type === 'password';
    f.type = show ? 'text' : 'password';
    this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
document.getElementById('loginForm').addEventListener('submit', function () {
    const b = document.getElementById('signinBtn');
    b.disabled = true;
    b.querySelector('.btn-label').textContent = 'Signing in…';
    b.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
</body>
</html>

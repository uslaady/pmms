<style>
    /* Global sticky footer — self-contained so it renders identically on
       every page (app, login, help, public portal). */
    :root { --pmms-footer-h: 46px; }
    body { padding-bottom: var(--pmms-footer-h); }
    .app-footer {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        z-index: 1030;
        min-height: var(--pmms-footer-h);
        display: flex; align-items: center;
        background: linear-gradient(180deg, #10231E 0%, #0A1713 100%);
        border-top: 1px solid rgba(255,255,255,.08);
        box-shadow: 0 -4px 14px rgba(0,0,0,.22);
        color: rgba(255,255,255,.78);
    }
    .app-footer .footer-inner {
        width: 100%;
        display: flex; flex-wrap: wrap;
        justify-content: space-between; align-items: center;
        gap: .25rem .75rem;
        padding: .45rem 1rem;
        font-size: .8rem;
    }
    .app-footer .footer-copy { color: rgba(255,255,255,.72); }
    .app-footer .footer-meta { color: rgba(255,255,255,.45); }
    .app-footer .footer-nav { display: flex; flex-wrap: wrap; gap: 1rem; }
    .footer-link {
        color: rgba(255,255,255,.78);
        text-decoration: none;
        transition: color .18s ease;
        border-radius: 4px;
        white-space: nowrap;
    }
    .footer-link:hover { color: #E4C765; }
    .footer-link:focus-visible { outline: 2px solid #E4C765; outline-offset: 3px; color: #E4C765; }
    @media (max-width: 575.98px) {
        :root { --pmms-footer-h: 76px; }
        .app-footer .footer-inner { justify-content: center; text-align: center; }
    }
    @media print { .app-footer { display: none; } body { padding-bottom: 0; } }
</style>
<footer class="app-footer" role="contentinfo">
    <div class="footer-inner">
        <span class="footer-copy">© 2026 Borno State Ministry of Works and Housing (BOMWH). All Rights Reserved.
            <span class="d-none d-lg-inline footer-meta">· Version 1.0.0 · Updated {{ now()->format('M Y') }}</span>
        </span>
        <nav class="footer-nav" aria-label="Footer navigation">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="footer-link"><i class="bi bi-house me-1" aria-hidden="true"></i>Home</a>
            <a href="{{ route('help.cookies') }}" class="footer-link"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Cookies</a>
            <a href="{{ route('help.index') }}" class="footer-link"><i class="bi bi-life-preserver me-1" aria-hidden="true"></i>Help</a>
            <a href="{{ route('help.faqs') }}" class="footer-link"><i class="bi bi-question-circle me-1" aria-hidden="true"></i>FAQs</a>
        </nav>
    </div>
</footer>

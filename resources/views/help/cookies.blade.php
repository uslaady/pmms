@extends('help._shell')
@section('title', 'Cookies Policy')
@section('body')
<p class="text-muted">Last updated: {{ now()->format('F Y') }}</p>

<h2 class="fs-5 mt-4">What are cookies?</h2>
<p>Cookies are small text files stored on your device by your web browser when you visit a website. This system uses a minimal set of cookies that are strictly necessary for it to function securely. We do not use advertising or cross-site tracking cookies.</p>

<h2 class="fs-5 mt-4">Cookies this system uses</h2>
<div class="table-responsive"><table class="table table-bordered">
    <thead><tr><th>Cookie</th><th>Purpose</th><th>Type</th><th>Duration</th></tr></thead>
    <tbody>
        <tr><td><code>pmms_session</code></td><td>Session management — keeps you signed in as you move between pages and remembers preferences such as rows-per-page.</td><td>Strictly necessary</td><td>Until the session ends</td></tr>
        <tr><td><code>XSRF-TOKEN</code></td><td>Security — protects every form you submit against cross-site request forgery attacks.</td><td>Strictly necessary</td><td>Session</td></tr>
        <tr><td><code>remember_web_*</code></td><td>Authentication — set only if you tick "Remember me" on the sign-in page, so you stay signed in on your device.</td><td>Functional (optional)</td><td>Up to 5 years or until sign-out</td></tr>
        <tr><td><code>pmms-theme</code> <span class="text-muted">(local storage)</span></td><td>User preference — remembers whether you chose Light or Dark mode. Stored in your browser's local storage, not sent to the server.</td><td>Preference</td><td>Until cleared</td></tr>
    </tbody>
</table></div>

<h2 class="fs-5 mt-4">Why these cookies are necessary</h2>
<p>Without the session and security cookies, it would be impossible to sign in, submit forms safely, or keep your work associated with your account. They contain a random identifier only — no personal information is stored inside the cookies themselves.</p>

<h2 class="fs-5 mt-4">Analytics</h2>
<p>This system does not currently use third-party analytics cookies. Should the Ministry introduce usage analytics in future, this policy will be updated and consent obtained where required.</p>

<h2 class="fs-5 mt-4">Managing cookies</h2>
<p>You can view, delete, or block cookies through your browser settings (usually under Privacy or Site Settings). Note that blocking the strictly necessary cookies listed above will prevent you from signing in to this system. Choosing not to tick "Remember me" avoids the persistent authentication cookie entirely.</p>

<h2 class="fs-5 mt-4">Contact</h2>
<p>Questions about this policy should be directed to the system administrator at the Borno State Ministry of Works and Housing ICT unit.</p>
@endsection

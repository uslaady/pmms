@extends('help._shell')
@section('title', 'Help Center')
@section('body')

<div class="mb-4 no-print">
    <div class="input-group input-group-lg">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" id="helpSearch" class="form-control" placeholder="Search the Help Center (e.g. 'upload photo', 'approve payment', 'reset password')..." aria-label="Search help topics">
    </div>
    <div class="form-text">Type to filter every guide below. Clear the box to see everything again.</div>
</div>

<h2 class="fs-5 mb-3">Quick start</h2>
<div class="accordion mb-4" id="quickStart">
    <div class="accordion-item help-topic">
        <h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qs1">Signing in for the first time</button></h3>
        <div id="qs1" class="accordion-collapse collapse" data-bs-parent="#quickStart"><div class="accordion-body">
            <ol>
                <li>Open the application address in your browser and enter the email and temporary password given to you by the administrator.</li>
                <li>After signing in, click <strong>Change password</strong> in the sidebar and set a personal password (at least 10 characters with upper &amp; lower case letters and a number).</li>
                <li>Your sidebar shows only the menus your role is permitted to use.</li>
            </ol>
        </div></div>
    </div>
    <div class="accordion-item help-topic">
        <h3 class="accordion-header"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#qs2">Understanding the dashboard</button></h3>
        <div id="qs2" class="accordion-collapse collapse" data-bs-parent="#quickStart"><div class="accordion-body">
            The dashboard summarises the whole portfolio: coloured cards count projects by state and money totals; the charts break projects down by status, district, LGA, ward, and contractor. <strong>Click any chart segment or bar to filter the entire page</strong>; click it again (or the fixed Reset filter button, top-right) to clear. The bottom table toggles between Top delayed projects and the full filtered project list.
        </div></div>
    </div>
</div>

<h2 class="fs-5 mb-3">Guides by role</h2>
<div class="accordion" id="roleGuides">
@php
$roles = [
    ['Super Admin', 'Full control: every task and every page, including creating Super Admin accounts and resetting anyone\'s password.', [
        'Users' => 'Users menu → Add user (any role) → set Active / Deactivated / Suspended / Blacklisted → Reset password. New and reset accounts must set their own password at next sign-in.',
        'Locked records' => 'Edit, reopen (change status) or delete Suspended / Cancelled / Deactivated projects and Deactivated / Suspended / Blacklisted contractors.',
        'Audit log' => 'Every create, update, delete and approval with user, time, details and IP address.',
    ], 'Keep Super Admin accounts to a minimum and review the audit log regularly.'],
    ['Honorable Commissioner', 'Every task and every page, except creating a Super Admin or another Commissioner, or changing a Super Admin account.', [
        'Users' => 'Create any user except a Super Admin or Commissioner (you create Permanent Secretaries); change status and reset passwords of everyone except the Super Admin.',
        'Executive view' => 'Dashboard, Analytics, GIS map and Reports cover all four directorates. Audit log is available.',
    ], 'Use chart clicks on the dashboard to drill into a directorate area, LGA or contractor.'],
    ['Permanent Secretary', 'Approves: new projects, contractors, payments and progress updates; manages project status and users.', [
        'Approve or reject a project' => 'Projects → filter "Pending approval" → open the project → Approve project, or Reject with a reason (the Director corrects it and resubmits). The Director Planning is notified to assign a contractor after approval.',
        'Approve or reject a contractor' => 'Contractors → open a Pending contractor → check its details and documents → Set status Active, or Reject with a reason. (You cannot register or edit active contractors.)',
        'Approve payments and progress' => 'Open the project → Financials → Approve; Progress updates → Approve / Reject.',
        'Project status' => 'Open the project → status dropdown: Suspended, Cancelled and Deactivated lock the project; set it back to In progress to reopen it.',
        'Locked records & deletion' => 'You can edit or delete locked projects and contractors, and delete any project.',
        'Users' => 'Create users (any role except Super Admin, Commissioner or Permanent Secretary) and change the status of anyone except the Super Admin and Commissioner. Password resets are done by the Commissioner or Super Admin.',
    ], 'Notifications tell you whenever something is waiting for your approval.'],
    ['Directors: Civil Engineering · Public Building · Electrical Engineering · Mechanical Engineering', 'Register and edit projects of your own directorate, and do everything your Resident Engineer can do on them.', [
        'Register a project' => 'Projects → Register project (or Bulk upload). The directorate is fixed to yours; the project goes to the Permanent Secretary for approval. Do not enter a contractor — the Director Planning assigns it after approval. If the project is rejected, open it, read the reason, correct it (Edit) and click Resubmit for approval.',
        'Edit a project' => 'Open the project → Edit. Locked (Suspended / Cancelled / Deactivated) projects can no longer be edited by you.',
        'Site updates' => 'Progress updates, milestones, site photos, documents and inspections — exactly like your Resident Engineer.',
        'My map, analytics and reports' => 'Show your directorate only.',
    ], 'You cannot delete projects, register contracts or choose contractors.'],
    ['Director Planning, Research & Statistics', 'Registers contractors and their documents, assigns Active contractors to approved projects, registers contracts, and can register/edit projects of any directorate.', [
        'Register contractors' => 'Contractors → Register contractor, or Bulk upload with the CSV template. Then open the contractor and upload its registration documents. It stays Pending until the Permanent Secretary activates it. If it is rejected, correct it and click Resubmit for approval.',
        'Assign a contractor' => 'Open an approved project → Assign contractor (only Active contractors are listed).',
        'Register a contract' => 'Open the project → Financials → Register contract; record variation orders / extensions of time.',
        'Insight' => 'GIS map, Analytics and Reports cover all directorates; Reports can be filtered by directorate.',
    ], 'Deactivated, Suspended and Blacklisted contractors are locked: you can view them but not change them.'],
    ['Director Finance & Accounts', 'Records and updates payments for every project; full GIS map, Analytics and Reports.', [
        'Record a payment' => 'Open the project → Financials → Date, Certificate No, Amount → Record payment. It goes to the Permanent Secretary for approval.',
        'Mark as paid' => 'Once approved and the funds are disbursed, click Mark paid — only Paid amounts count toward Total Paid and Outstanding.',
        'Financial reports' => 'Reports → report type Financial → date range (payment dates) → View, CSV, Excel or PDF.',
    ], 'Payments cannot be recorded on projects that are pending approval or locked.'],
    ['Project Resident Engineers: Civil · Public Building · Electrical · Mechanical', 'Field role: site updates on projects of your own directorate only; personalised GIS map and Analytics.', [
        'Submit a progress update' => 'Open the project → Progress updates → physical progress % and a narrative → Submit. It waits for approval by the Permanent Secretary.',
        'Milestones, photos, documents' => 'Open the project → Milestones (update status), Site photos (Before / During / After), Documents.',
        'Record an inspection' => 'Open the project → Inspections → date, risk level, quality score (0–100), observations.',
    ], 'Projects pending approval or locked do not accept updates.'],
    ['Public (no login)', 'The public transparency portal at /public/projects.', [
        'Browse projects' => 'Filter by local government area; open any project to see its location, progress and photos.',
    ], 'Only completed and in-progress projects are visible publicly.'],
];
@endphp
@foreach ($roles as $i => [$role, $overview, $tasks, $tip])
    <div class="accordion-item help-topic">
        <h3 class="accordion-header">
            <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#role{{ $i }}">
                <i class="bi bi-person-badge me-2"></i>{{ $role }}
            </button>
        </h3>
        <div id="role{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#roleGuides">
            <div class="accordion-body">
                <p>{{ $overview }}</p>
                <h4 class="fs-6">Common tasks</h4>
                <dl>
                    @foreach ($tasks as $task => $steps)
                        <dt>{{ $task }}</dt><dd class="text-muted">{{ $steps }}</dd>
                    @endforeach
                </dl>
                <div class="alert alert-success py-2 small mb-0"><i class="bi bi-lightbulb me-1"></i>{{ $tip }}</div>
            </div>
        </div>
    </div>
@endforeach
</div>

<h2 class="fs-5 mt-4">Troubleshooting &amp; support</h2>
<ul>
    <li><strong>You see "403 — You do not have permission":</strong> your role doesn't include that action. Contact an administrator if you believe you need it.</li>
    <li><strong>You're locked out:</strong> an administrator can reset your password from the Users page.</li>
    <li><strong>Something looks wrong after an update:</strong> hard-refresh with Ctrl+F5 to clear cached styles.</li>
    <li><strong>Technical support:</strong> contact the BOMWH ICT unit or your system administrator.</li>
</ul>

<script>
document.getElementById('helpSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.help-topic').forEach(item => {
        item.style.display = !q || item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
@endsection

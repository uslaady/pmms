@extends('help._shell')
@section('title', 'Frequently Asked Questions')
@section('body')

<div class="mb-4 no-print">
    <div class="input-group input-group-lg">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" id="faqSearch" class="form-control" placeholder="Search the FAQs..." aria-label="Search FAQs">
    </div>
</div>

@php
$faqs = [
    'General' => [
        ['What is the purpose of this system?', 'The Project Monitoring & Management System (PMMS) gives the Borno State Ministry of Works and Housing real-time visibility of every capital project: registration, contractor and contract management, progress and milestone tracking, inspections, photos and documents, finances, GIS mapping, analytics, and reports — all under role-based access control.'],
        ['Who can access the system?', 'Ministry staff and contractor representatives with accounts created by an administrator. Each account has one of 13 roles that determines exactly what its holder can see and do; Directors and Resident Engineers only see their own directorate. Completed and ongoing projects are also visible to the public on the transparency portal without a login.'],
        ['How do I change my password?', 'Click "Change password" in the sidebar, enter your current password and a new one (minimum 10 characters with upper and lower case letters and a number), and save.', 'forgot-password'],
        ['What happens when a project or contractor is rejected?', 'The Permanent Secretary gives a reason. The project (or contractor) is not locked: the Director (or Director Planning for contractors) opens it, reads the reason, corrects it and clicks Resubmit for approval. Nothing else — contractor, contract, payments, site updates — can be added until it is approved.'],
        ['Which date format does the bulk upload use?', 'DD/MM/YYYY, for example 31/12/2026. Impossible dates such as 31/02/2026 are rejected with the row number.'],
        ['Why am I asked to change my password when I sign in?', 'Accounts created or reset by an administrator must set a personal password before using the system. Your new password must be different from the one you were given.'],
        ['Why can\'t I sign in?', 'If the sign-in page says your account is deactivated, suspended or blacklisted, contact the Office of the Permanent Secretary or the system administrator. Only Active accounts can sign in.'],
        ['What should I do if I forget my password?', 'Contact your system administrator — the Commissioner or Super Admin can set a new password for you from the Users page. Sign in with it and change it immediately using "Change password" in the sidebar.', 'forgot-password'],
    ],
    'Project Management' => [
        ['How do I create a new project?', 'Projects → Register project (Directors register projects of their own directorate; the Director Planning, Commissioner and Super Admin can register for any directorate). Fill the project code, name, location (District → LGA → Ward — type to search; coordinates fill automatically from the ward), dates and priority, then Save. The project goes to the Permanent Secretary for approval; the Director Planning then assigns an Active contractor.'],
        ['How do I register many projects at once?', 'Projects → Bulk upload → download the CSV template, fill one project per row, and upload it. Valid rows are created immediately; invalid rows are skipped and listed with the row number and exact reason so you can fix and re-upload just those.'],
        ['How do I edit project information?', 'Open the project and click Edit at the top. Directors can edit projects in their own directorate; the Director of Planning, Research & Statistics, Commissioner and Super Admin can edit any project.'],
        ['Can I delete a project?', 'Only the Super Admin, the Honorable Commissioner and the Permanent Secretary can delete a project, and it is a soft delete — the record is recoverable from the database and its project code stays reserved. In most cases changing the status to Cancelled is more appropriate than deletion.'],
        ['How do I upload project photographs?', 'Open the project → Site photos → choose the image (phone cameras work directly), pick the stage (Before / During / After), add a caption, Upload. Photos over 1 MB are automatically compressed to about 300 KB, and your GPS position is attached if you allow location access.'],
        ['How do I update project progress?', 'Open the project → Progress updates → enter the physical progress percentage and a short narrative of what was done → Submit. The update is pending until the Permanent Secretary (or Commissioner / Super Admin) approves it; approval updates the project\'s official progress.'],
        ['How do I change a project\'s status?', 'Open the project and use the status dropdown at the top right (Permanent Secretary, Commissioner or Super Admin). Suspended, Cancelled and Deactivated projects are locked — nothing can be added or changed until an executive reopens them. Suspending or completing a project notifies the executives automatically. Projects that pass their expected completion date, or slip more than 15 points behind plan, are flagged as Delayed automatically every morning.'],
    ],
    'Contractors' => [
        ['How do I register a contractor?', 'Contractors → Register contractor, or Bulk upload (Director Planning, Commissioner or Super Admin). Upload the registration documents on the contractor\'s page. New contractors start as Pending and must be activated by the Permanent Secretary (or Commissioner / Super Admin) before they can be assigned to projects.'],
        ['Why can\'t I find a contractor when assigning one?', 'Only Active contractors appear in the Assign contractor list, and only on approved projects. Check the contractor\'s status on the Contractors page — Pending, Deactivated, Suspended, and Blacklisted contractors are excluded.'],
        ['Can I delete a contractor?', 'Only a Deactivated, Suspended or Blacklisted contractor can be deleted, and only by the Permanent Secretary, Commissioner or Super Admin. It is a soft delete: its name stays on its existing projects. Locked contractors keep their existing projects (which can still be updated) but cannot receive new ones.'],
        ['How is contractor performance evaluated?', 'The Analytics page ranks contractors by completion rate (completed vs total projects) and delayed project count, alongside the performance score maintained on the contractor record.'],
    ],
    'Financial Management' => [
        ['How do I record project payments?', 'Open the project → Financials → enter Date, Certificate No, and Amount (the field formats with commas as you type) → Record payment (Director Finance & Accounts). The payment starts as Pending.'],
        ['Who approves payments and when do they count as paid?', 'The Permanent Secretary (or Commissioner / Super Admin) clicks Approve on the pending payment. When the funds are actually disbursed, Finance clicks Mark paid. Only payments in the Paid state count toward Total Paid and reduce the Outstanding balance.'],
        ['What is the Revised Value?', 'The original contract value plus all approved variation orders. It updates automatically when a variation order is recorded in the Financials section of the project.'],
        ['How do I track contract variations?', 'Open the project → Financials → "Record variation order / extension of time". Variation orders adjust the Revised Value; extensions of time push the expected completion date by the given number of days.'],
    ],
    'Inspections' => [
        ['How do I submit an inspection report?', 'Open the project → Inspections → fill the date, risk level, quality score (0–100), and observations → Record inspection (Resident Engineer or Director of the project\'s directorate).'],
        ['How do I upload inspection images?', 'Use the Site photos section on the same project page — photos are geo-tagged and staged, and they form part of the project\'s evidence record.'],
        ['I recorded an inspection in error — can I fix it?', 'Yes. The pencil icon on the inspection opens an edit form, and the trash icon deletes it (Resident Engineer or Director of that directorate, Commissioner or Super Admin).'],
    ],
    'GIS & Mapping' => [
        ['Why is my project location not appearing on the map?', 'The project has no coordinates. Open the project → Edit → pick the ward (coordinates auto-fill from the ward centroid) or type the exact latitude/longitude → Save.'],
        ['How do I update project coordinates?', 'Edit the project and change the Latitude/Longitude fields, or re-select the ward to reset them to the ward centroid.'],
        ['How do I switch to satellite view?', 'Use the layer control in the top-right corner of any map to toggle between Map and Satellite.'],
    ],
    'Reports' => [
        ['How do I generate reports?', 'Reports menu → choose any combination of contractors, districts, LGAs, wards, statuses, and a date range (leave everything blank for the general report) → View report, or export directly.'],
        ['Can I export reports to PDF or Excel?', 'Yes — CSV, Excel, and PDF export buttons are on the report builder and on every generated report. Exports include the criteria used so readers know the report\'s scope.'],
    ],
    'Notifications' => [
        ['Why didn\'t I receive a notification?', 'In-app notifications are sent to the roles concerned by each event (e.g. overdue projects notify the Permanent Secretary, the Commissioner and the Director and Resident Engineers of that project\'s directorate). Check the Notifications page; if you believe your role should be notified for an event, contact the administrator.'],
        ['How do notification triggers work?', 'Notifications fire when a project goes overdue, an inspection falls due, a milestone completes, a payment is approved, or a project is suspended or completed. A scheduled job also runs every morning to flag overdue and slipping projects automatically.'],
    ],
    'Technical Issues' => [
        ['The application is running slowly.', 'Try a hard refresh (Ctrl+F5). Large tables render faster with a smaller "Rows per page" setting. If slowness persists across users, report it to the ICT unit.'],
        ['Images are not uploading.', 'Check the error shown at the top of the page — it states the exact reason (format not supported, file over 20 MB, or no file chosen). Supported formats: JPG, PNG, GIF, WebP.'],
        ['My dashboard is empty.', 'Either no projects match the active filters (click Reset filter, top right) or no projects have been registered yet.'],
        ['I cannot access a menu.', 'Menus are hidden when your role lacks the permission behind them. Ask an administrator if your responsibilities have changed.'],
        ['I accidentally submitted incorrect information.', 'Most records can be corrected: projects via Edit, inspections via the pencil icon, milestones via their status control, contractors via Edit. Progress updates cannot be edited after submission — ask the Permanent Secretary to Reject the update and submit a corrected one.'],
        ['How do I contact the system administrator?', 'Reach the BOMWH ICT unit, or any Super Admin listed by your Ministry. Include your name, role, what you were doing, and the exact error text.'],
    ],
];
@endphp

@foreach ($faqs as $category => $items)
    <h2 class="fs-5 mt-4 mb-2">{{ $category }}</h2>
    <div class="accordion mb-3" id="cat{{ Str::slug($category) }}">
        @foreach ($items as $j => $qa)
            <div class="accordion-item faq-item" @if(isset($qa[2])) id="{{ $qa[2] }}" @endif>
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq{{ Str::slug($category) }}{{ $j }}">{{ $qa[0] }}</button>
                </h3>
                <div id="faq{{ Str::slug($category) }}{{ $j }}" class="accordion-collapse collapse" data-bs-parent="#cat{{ Str::slug($category) }}">
                    <div class="accordion-body">{{ $qa[1] }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach

<script>
document.getElementById('faqSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.faq-item').forEach(item => {
        item.style.display = !q || item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
@endsection

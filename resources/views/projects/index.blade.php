@extends('layouts.app')
@section('title', 'Projects')
@section('content')
@php
    $me = auth()->user();
    $directorates = \App\Models\Project::DIRECTORATES;
    $canCreate = $me->hasPermission('projects.create');
    $myDirectorate = $me->seesAllDirectorates() ? null : ($directorates[$me->directorate()] ?? null);
    $bulkErrors = $errors->has('file');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h1 class="fs-4 mb-0">Projects</h1>
        @if ($canCreate && $myDirectorate)
            <div class="text-muted small">Your projects are registered under your directorate automatically.</div>
        @elseif ($canCreate)
            <div class="text-muted small">Choose the directorate of each project you register.</div>
        @endif
    </div>
    @if ($canCreate)
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newProject" aria-expanded="false" aria-controls="newProject">
            <i class="bi bi-plus-lg me-1"></i>Register project</button>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#bulkUpload" aria-expanded="false" aria-controls="bulkUpload">
            <i class="bi bi-upload me-1"></i>Bulk upload</button>
    </div>
    @endif
</div>

{{-- Bulk upload results --}}
@if (session('bulk_results'))
    @php $res = session('bulk_results'); @endphp
    <div class="alert {{ count($res['errors']) ? 'alert-warning' : 'alert-success' }}">
        <strong>{{ $res['created'] }}</strong> project(s) created and sent to the Permanent Secretary for approval.
        @if (count($res['errors']))
            <strong>{{ count($res['errors']) }}</strong> row(s) skipped — correct the reasons below and re-upload only those rows.
        @endif
    </div>
    @if (count($res['errors']))
    <div class="card mb-3">
        <div class="card-header">Skipped rows</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead><tr><th style="width:80px">Row</th><th style="width:200px">Project Code</th><th>Reasons</th></tr></thead>
                <tbody>
                @foreach ($res['errors'] as $err)
                    <tr><td>{{ $err['row'] }}</td><td>{{ $err['code'] }}</td><td>{{ implode('; ', $err['reasons']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endif

@if ($canCreate)
{{-- The two panels share a parent, so opening one closes the other --}}
<div id="registerPanels">

    {{-- Single (one-by-one) registration --}}
    <div class="collapse mb-3 {{ $errors->any() && ! $bulkErrors ? 'show' : '' }}" id="newProject" data-bs-parent="#registerPanels">
        <div class="card card-body">
            <h2 class="fs-6 mb-1">Register a project</h2>
            <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>New projects are sent to the Permanent Secretary for approval. Once approved, the Director of Planning assigns an active contractor.</p>
            <form method="POST" action="{{ route('projects.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3"><label class="form-label">Project Code *</label><input name="project_code" value="{{ old('project_code') }}" class="form-control" placeholder="MWH/2026/CE/001" required></div>
                <div class="col-md-9"><label class="form-label">Project Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Directorate *</label>
                    @if ($me->seesAllDirectorates())
                    <select name="directorate" class="form-select" required>
                        <option value="">— select directorate —</option>
                        @foreach ($directorates as $key => $label)<option value="{{ $key }}" @selected(old('directorate') === $key)>{{ $label }}</option>@endforeach
                    </select>
                    @else
                    <input class="form-control" value="{{ $myDirectorate }}" disabled>
                    <div class="form-text">Fixed to your directorate.</div>
                    @endif
                </div>
                <div class="col-md-4"><label class="form-label">District</label>
                    <select id="selDistrict" name="district_id" class="form-select" placeholder="Type to search district..."><option value="">—</option></select></div>
                <div class="col-md-4"><label class="form-label">LGA</label>
                    <select id="selLga" name="lga_id" class="form-select" placeholder="Select district first..."><option value="">—</option></select></div>
                <div class="col-md-4"><label class="form-label">Ward</label>
                    <select id="selWard" name="ward_id" class="form-select" placeholder="Select LGA first..."><option value="">—</option></select></div>
                <div class="col-md-4"><label class="form-label">Latitude <span class="text-muted small">(auto from ward, editable)</span></label>
                    <input id="inpLat" name="latitude" value="{{ old('latitude') }}" class="form-control" placeholder="11.8311"></div>
                <div class="col-md-4"><label class="form-label">Longitude <span class="text-muted small">(auto from ward, editable)</span></label>
                    <input id="inpLng" name="longitude" value="{{ old('longitude') }}" class="form-control" placeholder="13.1510"></div>
                <div class="col-md-4"><label class="form-label">Award date</label><input type="date" name="award_date" value="{{ old('award_date') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Commencement date</label><input type="date" name="commencement_date" value="{{ old('commencement_date') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Expected completion date</label><input type="date" name="expected_completion_date" value="{{ old('expected_completion_date') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        @foreach (['medium', 'low', 'high', 'critical'] as $pr)<option value="{{ $pr }}" @selected(old('priority', 'medium') === $pr)>{{ ucfirst($pr) }}</option>@endforeach
                    </select></div>
                <div class="col-md-8"><label class="form-label">Funded by</label>
                    <input name="funded_by" value="{{ old('funded_by', 'Borno State Govt.') }}" class="form-control"></div>
                <div class="col-12"><label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea></div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-success">Save project</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#newProject">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk registration --}}
    <div class="collapse mb-3 {{ $bulkErrors ? 'show' : '' }}" id="bulkUpload" data-bs-parent="#registerPanels">
        <div class="card card-body">
            <h2 class="fs-6 mb-2">Bulk Upload Guide:</h2>
            <ul class="small mb-3">
                <li>Enter one project per row. <code>project_code</code> and <code>project_name</code> are required.</li>
                @if ($myDirectorate)
                    <li>Your projects are registered under your directorate automatically.</li>
                @else
                    <li>Enter the <code>directorate</code> of every project: <code>civil</code>, <code>public_building</code>, <code>electrical</code> or <code>mechanical</code>.</li>
                @endif
                <li>Ensure that the Ward belongs to the LGA and that the LGA belongs to the District. Also, ensure that all names are spelt correctly.</li>
                <li>Use the date format <strong>DD/MM/YYYY</strong>.</li>
                <li>Leave Latitude and Longitude blank to automatically populate them using the ward's coordinates, or enter the exact values to override them.</li>
                <li>Do not include the Contractor. All new projects are submitted to the Permanent Secretary for approval. Once approved, the Director of Planning assigns an active contractor.</li>
                <li>After the bulk upload, only valid rows are created immediately. Invalid rows are skipped, and the reasons for rejection are listed so you can correct the errors and re-upload them.</li>
                <li><a href="{{ route('projects.bulk.template') }}"><i class="bi bi-download me-1"></i>Download the CSV template</a>, which includes one example row that you can delete before entering your project data.</li>
            </ul>
            <form method="POST" action="{{ route('projects.bulk.store') }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-6"><input type="file" name="file" accept=".csv,text/csv" class="form-control" required></div>
                <div class="col-md-3"><button class="btn btn-success w-100">Upload and register</button></div>
                <div class="col-md-3"><button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="collapse" data-bs-target="#bulkUpload">Cancel</button></div>
            </form>
        </div>
    </div>
</div>
@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search Project Code, Project Name, LGA or Contractor"></div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Models\Project::STATUS_LABELS as $s => $label)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @if ($me->seesAllDirectorates())
    <div class="col-md-2">
        <select name="directorate" class="form-select">
            <option value="">All directorates</option>
            @foreach ($directorates as $key => $label)<option value="{{ $key }}" @selected(request('directorate') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    @endif
    <div class="col-md-2"><input name="year" value="{{ request('year') }}" class="form-control" placeholder="Award year"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    @if (request()->hasAny(['q', 'status', 'year', 'lga_id', 'contractor_id', 'directorate']))
        <div class="col-md-2"><a href="{{ route('projects.index') }}" class="btn btn-outline-danger w-100">Reset filters</a></div>
    @endif
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Project Code</th><th>Project Name</th><th>Directorate</th><th>LGA</th><th>Contractor</th><th>Progress</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($projects as $p)
                <tr>
                    <td><a href="{{ route('projects.show', $p) }}">{{ $p->project_code }}</a></td>
                    <td>{{ $p->name }}</td>
                    <td class="small">{{ $p->directorate_label }}</td>
                    <td>{{ $p->lga?->name }}</td>
                    <td>{{ $p->contractor?->name ?? '—' }}</td>
                    <td style="min-width:140px">
                        <div class="progress" style="height:16px">
                            <div class="progress-bar" style="width: {{ $p->actual_progress_percent }}%; background: {{ $p->status_color }}">{{ $p->actual_progress_percent }}%</div>
                        </div>
                    </td>
                    <td><span class="badge" style="background: {{ $p->status_color }}">@if ($p->isFrozen())<i class="bi bi-lock-fill me-1"></i>@endif{{ $p->status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No projects match your filters. <a href="{{ route('projects.index') }}">Reset filters</a> or register the first project.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $projects->links() }}</div>
@endsection

@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// District -> LGA -> Ward cascade with type-to-search and coordinate auto-fill.
(async function () {
    const elDistrict = document.getElementById('selDistrict');
    if (!elDistrict) return; // user lacks projects.create — form not rendered

    const res = await fetch('{{ route('locations.tree') }}');
    const districts = await res.json();

    const tsOpts = { create: false, allowEmptyOption: true, maxOptions: 400, sortField: { field: 'text' } };
    const tsDistrict = new TomSelect('#selDistrict', tsOpts);
    const tsLga = new TomSelect('#selLga', tsOpts);
    const tsWard = new TomSelect('#selWard', tsOpts);

    const wardIndex = {}; // ward id -> {lat, lng}

    tsDistrict.addOptions(districts.map(d => ({ value: d.id, text: d.name })));

    tsDistrict.on('change', function (districtId) {
        tsLga.clear(true); tsLga.clearOptions();
        tsWard.clear(true); tsWard.clearOptions();
        const d = districts.find(x => x.id == districtId);
        if (d) tsLga.addOptions(d.lgas.map(l => ({ value: l.id, text: l.name })));
    });

    tsLga.on('change', function (lgaId) {
        tsWard.clear(true); tsWard.clearOptions();
        const d = districts.find(x => x.id == tsDistrict.getValue());
        const lga = d && d.lgas.find(l => l.id == lgaId);
        if (lga) {
            lga.wards.forEach(w => { wardIndex[w.id] = { lat: w.latitude, lng: w.longitude }; });
            tsWard.addOptions(lga.wards.map(w => ({ value: w.id, text: w.name })));
        }
    });

    tsWard.on('change', function (wardId) {
        const w = wardIndex[wardId];
        if (w && w.lat !== null) {
            // Pre-fill from the ward centroid; the user can overwrite with
            // exact field coordinates at any time.
            document.getElementById('inpLat').value = parseFloat(w.lat).toFixed(7);
            document.getElementById('inpLng').value = parseFloat(w.lng).toFixed(7);
        }
    });
})();
</script>
@endpush

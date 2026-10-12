@extends('layouts.app')
@section('title', 'Reports')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<h1 class="fs-4 mb-3">Report builder</h1>
<p class="text-muted small">Combine any criteria — one, several, or none (none = full general report). Then view on screen or export.</p>

<div class="card mb-4"><div class="card-body">
    <form method="GET" action="{{ route('reports.build') }}" class="row g-3" id="reportForm">
        @if (auth()->user()->seesAllDirectorates())
        <div class="col-md-12">
            <label class="form-label">Directorates <span class="text-muted small">(multiple allowed)</span></label>
            <select name="directorates[]" multiple class="form-select js-multi" placeholder="All directorates...">
                @foreach (\App\Models\Project::DIRECTORATES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </div>
        @endif
        <div class="col-md-6">
            <label class="form-label">Contractors <span class="text-muted small">(multiple allowed)</span></label>
            <select name="contractor_ids[]" multiple class="form-select js-multi" placeholder="All contractors...">
                @foreach ($contractors as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Statuses <span class="text-muted small">(multiple allowed)</span></label>
            <select name="statuses[]" multiple class="form-select js-multi" placeholder="All statuses...">
                @foreach (\App\Models\Project::STATUS_LABELS as $s => $label)
                    <option value="{{ $s }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Districts</label>
            <select name="district_ids[]" multiple class="form-select js-multi" placeholder="All districts...">
                @foreach ($districts as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">LGAs</label>
            <select name="lga_ids[]" multiple class="form-select js-multi" placeholder="All LGAs...">
                @foreach ($lgas as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Wards</label>
            <select name="ward_ids[]" multiple class="form-select js-multi" placeholder="All wards...">
                @foreach ($wards as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">From <span class="text-muted small">(award / payment date)</span></label>
            <input type="date" name="date_from" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Report type</label>
            <select name="report" class="form-select">
                <option value="projects">Project summary</option>
                <option value="financial">Financial report</option>
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button name="format" value="html" class="btn btn-primary"><i class="bi bi-eye me-1"></i>View report</button>
            @if (auth()->user()->hasPermission('reports.export'))
                <button name="format" value="csv" class="btn btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</button>
                <button name="format" value="xls" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                <button name="format" value="pdf" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
            @endif
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary ms-auto"><i class="bi bi-x-circle me-1"></i>Reset filter</a>
        </div>
    </form>
</div></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.querySelectorAll('.js-multi').forEach(el => new TomSelect(el, { plugins: ['remove_button'], maxOptions: 400 }));
</script>
@endpush

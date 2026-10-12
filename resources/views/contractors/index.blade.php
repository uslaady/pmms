@extends('layouts.app')
@section('title', 'Contractors')
@section('content')
@php
    $u = auth()->user();
    $canRegister = $u->hasPermission('contractors.register');
    $canStatus = $u->hasPermission('contractors.status');
    $statusBadge = \App\Models\Contractor::STATUS_BADGES;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="fs-4 mb-0">Contractors</h1>
    @if ($canRegister)
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#bulkContractors">Bulk upload</button>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newContractor">Register contractor</button>
    </div>
    @endif
</div>

<div class="alert alert-light border small">
    <i class="bi bi-diagram-2 me-1"></i><strong>How it works:</strong>
    the Director Planning registers a contractor and uploads its documents (status <em>Pending</em>) →
    the Permanent Secretary checks them and makes it <em>Active</em> (or <em>Rejects</em> it with a reason, so it can be corrected and resubmitted) →
    the Director Planning assigns Active contractors to approved projects.
    Deactivated, Suspended and Blacklisted contractors are <i class="bi bi-lock-fill"></i> locked: they keep their existing projects but cannot get new ones.
</div>

{{-- Bulk upload results --}}
@if (session('bulk_results'))
    @php $res = session('bulk_results'); @endphp
    <div class="alert {{ count($res['errors']) ? 'alert-warning' : 'alert-success' }}">
        <strong>{{ $res['created'] }}</strong> contractor(s) registered as Pending.
        @if (count($res['errors']))<strong>{{ count($res['errors']) }}</strong> row(s) skipped — see the reasons below.@endif
    </div>
    @if (count($res['errors']))
    <div class="card mb-4"><div class="table-responsive">
        <table class="table table-sm mb-0 small">
            <thead><tr><th style="width:80px">Row</th><th style="width:240px">Name</th><th>Reasons</th></tr></thead>
            <tbody>@foreach ($res['errors'] as $err)<tr><td>{{ $err['row'] }}</td><td>{{ $err['code'] }}</td><td>{{ implode('; ', $err['reasons']) }}</td></tr>@endforeach</tbody>
        </table>
    </div></div>
    @endif
@endif

@if ($canRegister)
<div class="collapse mb-4" id="bulkContractors"><div class="card card-body">
    <h2 class="fs-6">Bulk contractor registration</h2>
    <ol class="small text-muted mb-3">
        <li><a href="{{ route('contractors.bulk.template') }}">Download the CSV template</a> (one example row you can delete).</li>
        <li>One contractor per row. Only <code>name</code> is required; names must be unique.</li>
        <li>Valid rows are registered as <em>Pending</em>; invalid rows are skipped and listed with the reason. Upload each contractor's documents from its page afterwards.</li>
    </ol>
    <form method="POST" action="{{ route('contractors.bulk.store') }}" enctype="multipart/form-data" class="row g-2">
        @csrf
        <div class="col-md-6"><input type="file" name="file" accept=".csv,text/csv" class="form-control" required></div>
        <div class="col-md-3"><button class="btn btn-success w-100">Upload and register</button></div>
    </form>
</div></div>

<div class="collapse mb-4 {{ $errors->any() && old('name') !== null ? 'show' : '' }}" id="newContractor"><div class="card card-body">
    <h2 class="fs-6 text-muted">Register contractor <span class="small">(starts as Pending until the Permanent Secretary activates it)</span></h2>
    <form method="POST" action="{{ route('contractors.store') }}" class="row g-2">
        @csrf
        <div class="col-md-4"><label class="form-label small mb-1">Company name *</label><input name="name" value="{{ old('name') }}" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><label class="form-label small mb-1">RC number</label><input name="rc_number" value="{{ old('rc_number') }}" class="form-control form-control-sm"></div>
        <div class="col-md-2"><label class="form-label small mb-1">Tax ID (TIN)</label><input name="tax_id" value="{{ old('tax_id') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Director</label><input name="director" value="{{ old('director') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Contact person</label><input name="contact_person" value="{{ old('contact_person') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Email</label><input name="email" type="email" value="{{ old('email') }}" class="form-control form-control-sm"></div>
        <div class="col-md-4"><label class="form-label small mb-1">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control form-control-sm"></div>
        <div class="col-12"><label class="form-label small mb-1">Address</label><input name="address" value="{{ old('address') }}" class="form-control form-control-sm"></div>
        <div class="col-12"><button class="btn btn-sm btn-primary">Register contractor</button></div>
    </form>
</div></div>
@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or RC number"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (\App\Models\Contractor::ALL_STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
    @if (request()->hasAny(['q', 'status']))<div class="col-md-2"><a href="{{ route('contractors.index') }}" class="btn btn-outline-danger w-100">Reset</a></div>@endif
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>RC number</th><th>Contact</th><th class="text-end">Projects</th><th>Status</th><th style="min-width:240px">Actions</th></tr></thead>
            <tbody>
            @forelse ($contractors as $c)
                <tr>
                    <td><a href="{{ route('contractors.show', $c) }}">{{ $c->name }}</a></td>
                    <td>{{ $c->rc_number }}</td>
                    <td class="small">{{ $c->contact_person }} {{ $c->phone }}</td>
                    <td class="text-end">{{ $c->projects_count }}</td>
                    <td><span class="badge bg-{{ $statusBadge[$c->status] ?? 'secondary' }}">@if ($c->isFrozen())<i class="bi bi-lock-fill me-1"></i>@endif{{ ucfirst($c->status) }}</span></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            @if ($canStatus)
                            <form method="POST" action="{{ route('contractors.status', $c) }}" class="d-flex gap-1">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="width:auto">
                                    @foreach (\App\Models\Contractor::STATUSES as $s)
                                        <option value="{{ $s }}" @selected($c->status === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary">Set</button>
                            </form>
                            @endif
                            <a href="{{ route('contractors.show', $c) }}" class="btn btn-sm btn-outline-secondary">
                                {{ $c->canBeEditedBy($u) ? 'View / edit' : 'View' }}</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No contractors found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $contractors->links() }}</div>
@endsection

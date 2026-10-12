@extends('layouts.app')
@section('title', $contractor->name)
@section('content')
@php
    $u = auth()->user();
    $canEdit = $contractor->canBeEditedBy($u);
    $canDocs = $u->hasPermission('contractors.documents') && $canEdit;
    $statusBadge = \App\Models\Contractor::STATUS_BADGES;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <a href="{{ route('contractors.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Contractors</a>
        <h1 class="fs-4 mb-1">{{ $contractor->name }}</h1>
        <span class="badge bg-{{ $statusBadge[$contractor->status] ?? 'secondary' }}">@if ($contractor->isFrozen())<i class="bi bi-lock-fill me-1"></i>@endif{{ ucfirst($contractor->status) }}</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($u->hasPermission('contractors.status'))
        <form method="POST" action="{{ route('contractors.status', $contractor) }}" class="d-flex gap-1">
            @csrf
            <select name="status" class="form-select form-select-sm" style="width:auto">
                @foreach (\App\Models\Contractor::STATUSES as $s)<option value="{{ $s }}" @selected($contractor->status === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button class="btn btn-sm btn-outline-primary">Set status</button>
        </form>
        @endif
        @if ($contractor->status === 'pending' && $u->hasPermission('contractors.status'))
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectContractor"><i class="bi bi-x-circle me-1"></i>Reject</button>
        @endif
        @if ($contractor->canBeDeletedBy($u))
        <form method="POST" action="{{ route('contractors.destroy', $contractor) }}"
              onsubmit="return confirm('Delete {{ addslashes($contractor->name) }}? Its name will remain on its existing projects.');">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
        @endif
    </div>
</div>

@if ($contractor->status === 'pending' && $u->hasPermission('contractors.status'))
<div class="collapse mb-3" id="rejectContractor"><div class="card card-body border-danger">
    <form method="POST" action="{{ route('contractors.reject', $contractor) }}">
        @csrf
        <label class="form-label">Reason for rejecting this contractor <span class="text-muted small">(sent to the Director Planning to correct it)</span></label>
        <textarea name="rejection_reason" rows="2" class="form-control mb-2" required>{{ old('rejection_reason') }}</textarea>
        <button class="btn btn-sm btn-danger">Reject contractor</button>
    </form>
</div></div>
@endif

@if ($contractor->status === 'pending')
    <div class="alert alert-info"><i class="bi bi-hourglass-split me-1"></i>Pending: the Permanent Secretary must review the details and documents and make this contractor Active (or reject it) before it can be assigned to projects.</div>
@elseif ($contractor->isRejected())
    <div class="alert alert-danger"><i class="bi bi-x-octagon me-1"></i><strong>Rejected by the Permanent Secretary.</strong>
        <div class="mt-1"><strong>Reason:</strong> {{ $contractor->rejection_reason }}</div>
        <div class="small mt-1">Correct the details or documents below, then resubmit it for approval.</div>
        @if ($u->hasPermission('contractors.edit'))
        <form method="POST" action="{{ route('contractors.resubmit', $contractor) }}" class="mt-2">
            @csrf <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Resubmit for approval</button>
        </form>
        @endif
    </div>
@elseif ($contractor->isFrozen())
    <div class="alert alert-secondary"><i class="bi bi-lock-fill me-1"></i>This contractor is {{ $contractor->status }} and locked. Its details and documents are read-only
        (only the Super Admin, Commissioner or Permanent Secretary can edit or delete it). Its existing projects continue and can still be updated, but it cannot be assigned new projects.</div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Company details</h2>
            @if ($canEdit)
            <form method="POST" action="{{ route('contractors.update', $contractor) }}" class="row g-2">
                @csrf @method('PUT')
                <div class="col-12"><label class="form-label small mb-1">Company name *</label><input name="name" value="{{ old('name', $contractor->name) }}" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small mb-1">RC number</label><input name="rc_number" value="{{ old('rc_number', $contractor->rc_number) }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small mb-1">Tax ID (TIN)</label><input name="tax_id" value="{{ old('tax_id', $contractor->tax_id) }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small mb-1">Director</label><input name="director" value="{{ old('director', $contractor->director) }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small mb-1">Contact person</label><input name="contact_person" value="{{ old('contact_person', $contractor->contact_person) }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small mb-1">Email</label><input name="email" type="email" value="{{ old('email', $contractor->email) }}" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label small mb-1">Phone</label><input name="phone" value="{{ old('phone', $contractor->phone) }}" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label small mb-1">Address</label><input name="address" value="{{ old('address', $contractor->address) }}" class="form-control form-control-sm"></div>
                <div class="col-12"><button class="btn btn-sm btn-success">Save changes</button></div>
            </form>
            @else
            <dl class="row small mb-0">
                <dt class="col-4">RC number</dt><dd class="col-8">{{ $contractor->rc_number ?? '—' }}</dd>
                <dt class="col-4">Tax ID (TIN)</dt><dd class="col-8">{{ $contractor->tax_id ?? '—' }}</dd>
                <dt class="col-4">Director</dt><dd class="col-8">{{ $contractor->director ?? '—' }}</dd>
                <dt class="col-4">Contact person</dt><dd class="col-8">{{ $contractor->contact_person ?? '—' }}</dd>
                <dt class="col-4">Email</dt><dd class="col-8">{{ $contractor->email ?? '—' }}</dd>
                <dt class="col-4">Phone</dt><dd class="col-8">{{ $contractor->phone ?? '—' }}</dd>
                <dt class="col-4">Address</dt><dd class="col-8">{{ $contractor->address ?? '—' }}</dd>
            </dl>
            @endif
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Registration documents</h2>
            <table class="table table-sm small mb-2">
                <thead><tr><th>Type</th><th>Title</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                @forelse ($contractor->documents as $doc)
                    <tr>
                        <td>{{ $doc->type_label }}</td>
                        <td>{{ $doc->title }}</td>
                        <td class="text-muted">{{ $doc->created_at->format('d M Y') }}<br>{{ $doc->uploader?->name }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('contractors.documents.download', $doc) }}">Download</a>
                            @if ($canDocs)
                            <form method="POST" action="{{ route('contractors.documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Remove this document?');">
                                @csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0 ms-2">Remove</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No documents uploaded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            @if ($canDocs)
            <form method="POST" action="{{ route('contractors.documents.store', $contractor) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-5">
                    <select name="type" class="form-select form-select-sm">
                        @foreach (\App\Models\ContractorDocument::TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-7"><input name="title" class="form-control form-control-sm" placeholder="Title, e.g. Tax clearance 2026" required></div>
                <div class="col-md-8"><input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control form-control-sm" required></div>
                <div class="col-md-4"><button class="btn btn-sm btn-outline-primary w-100">Upload</button></div>
                <div class="col-12 form-text">PDF, Word, JPG or PNG up to 5 MB. Documents are private — only signed-in users who can open this page can download them.</div>
            </form>
            @endif
        </div></div>
    </div>
</div>

<div class="card mt-3"><div class="card-body">
    <h2 class="fs-6 text-muted">Projects ({{ $contractor->projects->count() }})</h2>
    <table class="table table-sm small mb-0">
        <thead><tr><th>Code</th><th>Name</th><th>Directorate</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($contractor->projects as $p)
            <tr>
                <td>@if (auth()->user()->seesAllDirectorates() || auth()->user()->directorate() === $p->directorate)<a href="{{ route('projects.show', $p) }}">{{ $p->project_code }}</a>@else{{ $p->project_code }}@endif</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->directorate_label }}</td>
                <td><span class="badge" style="background: {{ $p->status_color }}">{{ $p->status_label }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">No projects assigned yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection

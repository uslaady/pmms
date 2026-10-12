@extends('layouts.app')
@section('title', $project->project_code)

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
@php
    $u = auth()->user();
    $work = $project->acceptsWork();          // approved and not locked
    $canEdit = $project->canBeEditedBy($u);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h1 class="fs-4 mb-1">{{ $project->name }}</h1>
        <span class="text-muted">{{ $project->project_code }}</span>
        <span class="badge ms-2" style="background: {{ $project->status_color }}">{{ $project->status_label }}</span>
        <span class="badge bg-secondary ms-1">{{ $project->priority }} priority</span>
        <span class="badge bg-dark ms-1"><i class="bi bi-diagram-3 me-1"></i>{{ $project->directorate_label }}</span>
        @if ($project->funded_by)<span class="badge bg-light text-dark border ms-1"><i class="bi bi-bank me-1"></i>Funded by {{ $project->funded_by }}</span>@endif
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
    @if ($canEdit)
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editProject"><i class="bi bi-pencil me-1"></i>Edit</button>
    @endif
    @if ($u->hasPermission('projects.delete'))
        <form method="POST" action="{{ route('projects.destroy', $project) }}"
              onsubmit="return confirm('Delete project {{ $project->project_code }}? This can only be undone from the database.');">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
    @endif
    @if ($u->hasPermission('projects.approve'))
    @if ($project->isPending())
        <form method="POST" action="{{ route('projects.status', $project) }}">
            @csrf <input type="hidden" name="status" value="planned">
            <button class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Approve project</button>
        </form>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectProject"><i class="bi bi-x-circle me-1"></i>Reject</button>
    @endif
    <form method="POST" action="{{ route('projects.status', $project) }}" class="d-flex gap-2">
        @csrf
        <select name="status" class="form-select form-select-sm">
            @foreach (\App\Models\Project::STATUS_LABELS as $s => $label)
                @continue($s === 'rejected' && ! $project->isRejected())
                <option value="{{ $s }}" @selected($project->status === $s) @disabled($s === 'rejected')>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-primary">Update status</button>
    </form>
    @endif
    </div>
</div>

@if ($canEdit)
<div class="collapse mb-3" id="editProject">
    <div class="card card-body">
        <h2 class="fs-6 text-muted">Edit project</h2>
        <form method="POST" action="{{ route('projects.update', $project) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-3"><label class="form-label">Project code</label>
                <input name="project_code" value="{{ old('project_code', $project->project_code) }}" class="form-control" required></div>
            <div class="col-md-5"><label class="form-label">Project name</label>
                <input name="name" value="{{ old('name', $project->name) }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Directorate</label>
                @if ($u->seesAllDirectorates())
                <select name="directorate" class="form-select" required>
                    @foreach (\App\Models\Project::DIRECTORATES as $key => $label)
                        <option value="{{ $key }}" @selected(old('directorate', $project->directorate) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @else
                <input class="form-control" value="{{ $project->directorate_label }}" disabled>
                @endif
            </div>
            <div class="col-md-4"><label class="form-label">District</label>
                <select id="selDistrict" name="district_id" class="form-select" placeholder="Type to search district..."><option value="">—</option></select></div>
            <div class="col-md-4"><label class="form-label">LGA</label>
                <select id="selLga" name="lga_id" class="form-select" placeholder="Select district first..."><option value="">—</option></select></div>
            <div class="col-md-4"><label class="form-label">Ward</label>
                <select id="selWard" name="ward_id" class="form-select" placeholder="Select LGA first..."><option value="">—</option></select></div>
            <div class="col-md-3"><label class="form-label">Latitude <span class="text-muted small">(auto from ward, editable)</span></label>
                <input id="inpLat" name="latitude" value="{{ old('latitude', $project->latitude) }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Longitude <span class="text-muted small">(auto from ward, editable)</span></label>
                <input id="inpLng" name="longitude" value="{{ old('longitude', $project->longitude) }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Award date</label>
                <input type="date" name="award_date" value="{{ old('award_date', $project->award_date?->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Commencement</label>
                <input type="date" name="commencement_date" value="{{ old('commencement_date', $project->commencement_date?->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Expected completion</label>
                <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date', $project->expected_completion_date?->format('Y-m-d')) }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Funded by</label>
                <input name="funded_by" value="{{ $project->funded_by ?? 'Borno State Govt.' }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                    @foreach (['low','medium','high','critical'] as $p)
                        <option value="{{ $p }}" @selected($project->priority === $p)>{{ $p }}</option>
                    @endforeach
                </select></div>
            <div class="col-md-9"><label class="form-label">Description</label>
                <input name="description" value="{{ old('description', $project->description) }}" class="form-control"></div>
            <div class="col-12"><button class="btn btn-success">Save changes</button></div>
        </form>
    </div>
</div>
@endif

@if ($project->isFrozen())
    <div class="alert alert-secondary d-flex gap-2 align-items-start">
        <i class="bi bi-lock-fill fs-5"></i>
        <div><strong>This project is {{ $project->status_label }} and locked.</strong>
            All details are read-only: no updates, photos, inspections, milestones, documents or payments can be added or removed.
            Only the Super Admin, Commissioner or Permanent Secretary can edit it, change its status (to reopen it) or delete it.</div>
    </div>
@elseif ($project->isRejected())
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-x-octagon fs-5"></i>
        <div class="flex-grow-1"><strong>Rejected by the Permanent Secretary.</strong>
            <div class="mt-1"><strong>Reason:</strong> {{ $project->rejection_reason }}</div>
            <div class="small mt-1">Correct the project (Edit), then resubmit it for approval.</div>
            @if ($canEdit)
            <form method="POST" action="{{ route('projects.resubmit', $project) }}" class="mt-2">
                @csrf <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Resubmit for approval</button>
            </form>
            @endif
        </div>
    </div>
@elseif ($project->isPending())
    <div class="alert alert-warning d-flex gap-2 align-items-start">
        <i class="bi bi-hourglass-split fs-5"></i>
        <div><strong>Awaiting approval by the Permanent Secretary.</strong>
            The details and documents can still be edited. A contractor can be assigned, and contracts, payments and site
            updates recorded, once the project is approved.</div>
    </div>
@endif

@if ($project->isPending() && $u->hasPermission('projects.approve'))
<div class="collapse mb-3" id="rejectProject"><div class="card card-body border-danger">
    <form method="POST" action="{{ route('projects.reject', $project) }}">
        @csrf
        <label class="form-label">Reason for rejecting this project <span class="text-muted small">(sent to the Director to correct it)</span></label>
        <textarea name="rejection_reason" rows="2" class="form-control mb-2" required>{{ old('rejection_reason') }}</textarea>
        <button class="btn btn-sm btn-danger">Reject project</button>
    </form>
</div></div>
@endif

@if ($u->hasPermission('projects.assign_contractor') && $project->isActive())
<div class="card mb-3 border-primary"><div class="card-body">
    <h2 class="fs-6 text-muted mb-2"><i class="bi bi-person-badge me-1"></i>{{ $project->contractor ? 'Change contractor' : 'Assign contractor' }}
        <span class="small">(Active contractors only)</span></h2>
    <form method="POST" action="{{ route('projects.contractor.assign', $project) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-6">
            <select name="contractor_id" class="form-select form-select-sm" required>
                <option value="">— select an Active contractor —</option>
                @foreach (\App\Models\Contractor::active()->orderBy('name')->get() as $c)
                    <option value="{{ $c->id }}" @selected($project->contractor_id === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">{{ $project->contractor ? 'Change contractor' : 'Assign contractor' }}</button></div>
    </form>
</div></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Overview</h2>
            <div class="row">
                <div class="col-sm-6">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Contractor</dt><dd class="col-7">{{ $project->contractor?->name ?? 'Not yet assigned' }}
                            @if ($project->contractor && $project->contractor->status !== 'active')
                                <span class="badge bg-warning text-dark">{{ $project->contractor->trashed() ? 'deleted' : $project->contractor->status }}</span>
                                <div class="small text-muted">Existing work continues; this contractor cannot receive new projects.</div>
                            @endif</dd>
                        <dt class="col-5">Consultant</dt><dd class="col-7">{{ $project->consultant?->name ?? '—' }}</dd>
                        <dt class="col-5">Engineer</dt><dd class="col-7">{{ $project->supervisingEngineer?->name ?? '—' }}</dd>
                        <dt class="col-5">Directorate</dt><dd class="col-7">{{ $project->directorate_label }}</dd>
                        <dt class="col-5">Location</dt><dd class="col-7">{{ collect([$project->district?->name, $project->lga?->name, $project->ward?->name])->filter()->implode(' › ') ?: '—' }}</dd>
                    </dl>
                </div>
                <div class="col-sm-6">
                    <dl class="row mb-0 small">
                        <dt class="col-6">Award date</dt><dd class="col-6">{{ $project->award_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-6">Commencement</dt><dd class="col-6">{{ $project->commencement_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-6">Expected completion</dt><dd class="col-6">{{ $project->expected_completion_date?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-4"><div class="text-muted small">Planned</div><div class="fs-4">{{ $project->planned_progress_percent }}%</div></div>
                <div class="col-4"><div class="text-muted small">Actual</div><div class="fs-4">{{ $project->actual_progress_percent }}%</div></div>
                <div class="col-4"><div class="text-muted small">Deficit</div><div class="fs-4 {{ $project->delay_days > 10 ? 'text-danger' : '' }}">{{ $project->delay_days }}%</div></div>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div id="miniMap" style="height:100%;min-height:260px;border-radius:inherit"></div>
        </div>
    </div>
</div>

{{-- Financial summary --}}
<div class="card mb-3"><div class="card-body">
    <h2 class="fs-6 text-muted">Financials</h2>
    @if ($project->contract)
        @php $c = $project->contract; @endphp
        <div class="row text-center mb-3">
            <div class="col"><div class="text-muted small">Contract value</div><div class="fw-bold">₦{{ number_format($c->contract_value, 2) }}</div></div>
            <div class="col"><div class="text-muted small">Revised value</div><div class="fw-bold">{{ $c->revised_value ? '₦'.number_format($c->revised_value, 2) : '—' }}</div></div>
            <div class="col"><div class="text-muted small">Total paid</div><div class="fw-bold">₦{{ number_format($c->total_paid, 2) }}</div></div>
            <div class="col"><div class="text-muted small">Outstanding</div><div class="fw-bold">₦{{ number_format($c->outstanding_balance, 2) }}</div></div>
        </div>
        <table class="table table-sm small mb-2">
            <thead><tr><th>Date</th><th>Certificate</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($c->payments as $pay)
                <tr>
                    <td>{{ $pay->payment_date->format('d M Y') }}</td>
                    <td>{{ $pay->certificate_number }}</td>
                    <td class="text-end">₦{{ number_format($pay->amount, 2) }}</td>
                    <td><span class="badge bg-{{ ['pending'=>'warning','approved'=>'info','paid'=>'success','rejected'=>'danger'][$pay->status] }}">{{ $pay->status }}</span></td>
                    <td>
                        @if ($pay->status === 'pending' && ($work && $u->hasPermission('payments.approve')))
                            <form method="POST" action="{{ route('payments.approve', $pay) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                        @elseif ($pay->status === 'approved' && ($work && $u->hasPermission('payments.record')))
                            <form method="POST" action="{{ route('payments.markpaid', $pay) }}"
                                  onsubmit="return confirm('Confirm the funds for this certificate have been disbursed?');">
                                @csrf<button class="btn btn-sm btn-success">Mark paid</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if (($work && $u->hasPermission('contracts.manage')))
        <details class="mb-3">
            <summary class="small text-muted" style="cursor:pointer">Record variation order / extension of time
                <span class="text-muted">(variation orders update the Revised Value automatically)</span></summary>
            <form method="POST" action="{{ route('contracts.variations.store', $c) }}" class="row g-2 align-items-end mt-1">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm" onchange="this.form.querySelector('[name=amount]').closest('div').style.display = this.value==='variation_order' ? '' : 'none'; this.form.querySelector('[name=extra_days]').closest('div').style.display = this.value==='extension_of_time' ? '' : 'none';">
                        <option value="variation_order">Variation order (₦)</option>
                        <option value="extension_of_time">Extension of time (days)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Amount (₦)</label>
                    <input name="amount" type="text" inputmode="decimal" class="form-control form-control-sm money" placeholder="e.g. 15,000,000">
                </div>
                <div class="col-md-2" style="display:none">
                    <label class="form-label small mb-1">Extra days</label>
                    <input name="extra_days" type="number" min="1" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Date</label>
                    <input name="date" type="date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Reason</label>
                    <input name="reason" class="form-control form-control-sm" required>
                </div>
                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Record variation</button></div>
            </form>
        </details>
        @endif
        @if (($work && $u->hasPermission('payments.record')))
        <form method="POST" action="{{ route('payments.store', $c) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small mb-1">Date</label>
                <input name="payment_date" type="date" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Certificate No</label>
                <input name="certificate_number" class="form-control form-control-sm" placeholder="e.g. IPC-4">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Amount (₦)</label>
                <input name="amount" type="text" inputmode="decimal" class="form-control form-control-sm money" placeholder="e.g. 25,000,000.00" required>
            </div>
            <div class="col-md-3"><button class="btn btn-sm btn-outline-primary w-100">Record payment</button></div>
        </form>
        @endif
    @elseif (($work && $u->hasPermission('contracts.manage')))
        <form method="POST" action="{{ route('projects.contract.store', $project) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small mb-1">Contract number</label>
                <input name="contract_number" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Award date</label>
                <input name="award_date" type="date" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Contract value (₦)</label>
                <input name="contract_value" type="text" inputmode="decimal" class="form-control form-control-sm money" placeholder="e.g. 450,000,000.00" required>
            </div>
            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Register contract</button></div>
        </form>
    @else
        <p class="text-muted mb-0">No contract registered yet.</p>
    @endif
</div></div>

{{-- Timeline / milestones (Gantt-style) --}}
<div class="card mb-3"><div class="card-body">
    <h2 class="fs-6 text-muted">Milestones</h2>
    @foreach ($project->milestones as $m)
        <div class="d-flex align-items-center gap-2 py-1 border-bottom small">
            <span style="width:20px">
                @if ($m->status === 'completed')<i class="bi bi-check-circle-fill text-success"></i>
                @elseif ($m->status === 'in_progress')<i class="bi bi-arrow-repeat text-primary"></i>
                @elseif ($m->status === 'delayed')<i class="bi bi-exclamation-circle-fill text-danger"></i>
                @else<i class="bi bi-circle text-muted"></i>@endif
            </span>
            <span class="flex-grow-1">{{ $m->name }}</span>
            <span class="text-muted">planned {{ $m->planned_date?->format('d M Y') ?? '—' }}</span>
            <span class="text-muted">actual {{ $m->actual_date?->format('d M Y') ?? '—' }}</span>
            @if (($work && $u->hasPermission('milestones.update')))
            <form method="POST" action="{{ route('milestones.update', $m) }}" class="d-flex gap-1">
                @csrf
                <select name="status" class="form-select form-select-sm" style="width:auto">
                    @foreach (['pending','in_progress','completed','delayed'] as $s)
                        <option value="{{ $s }}" @selected($m->status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="actual_date" value="{{ now()->format('Y-m-d') }}">
                <button class="btn btn-sm btn-outline-secondary">Set</button>
            </form>
            @endif
        </div>
    @endforeach
    @if (($work && $u->hasPermission('milestones.update')))
    <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="row g-2 mt-2">
        @csrf
        <div class="col-md-5"><input name="name" class="form-control form-control-sm" placeholder="Milestone name" required></div>
        <div class="col-md-3"><input name="planned_date" type="date" class="form-control form-control-sm"></div>
        <div class="col-md-2"><input name="sequence" type="number" class="form-control form-control-sm" placeholder="Order"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Add milestone</button></div>
    </form>
    @endif
</div></div>

{{-- Progress updates with approval workflow --}}
<div class="card mb-3"><div class="card-body">
    <h2 class="fs-6 text-muted">Progress updates</h2>
    <table class="table table-sm small">
        <thead><tr><th>Date</th><th>By</th><th class="text-end">Physical %</th><th>Narrative</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($project->updates as $up)
            <tr>
                <td>{{ $up->created_at->format('d M Y') }}</td>
                <td>{{ $up->submitter?->name }}</td>
                <td class="text-end">{{ $up->physical_progress_percent }}%</td>
                <td>{{ Str::limit($up->narrative, 60) }}</td>
                <td><span class="badge bg-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$up->approval_status] }}">{{ $up->approval_status }}</span></td>
                <td>
                    @if ($up->approval_status === 'pending' && ($work && $u->hasPermission('progress.approve')))
                        <form method="POST" action="{{ route('updates.review', $up) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-outline-success">Approve</button></form>
                        <form method="POST" action="{{ route('updates.review', $up) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-3">No progress updates submitted yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if (($work && $u->hasPermission('progress.update')))
    <hr>
    <h3 class="fs-6 text-muted">Submit new update</h3>
    <form method="POST" action="{{ route('projects.progress.store', $project) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3">
            <label class="form-label small mb-1">Physical progress — % complete (0–100)</label>
            <input name="physical_progress_percent" type="number" min="0" max="100" step="0.1" class="form-control form-control-sm" placeholder="e.g. 45" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1">Narrative — what was done</label>
            <input name="narrative" class="form-control form-control-sm" placeholder="e.g. Completed drainage on section B">
        </div>
        <div class="col-md-3"><button class="btn btn-sm btn-outline-primary w-100">Submit update</button></div>
    </form>
    @endif
</div></div>

{{-- Photo gallery --}}
<div class="card mb-3"><div class="card-body">
    <h2 class="fs-6 text-muted">Site photos</h2>
    <div class="row g-2 mb-2">
        @forelse ($project->images as $img)
            <div class="col-6 col-md-3 col-lg-2">
                <a href="{{ Storage::url($img->path) }}" target="_blank">
                    <img src="{{ Storage::url($img->path) }}" class="img-fluid rounded" alt="{{ $img->caption }}">
                </a>
                <div class="small text-muted d-flex justify-content-between align-items-center">
                    <span>{{ $img->stage }} · {{ $img->created_at->format('d M Y') }}</span>
                    @if (($work && $u->hasPermission('images.upload')))
                        <form method="POST" action="{{ route('images.destroy', $img) }}"
                              onsubmit="return confirm('Remove this photo? To replace it, upload the new photo after removing.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0" title="Remove photo"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No photos yet.</p>
        @endforelse
    </div>
    @if (($work && $u->hasPermission('images.upload')))
    <form method="POST" action="{{ route('projects.images.store', $project) }}" enctype="multipart/form-data" class="row g-2" id="photoForm">
        @csrf
        <div class="col-md-4"><input type="file" name="image" id="photoFile" accept="image/*" capture="environment" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><select name="stage" class="form-select form-select-sm"><option>during</option><option>before</option><option>after</option></select></div>
        <div class="col-md-3"><input name="caption" class="form-control form-control-sm" placeholder="Caption"></div>
        <div class="col-md-3"><button class="btn btn-sm btn-outline-primary w-100" id="photoBtn">Upload photo</button></div>
        <input type="hidden" name="gps_lat" id="gps_lat"><input type="hidden" name="gps_lng" id="gps_lng">
        <div class="col-12" id="photoStatus" style="display:none">
            <div class="small mb-1" id="photoStatusText" role="status" aria-live="polite"></div>
            <div class="progress" style="height:8px"><div class="progress-bar bg-success" id="photoProgress" style="width:0%"></div></div>
        </div>
        <div class="col-12"><div class="alert alert-danger py-2 small mb-0" id="photoError" style="display:none"></div></div>
    </form>
    @endif
</div></div>

{{-- Documents, inspections, risks, issues in a row --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Documents</h2>
            <ul class="list-unstyled small mb-2">
                @foreach ($project->documents as $doc)
                    <li class="py-1 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark me-1"></i>{{ $doc->title }} <span class="text-muted">({{ $doc->type }} · {{ strtoupper(pathinfo($doc->path, PATHINFO_EXTENSION)) }})</span></span>
                        <span class="d-flex gap-2 align-items-center">
                            <a href="{{ route('documents.download', $doc) }}">Download</a>
                            @if ((! $project->isFrozen() && $u->hasPermission('documents.upload')))
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('Delete this document? To replace it, upload the new version after deleting.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger p-0" title="Delete document"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
            @if ((! $project->isFrozen() && $u->hasPermission('documents.upload')))
            <form method="POST" action="{{ route('projects.documents.store', $project) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-7"><input name="title" class="form-control form-control-sm" placeholder="Title" required></div>
                <div class="col-md-5"><select name="type" class="form-select form-select-sm">@foreach (\App\Http\Controllers\DocumentController::TYPES as $t)<option>{{ $t }}</option>@endforeach</select></div>
                <div class="col-12"><input type="file" name="file" id="docFile" class="form-control form-control-sm" required>
                    <div class="form-text">Maximum size: 1 MB. Documents are not compressed — reduce the file size before uploading if needed.</div>
                </div>
                <div class="col-12"><div class="alert alert-danger py-2 small mb-0" id="docError" style="display:none"></div></div>
                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Upload document</button></div>
            </form>
            @endif
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Inspections</h2>
            @foreach ($project->inspections as $insp)
                <div class="border-bottom py-1 small">
                    <div class="d-flex justify-content-between align-items-start">
                        <span>
                            <strong>{{ $insp->inspection_date->format('d M Y') }}</strong> — {{ $insp->officer?->name }}
                            · risk <span class="badge bg-{{ ['low'=>'success','medium'=>'warning','high'=>'danger'][$insp->risk_level] }}">{{ $insp->risk_level }}</span>
                            @if ($insp->score) · score {{ $insp->score }}/100 @endif
                        </span>
                        @if (($work && $u->hasPermission('inspections.create')))
                        <span class="d-flex gap-1">
                            <button class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" data-bs-target="#editInsp{{ $insp->id }}" title="Edit inspection"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('inspections.destroy', $insp) }}"
                                  onsubmit="return confirm('Delete this inspection record?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-link text-danger p-0" title="Delete inspection"><i class="bi bi-trash"></i></button>
                            </form>
                        </span>
                        @endif
                    </div>
                    <div class="text-muted">{{ Str::limit($insp->observations, 100) }}</div>
                    @if (($work && $u->hasPermission('inspections.create')))
                    <div class="collapse mt-1" id="editInsp{{ $insp->id }}">
                        <form method="POST" action="{{ route('inspections.update', $insp) }}" class="row g-1">
                            @csrf @method('PUT')
                            <div class="col-4"><input name="inspection_date" type="date" value="{{ $insp->inspection_date->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                            <div class="col-4"><select name="risk_level" class="form-select form-select-sm">
                                @foreach (['low','medium','high'] as $rl)<option value="{{ $rl }}" @selected($insp->risk_level === $rl)>{{ $rl }}</option>@endforeach
                            </select></div>
                            <div class="col-4"><input name="score" type="number" min="0" max="100" value="{{ $insp->score }}" class="form-control form-control-sm" placeholder="Score (0–100)"></div>
                            <div class="col-12"><textarea name="observations" class="form-control form-control-sm" rows="2">{{ $insp->observations }}</textarea></div>
                            <div class="col-12"><button class="btn btn-sm btn-success">Save inspection</button></div>
                        </form>
                    </div>
                    @endif
                </div>
            @endforeach
            @if (($work && $u->hasPermission('inspections.create')))
            <form method="POST" action="{{ route('projects.inspections.store', $project) }}" class="row g-2 mt-2">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1">Inspection date</label>
                    <input name="inspection_date" type="date" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Risk level</label>
                    <select name="risk_level" class="form-select form-select-sm"><option>low</option><option>medium</option><option>high</option></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Quality score (0–100)</label>
                    <input name="score" type="number" min="0" max="100" class="form-control form-control-sm" placeholder="e.g. 85">
                    <div class="form-text small">100 = fully compliant with specifications; below 50 signals serious quality concerns.</div>
                </div>
                <div class="col-12"><textarea name="observations" class="form-control form-control-sm" placeholder="Observations" rows="2"></textarea></div>
                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Record inspection</button></div>
            </form>
            @endif
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
@if ($project->latitude && $project->longitude)
    // Base layers: standard map + satellite imagery, switchable from the control
    const baseMap = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
    });
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: 'Imagery &copy; Esri, Maxar, Earthstar Geographics',
    });
    // Zoom 16 frames a single project site tightly in the canvas
    const map = L.map('miniMap', { layers: [baseMap] })
        .setView([{{ $project->latitude }}, {{ $project->longitude }}], 16);
    L.control.layers({ 'Map': baseMap, 'Satellite': satellite }, null, { position: 'topright' }).addTo(map);
    L.circleMarker([{{ $project->latitude }}, {{ $project->longitude }}], {
        radius: 10, color: '{{ $project->status_color }}', fillColor: '{{ $project->status_color }}', fillOpacity: .8
    }).addTo(map).bindPopup(@json($project->name));
@else
    document.getElementById('miniMap').innerHTML = '<div class="d-flex h-100 align-items-center justify-content-center text-muted">No coordinates yet</div>';
@endif

// District -> LGA -> Ward cascade on the Edit form, prefilled with current values
(async function () {
    const elDistrict = document.getElementById('selDistrict');
    if (!elDistrict) return; // edit form not rendered for this user

    const res = await fetch('{{ route('locations.tree') }}');
    const districts = await res.json();

    const current = {
        district: @json($project->district_id),
        lga: @json($project->lga_id),
        ward: @json($project->ward_id),
    };

    const tsOpts = { create: false, allowEmptyOption: true, maxOptions: 400, sortField: { field: 'text' } };
    const tsDistrict = new TomSelect('#selDistrict', tsOpts);
    const tsLga = new TomSelect('#selLga', tsOpts);
    const tsWard = new TomSelect('#selWard', tsOpts);
    const wardIndex = {};

    function fillLgas(districtId) {
        tsLga.clearOptions();
        const d = districts.find(x => x.id == districtId);
        if (d) tsLga.addOptions(d.lgas.map(l => ({ value: l.id, text: l.name })));
    }
    function fillWards(districtId, lgaId) {
        tsWard.clearOptions();
        const d = districts.find(x => x.id == districtId);
        const lga = d && d.lgas.find(l => l.id == lgaId);
        if (lga) {
            lga.wards.forEach(w => { wardIndex[w.id] = { lat: w.latitude, lng: w.longitude }; });
            tsWard.addOptions(lga.wards.map(w => ({ value: w.id, text: w.name })));
        }
    }

    tsDistrict.addOptions(districts.map(d => ({ value: d.id, text: d.name })));

    // Prefill with the project's current hierarchy (silent = no change events)
    if (current.district) {
        tsDistrict.setValue(current.district, true);
        fillLgas(current.district);
        if (current.lga) {
            tsLga.setValue(current.lga, true);
            fillWards(current.district, current.lga);
            if (current.ward) tsWard.setValue(current.ward, true);
        }
    }

    tsDistrict.on('change', function (districtId) {
        tsLga.clear(true); tsWard.clear(true); tsWard.clearOptions();
        fillLgas(districtId);
    });
    tsLga.on('change', function (lgaId) {
        tsWard.clear(true);
        fillWards(tsDistrict.getValue(), lgaId);
    });
    tsWard.on('change', function (wardId) {
        const w = wardIndex[wardId];
        if (w && w.lat !== null) {
            document.getElementById('inpLat').value = parseFloat(w.lat).toFixed(7);
            document.getElementById('inpLng').value = parseFloat(w.lng).toFixed(7);
        }
    });
})();

// Capture device GPS for photo uploads (Module 18 — mobile field use)
if (navigator.geolocation && document.getElementById('gps_lat')) {
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('gps_lat').value = pos.coords.latitude.toFixed(7);
        document.getElementById('gps_lng').value = pos.coords.longitude.toFixed(7);
    }, () => {});
}
</script>
<script>
/* ---------- Upload enhancements: precise validation, client-side compression, progress ---------- */
(function () {
    const MB = 1024 * 1024;
    const fmt = b => (b / MB).toFixed(2) + ' MB';

    // ----- Documents: block anything over 1 MB before it leaves the browser -----
    const docFile = document.getElementById('docFile');
    if (docFile) {
        const docError = document.getElementById('docError');
        const check = () => {
            const f = docFile.files[0];
            if (f && f.size > MB) {
                docError.textContent = 'The selected document is ' + fmt(f.size) +
                    '. Documents must not exceed 1 MB. Please reduce the file size before uploading.';
                docError.style.display = '';
                return false;
            }
            docError.style.display = 'none';
            return true;
        };
        docFile.addEventListener('change', check);
        docFile.form.addEventListener('submit', e => { if (!check()) e.preventDefault(); });
    }

    // ----- Photos: compress in the browser when over 1 MB, then upload with a progress bar -----
    const form = document.getElementById('photoForm');
    if (!form) return;
    const input = document.getElementById('photoFile');
    const btn = document.getElementById('photoBtn');
    const status = document.getElementById('photoStatus');
    const statusText = document.getElementById('photoStatusText');
    const bar = document.getElementById('photoProgress');
    const errBox = document.getElementById('photoError');

    const showError = msg => { errBox.textContent = msg; errBox.style.display = ''; status.style.display = 'none'; btn.disabled = false; };
    const setProgress = (pct, text) => {
        status.style.display = ''; errBox.style.display = 'none';
        bar.style.width = pct + '%'; statusText.textContent = text;
    };

    input.addEventListener('change', () => {
        errBox.style.display = 'none';
        const f = input.files[0];
        if (!f) return;
        if (!f.type.startsWith('image/')) {
            showError('The selected file is not an image. Accepted formats: JPG, PNG, GIF, WebP.');
            input.value = '';
        } else if (f.size > MB) {
            setProgress(0, 'This image is ' + fmt(f.size) + ' — over the 1 MB limit. It will be compressed automatically when you click Upload.');
        } else {
            status.style.display = 'none';
        }
    });

    // Canvas-based compression. Browsers apply EXIF orientation when decoding,
    // so the drawn (and re-saved) image keeps its correct orientation.
    async function compress(file) {
        setProgress(15, 'Compressing ' + fmt(file.size) + ' image…');
        const bitmap = await createImageBitmap(file).catch(() => null);
        if (!bitmap) return file; // let the server-side compressor handle it
        let { width, height } = bitmap;
        const maxDim = 1600;
        if (Math.max(width, height) > maxDim) {
            const s = maxDim / Math.max(width, height);
            width = Math.round(width * s); height = Math.round(height * s);
        }
        const canvas = document.createElement('canvas');
        canvas.width = width; canvas.height = height;
        canvas.getContext('2d').drawImage(bitmap, 0, 0, width, height);
        for (let q = 0.85; q >= 0.4; q -= 0.1) {
            setProgress(15 + Math.round((0.85 - q) * 60), 'Optimizing image quality…');
            const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', q));
            if (blob && blob.size <= MB) {
                setProgress(45, 'Compressed to ' + fmt(blob.size) + '.');
                return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
            }
        }
        const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', 0.35));
        return blob && blob.size < file.size
            ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })
            : file;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        let f = input.files[0];
        if (!f) return;
        if (!f.type.startsWith('image/')) {
            return showError('The selected file is not an image. Accepted formats: JPG, PNG, GIF, WebP.');
        }
        btn.disabled = true;
        try {
            if (f.size > MB) {
                f = await compress(f);
                if (f.size > 20 * MB) {
                    return showError('Even after compression this image is ' + fmt(f.size) + ', above the 20 MB hard limit. Please choose a smaller photo.');
                }
            }
            const data = new FormData(form);
            data.set('image', f, f.name);
            setProgress(50, 'Uploading ' + fmt(f.size) + '…');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.addEventListener('progress', ev => {
                if (ev.lengthComputable) setProgress(50 + Math.round(ev.loaded / ev.total * 45), 'Uploading… ' + Math.round(ev.loaded / ev.total * 100) + '%');
            });
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 400) {
                    setProgress(100, 'Photo uploaded successfully — refreshing…');
                    setTimeout(() => window.location.reload(), 500);
                } else if (xhr.status === 422) {
                    let msg = 'The image could not be uploaded.';
                    try { const j = JSON.parse(xhr.responseText); msg = Object.values(j.errors || {}).flat()[0] || j.message || msg; } catch (_) {}
                    showError(msg);
                } else if (xhr.status === 413) {
                    showError('The image is ' + fmt(f.size) + ' — larger than the server accepts. Please choose a smaller photo.');
                } else {
                    showError('Upload failed (server responded ' + xhr.status + '). Please try again; if it persists, contact the administrator.');
                }
            });
            xhr.addEventListener('error', () => showError('Upload failed: the connection was interrupted. Check your network and try again.'));
            xhr.send(data);
        } catch (err) {
            showError('Something went wrong while preparing the image: ' + err.message);
        }
    });
})();
</script>
@endpush

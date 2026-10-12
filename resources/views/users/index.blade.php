@extends('layouts.app')
@section('title', 'Users')
@section('content')
<h1 class="fs-4 mb-3">User management</h1>

@php
    $me = auth()->user();
    $canCreate = $me->hasPermission('users.create');
    $canStatus = $me->hasPermission('users.status');
    $canReset = $me->hasPermission('users.reset_password');
@endphp

@if ($canCreate)
<div class="card mb-4"><div class="card-body">
    <h2 class="fs-6 text-muted">Add user <span class="small">({{ $me->hasRole(\App\Models\Role::SUPER_ADMIN) ? 'any role, including Super Admin'
        : ($me->hasRole(\App\Models\Role::COMMISSIONER) ? 'any role except Super Admin and Commissioner'
        : 'any role except Super Admin, Commissioner and Permanent Secretary') }})</span></h2>
    <form method="POST" action="{{ route('users.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="Full name" required></div>
        <div class="col-md-3"><input name="email" type="email" class="form-control form-control-sm" placeholder="Email" required></div>
        <div class="col-md-2">
            <select name="role_id" class="form-select form-select-sm" required>
                @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->display_name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><input name="password" type="password" class="form-control form-control-sm" placeholder="Password (10+ chars)" required></div>
        <div class="col-md-2"><input name="password_confirmation" type="password" class="form-control form-control-sm" placeholder="Confirm" required></div>
        <div class="col-12"><button class="btn btn-sm btn-primary">Create user</button></div>
    </form>
</div></div>
@endif

@php
    $statusBadge = ['active' => 'success', 'deactivated' => 'secondary', 'suspended' => 'warning', 'blacklisted' => 'dark'];
@endphp

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th>Status</th><th style="min-width:280px">Actions</th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->role->display_name }}</td>
                    <td class="small text-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                    <td><span class="badge bg-{{ $statusBadge[$u->status] ?? 'secondary' }}">{{ ucfirst($u->status) }}</span>
                        @if ($u->must_change_password)<br><span class="badge bg-light text-dark border mt-1" title="Has not yet replaced the password issued to them">Must change password</span>@endif</td>
                    <td>
                        @if ($u->id === auth()->id())
                            <span class="text-muted small">This is you — use Change password in the sidebar.</span>
                        @elseif (! $me->canManageUser($u))
                            <span class="text-muted small">Protected account — you cannot change it.</span>
                        @else
                            <div class="d-flex flex-wrap gap-1">
                                @if ($canStatus)
                                <form method="POST" action="{{ route('users.status', $u) }}" class="d-flex gap-1">
                                    @csrf
                                    <select name="status" class="form-select form-select-sm" style="width:auto">
                                        @foreach (\App\Models\User::STATUSES as $s)
                                            <option value="{{ $s }}" @selected($u->status === $s)>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Set</button>
                                </form>
                                @endif
                                @if ($canReset)
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#pw{{ $u->id }}">Reset password</button>
                                @endif
                            </div>
                            @if ($canReset)
                            <div class="collapse mt-1" id="pw{{ $u->id }}">
                                <form method="POST" action="{{ route('users.password.reset', $u) }}" class="d-flex gap-1">
                                    @csrf
                                    <input name="password" type="password" class="form-control form-control-sm" placeholder="New password (10+ chars)" required>
                                    <input name="password_confirmation" type="password" class="form-control form-control-sm" placeholder="Confirm" required>
                                    <button class="btn btn-sm btn-warning text-nowrap">Set new password</button>
                                </form>
                            </div>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection

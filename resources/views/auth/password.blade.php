@extends('layouts.app')
@section('title', 'Change password')
@section('content')
<h1 class="fs-4 mb-3">Change your password</h1>
@if (auth()->user()->must_change_password)
    <div class="alert alert-warning" style="max-width:480px">
        <i class="bi bi-shield-lock me-1"></i>You are using a password issued by an administrator.
        Set your own password to continue using the system.
    </div>
@endif
<div class="card" style="max-width:480px"><div class="card-body">
    <form method="POST" action="{{ route('profile.password.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Current password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="password" class="form-control" required>
            <div class="form-text">At least 10 characters with upper &amp; lower case letters and a number.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm new password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
        <button class="btn btn-primary">Change password</button>
    </form>
</div></div>
@endsection

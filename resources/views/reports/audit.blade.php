@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<h1 class="fs-4 mb-3">Audit log</h1>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0 small">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->auditable_type }}</td>
                    <td>{{ $log->details }}</td>
                    <td>{{ $log->ip_address }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection

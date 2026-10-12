@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fs-4 mb-0">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Mark all read</button></form>
</div>
<div class="card">
    <ul class="list-group list-group-flush">
        @forelse ($notifications as $n)
            <li class="list-group-item {{ $n->read_at ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between">
                    <strong>{{ $n->title }}</strong>
                    <span class="text-muted small">{{ $n->created_at->diffForHumans() }}</span>
                </div>
                <div class="small text-muted">{{ $n->body }}</div>
                @if ($n->project)<a class="small" href="{{ route('projects.show', $n->project) }}">Open project</a>@endif
            </li>
        @empty
            <li class="list-group-item text-muted">You're all caught up.</li>
        @endforelse
    </ul>
</div>
@if (method_exists($notifications, 'links'))<div class="mt-3">{{ $notifications->links() }}</div>@endif
@endsection

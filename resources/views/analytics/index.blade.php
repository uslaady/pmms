@extends('layouts.app')
@section('title', 'Analytics')
@section('content')
<h1 class="fs-4 mb-3">Portfolio analytics</h1>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Schedule performance index</div>
        <div class="fs-3 fw-bold {{ ($spi ?? 1) < 0.9 ? 'text-danger' : 'text-success' }}">{{ $spi ?? '—' }}</div>
        <div class="small text-muted">Actual ÷ planned progress. Below 1.0 means the portfolio is behind schedule.</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="text-muted small">Cost performance index</div>
        <div class="fs-3 fw-bold {{ ($cpi ?? 1) < 0.9 ? 'text-danger' : 'text-success' }}">{{ $cpi ?? '—' }}</div>
        <div class="small text-muted">Earned value ÷ amount paid. Below 1.0 means spend outpaces progress.</div>
    </div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header">Contractor rankings</div>
    <table class="table table-sm mb-0">
        <thead><tr><th>Contractor</th><th class="text-end">Projects</th><th class="text-end">Completed</th><th class="text-end">Delayed</th><th class="text-end">Completion rate</th></tr></thead>
        <tbody>
        @foreach ($contractorRankings as $c)
            <tr>
                <td>{{ $c->name }}</td>
                <td class="text-end">{{ $c->projects_count }}</td>
                <td class="text-end">{{ $c->completed_count }}</td>
                <td class="text-end">{{ $c->delayed_count }}</td>
                <td class="text-end">{{ $c->on_time_rate }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header">Average progress deficit by LGA (active projects)</div>
    <table class="table table-sm mb-0">
        <thead><tr><th>LGA</th><th class="text-end">Active projects</th><th class="text-end">Avg deficit (planned − actual)</th></tr></thead>
        <tbody>
        @foreach ($delayByLga as $row)
            <tr>
                <td>{{ $row->lga?->name ?? 'Unassigned' }}</td>
                <td class="text-end">{{ $row->total }}</td>
                <td class="text-end {{ $row->avg_deficit > 15 ? 'text-danger fw-bold' : '' }}">{{ round($row->avg_deficit, 1) }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

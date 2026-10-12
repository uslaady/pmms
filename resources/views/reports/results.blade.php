@extends('layouts.app')
@section('title', $title)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="fs-4 mb-0">{{ $title }}</h1>
    <div class="d-flex gap-2">
        @if (auth()->user()->hasPermission('reports.export'))
            @php $qs = request()->query(); @endphp
            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.build', array_merge($qs, ['format' => 'csv'])) }}">CSV</a>
            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.build', array_merge($qs, ['format' => 'xls'])) }}">Excel</a>
            <a class="btn btn-sm btn-outline-danger" href="{{ route('reports.build', array_merge($qs, ['format' => 'pdf'])) }}">PDF</a>
        @endif
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.index') }}">New report / Reset</a>
    </div>
</div>

<div class="card mb-3"><div class="card-body py-2">
    @foreach ($criteria as $label => $value)
        <span class="badge bg-light text-dark border me-1 mb-1">{{ $label }}: {{ $value }}</span>
    @endforeach
    <span class="text-muted small ms-2">Generated {{ now()->format('d M Y H:i') }}</span>
</div></div>

<div class="row g-3 mb-3">
    @if ($report === 'financial')
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-muted small">Payments</div><div class="fs-4 fw-bold">{{ $totals['count'] }}</div>
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-muted small">Total amount</div><div class="fs-4 fw-bold">₦{{ number_format($totals['amount'], 2) }}</div>
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body">
            <div class="text-muted small">Retention held</div><div class="fs-4 fw-bold">₦{{ number_format($totals['retention'], 2) }}</div>
        </div></div></div>
    @else
        <div class="col-md-3"><div class="card"><div class="card-body">
            <div class="text-muted small">Projects</div><div class="fs-4 fw-bold">{{ $totals['count'] }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <div class="text-muted small">Contract value</div><div class="fs-4 fw-bold">₦{{ number_format($totals['contract_value'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <div class="text-muted small">Total paid</div><div class="fs-4 fw-bold">₦{{ number_format($totals['paid'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body">
            <div class="text-muted small">Outstanding</div><div class="fs-4 fw-bold">₦{{ number_format($totals['outstanding'], 2) }}</div>
        </div></div></div>
    @endif
</div>

<div class="card">
    <div class="table-responsive">
        @include('reports._table')
    </div>
</div>
@endsection

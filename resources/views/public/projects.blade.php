<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><title>Public projects — Ministry of Works &amp; Housing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-3"><span class="navbar-brand">Ministry of Works &amp; Housing — Projects near you</span></nav>
<div class="container py-4">
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-4">
            <select name="lga_id" class="form-select">
                <option value="">All local government areas</option>
                @foreach ($lgas as $l)<option value="{{ $l->id }}" @selected(request('lga_id') == $l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <div class="row g-3">
        @foreach ($projects as $p)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="fs-6">{{ $p->name }}</h2>
                        <div class="small text-muted mb-2">{{ $p->lga?->name }} · {{ $p->contractor?->name }}</div>
                        <div class="progress mb-2" style="height:16px">
                            <div class="progress-bar" style="width: {{ $p->actual_progress_percent }}%; background: {{ $p->status_color }}">{{ $p->actual_progress_percent }}%</div>
                        </div>
                        <span class="badge" style="background: {{ $p->status_color }}">{{ $p->status_label }}</span>
                        <a class="small ms-2" href="{{ route('public.projects.show', $p) }}">View details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $projects->links() }}</div>
</div>
@include('partials.footer')
</body>
</html>

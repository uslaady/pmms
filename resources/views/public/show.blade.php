<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><title>{{ $project->name }} — Ministry of Works &amp; Housing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="{{ route('public.projects') }}">← All projects</a>
</nav>
<div class="container py-4">
    <h1 class="fs-4">{{ $project->name }}</h1>
    <p class="text-muted">{{ $project->lga?->name }} · Contractor: {{ $project->contractor?->name }} · Status:
        <span class="badge" style="background: {{ $project->status_color }}">{{ str_replace('_',' ',$project->status) }}</span></p>
    <div class="progress mb-3" style="height:20px">
        <div class="progress-bar" style="width: {{ $project->actual_progress_percent }}%; background: {{ $project->status_color }}">{{ $project->actual_progress_percent }}% complete</div>
    </div>
    @if ($project->latitude)
        <div id="map" style="height:300px" class="rounded mb-3"></div>
    @endif
    <div class="row g-2">
        @foreach ($project->images as $img)
            <div class="col-6 col-md-3"><img src="{{ Storage::url($img->path) }}" class="img-fluid rounded" alt="{{ $img->caption }}"></div>
        @endforeach
    </div>
</div>
@if ($project->latitude)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const m = L.map('map').setView([{{ $project->latitude }}, {{ $project->longitude }}], 13);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
L.circleMarker([{{ $project->latitude }}, {{ $project->longitude }}], { color: '{{ $project->status_color }}', fillOpacity: .8, radius: 10 }).addTo(m);
</script>
@endif
@include('partials.footer')
</body>
</html>

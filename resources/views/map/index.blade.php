@extends('layouts.app')
@section('title', 'GIS map')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
<h1 class="fs-4 mb-3">Statewide project map</h1>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <select id="fStatus" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach (\App\Models\Project::STATUS_LABELS as $s => $label)
                <option value="{{ $s }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="fLga" class="form-select form-select-sm">
            <option value="">All LGAs</option>
            @foreach ($lgas as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select id="fContractor" class="form-select form-select-sm">
            <option value="">All contractors</option>
            @foreach ($contractors as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button id="fApply" class="btn btn-sm btn-primary w-100">Apply filters</button></div>
    <div class="col-md-1"><button id="fClear" class="btn btn-sm btn-outline-danger w-100">Clear</button></div>
</div>

<div class="card mb-2"><div id="bigMap" style="height:70vh;border-radius:inherit"></div></div>

<div class="small text-muted">
    <span class="badge" style="background:#16A34A">Completed</span>
    <span class="badge" style="background:#2563EB">In progress</span>
    <span class="badge" style="background:#EAB308">Suspended</span>
    <span class="badge" style="background:#DC2626">Delayed</span>
    <span class="badge" style="background:#6B7280">Cancelled</span>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Base layers: standard map + satellite imagery, switchable from the control
const baseMap = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
});
const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19, attribution: 'Imagery &copy; Esri, Maxar, Earthstar Geographics',
});
const map = L.map('bigMap', { layers: [baseMap] }).setView([11.85, 13.15], 8); // Borno State
L.control.layers({ 'Map': baseMap, 'Satellite': satellite }, null, { position: 'topright' }).addTo(map);
let layer = null;

async function loadProjects() {
    const params = new URLSearchParams();
    const status = document.getElementById('fStatus').value;
    const lga = document.getElementById('fLga').value;
    const contractor = document.getElementById('fContractor').value;
    if (status) params.set('status', status);
    if (lga) params.set('lga_id', lga);
    if (contractor) params.set('contractor_id', contractor);

    const res = await fetch('{{ route('map.geojson') }}?' + params.toString());
    const geojson = await res.json();

    if (layer) map.removeLayer(layer);
    layer = L.geoJSON(geojson, {
        pointToLayer: (f, latlng) => L.circleMarker(latlng, {
            radius: 8, color: f.properties.color, fillColor: f.properties.color, fillOpacity: .8, weight: 2,
        }),
        onEachFeature: (f, l) => {
            // Hover: quick details; Click: full popup with link
            l.bindTooltip(
                `<strong>${f.properties.name}</strong><br>${f.properties.code}<br>` +
                `${f.properties.lga ?? ''} · ${f.properties.status.replace('_', ' ')} · ${Math.round(f.properties.progress)}%`,
                { direction: 'top', offset: [0, -8] }
            );
            l.bindPopup(
                `<strong>${f.properties.name}</strong><br>${f.properties.code}<br>` +
                `${f.properties.contractor ?? ''} · ${f.properties.lga ?? ''}<br>` +
                `Progress: ${Math.round(f.properties.progress)}%<br>` +
                `<a href="${f.properties.url}">Open project</a>`
            );
        },
    }).addTo(map);

    if (geojson.features.length) map.fitBounds(layer.getBounds().pad(0.2));
}
document.getElementById('fApply').addEventListener('click', loadProjects);
document.getElementById('fClear').addEventListener('click', () => {
    document.getElementById('fStatus').value = '';
    document.getElementById('fLga').value = '';
    document.getElementById('fContractor').value = '';
    loadProjects();
});
loadProjects();
</script>
@endpush

@extends('layouts.app')
@section('title', 'Executive dashboard')
@section('content')

{{-- Sticky Reset Filter button: fixed top-right, survives scrolling --}}
<a href="{{ route('dashboard') }}"
   class="btn {{ $filtersActive ? 'btn-danger' : 'btn-outline-secondary disabled' }} position-fixed shadow"
   style="top: 12px; right: 16px; z-index: 1050;">
    <i class="bi bi-x-circle me-1"></i>Reset filter
</a>

<h1 class="fs-4 mb-1">Executive dashboard</h1>
@if ($filtersActive)
    <p class="text-muted small mb-3">Filtered view — click the same chart segment again or use Reset filter to return to default.</p>
@else
    <p class="text-muted small mb-3">Click any chart segment or bar to filter the whole dashboard.</p>
@endif

<div class="row g-3 mb-3">
    @foreach ([
        ['Total projects', $cards['total_projects'], 'linear-gradient(135deg,#1E3A8A,#2563EB)'],
        ['Pending approval', $cards['pending'], 'linear-gradient(135deg,#5B21B6,#7C3AED)'],
        ['Approved', $cards['planned'], 'linear-gradient(135deg,#64748B,#94A3B8)'],
        ['Ongoing', $cards['ongoing'], 'linear-gradient(135deg,#0E7490,#06B6D4)'],
        ['Completed', $cards['completed'], 'linear-gradient(135deg,#14532D,#16A34A)'],
        ['Suspended', $cards['suspended'], 'linear-gradient(135deg,#A16207,#EAB308)'],
        ['Delayed', $cards['delayed'], 'linear-gradient(135deg,#7F1D1D,#DC2626)'],
        ['Cancelled / deactivated', $cards['cancelled'], 'linear-gradient(135deg,#374151,#6B7280)'],
    ] as [$label, $value, $bg])
        <div class="col-6 col-md-4 col-lg">
            <div class="card stat-card h-100" style="background: {{ $bg }}"><div class="card-body">
                <div class="small">{{ $label }}</div>
                <div class="fs-3 fw-bold" data-count="{{ $value }}">{{ $value }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card" style="background: linear-gradient(135deg,#312E81,#4F46E5)"><div class="card-body">
        <div class="small">Total contract value</div>
        <div class="fs-5 fw-bold">₦{{ number_format($cards['total_contract_value'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card" style="background: linear-gradient(135deg,#134E4A,#0D9488)"><div class="card-body">
        <div class="small">Total paid</div>
        <div class="fs-5 fw-bold">₦{{ number_format($cards['total_paid'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card" style="background: linear-gradient(135deg,#9A3412,#F97316)"><div class="card-body">
        <div class="small">Outstanding</div>
        <div class="fs-5 fw-bold">₦{{ number_format($cards['outstanding'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card" style="background: linear-gradient(135deg,#581C87,#9333EA)"><div class="card-body">
        <div class="small">Average completion</div>
        <div class="fs-5 fw-bold">{{ $cards['average_completion'] }}%</div>
    </div></div></div>
</div>

{{-- Row: status doughnut + district vertical bars, side by side --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card chart-card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Projects by status <span class="small">(click a segment to filter)</span></h2>
            <div style="position:relative;height:200px"><canvas id="chartStatus"></canvas></div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card chart-card h-100"><div class="card-body">
            <h2 class="fs-6 text-muted">Projects by District <span class="small">(click a bar to filter)</span></h2>
            <div style="position:relative;height:200px"><canvas id="chartDistrict"></canvas></div>
        </div></div>
    </div>
</div>

{{-- Full-width horizontal charts --}}
@foreach ([
    ['chartLga', 'Projects by LGA', $byLga],
    ['chartWard', 'Projects by Ward', $byWard],
    ['chartContractor', 'Projects by Contractor', $byContractor],
] as [$id, $title, $rows])
    <div class="card chart-card mb-3"><div class="card-body">
        <h2 class="fs-6 text-muted">{{ $title }} <span class="small">(click a bar to filter)</span></h2>
        <div style="position:relative; height: {{ max(120, count($rows) * 34) }}px">
            <canvas id="{{ $id }}"></canvas>
        </div>
    </div></div>
@endforeach

{{-- Bottom table with view toggle: Top delayed projects <-> Projects --}}
@php $toggleBase = request()->except('table', 'page'); @endphp
<div class="card shadow-sm" id="bottom-table">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>{{ $tableView === 'projects' ? 'Projects'.($filtersActive ? ' (filtered)' : '') : 'Top delayed projects' }}</span>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('dashboard', array_merge($toggleBase, ['table' => 'delayed'])) }}#bottom-table"
               class="btn {{ $tableView === 'delayed' ? 'btn-dark' : 'btn-outline-dark' }}">Top delayed projects</a>
            <a href="{{ route('dashboard', array_merge($toggleBase, ['table' => 'projects'])) }}#bottom-table"
               class="btn {{ $tableView === 'projects' ? 'btn-dark' : 'btn-outline-dark' }}">Projects</a>
        </div>
    </div>
    <div class="table-responsive">
        @if ($tableView === 'projects')
            <table class="table mb-0 align-middle">
                <thead><tr><th>Code</th><th>Name</th><th>District</th><th>LGA</th><th>Contractor</th><th style="min-width:130px">Progress</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($tableProjects as $p)
                    <tr>
                        <td><a href="{{ route('projects.show', $p) }}">{{ $p->project_code }}</a></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->district?->name }}</td>
                        <td>{{ $p->lga?->name }}</td>
                        <td>{{ $p->contractor?->name }}</td>
                        <td>
                            <div class="progress" style="height:16px">
                                <div class="progress-bar" style="width: {{ $p->actual_progress_percent }}%; background: {{ $p->status_color }}">{{ $p->actual_progress_percent }}%</div>
                            </div>
                        </td>
                        <td><span class="badge" style="background:{{ $p->status_color }}">{{ $p->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No projects match the current filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        @else
            <table class="table mb-0 align-middle">
                <thead><tr><th>Code</th><th>Name</th><th class="text-end">Planned</th><th class="text-end">Actual</th><th class="text-end">Deficit</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($topDelayed as $p)
                    <tr>
                        <td><a href="{{ route('projects.show', $p) }}">{{ $p->project_code }}</a></td>
                        <td>{{ $p->name }}</td>
                        <td class="text-end">{{ $p->planned_progress_percent }}%</td>
                        <td class="text-end">{{ $p->actual_progress_percent }}%</td>
                        <td class="text-end text-danger fw-bold">{{ $p->delay_days }}%</td>
                        <td><span class="badge" style="background:{{ $p->status_color }}">{{ $p->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No delayed or suspended projects in this view.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </div>
</div>
@if ($tableView === 'projects' && $tableProjects)
    <div class="mt-3">{{ $tableProjects->links() }}</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);
Chart.defaults.plugins.datalabels.display = false; // opt-in per chart

const activeFilters = @json($filters);

// "7 (20%)" labels used on all district/LGA/ward/contractor bars
const barLabels = {
    display: true,
    anchor: 'end',
    align: 'end',
    clamp: true,
    font: { size: 10, weight: 'bold' },
    color: getComputedStyle(document.body).getPropertyValue('color') || '#333',
    formatter: (value, ctx) => {
        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
        const pct = total ? Math.round(value / total * 100) : 0;
        return value + ' (' + pct + '%)';
    },
};

// ---- Premium fills: keep every chart's existing colours, add depth ----
function shade(hex, f) {
    const n = parseInt(hex.slice(1), 16);
    const r = Math.min(255, Math.max(0, (n >> 16) + Math.round(255 * f)));
    const g = Math.min(255, Math.max(0, ((n >> 8) & 255) + Math.round(255 * f)));
    const b = Math.min(255, Math.max(0, (n & 255) + Math.round(255 * f)));
    return 'rgb(' + r + ',' + g + ',' + b + ')';
}
// Gradient from a darker to a lighter tone of the SAME colour — the sheen
// used on the stat cards, applied to chart segments and bars.
function glossy(ctx, hex, vertical = true) {
    const area = ctx.chart.chartArea;
    if (!area) return hex;
    const gr = vertical
        ? ctx.chart.ctx.createLinearGradient(0, area.bottom, 0, area.top)
        : ctx.chart.ctx.createLinearGradient(area.left, 0, area.right, 0);
    gr.addColorStop(0, shade(hex, -0.14));
    gr.addColorStop(1, shade(hex, 0.12));
    return gr;
}

// Click a segment/bar to filter; click the same one again to clear it.
// The `table` and other params are preserved because we mutate the current URL.
function toggleFilter(key, value) {
    const url = new URL(window.location);
    url.searchParams.delete('page'); // filters change the result set — restart pagination
    if (value === null || String(url.searchParams.get(key)) === String(value)) {
        url.searchParams.delete(key);
    } else {
        url.searchParams.set(key, value);
    }
    window.location = url;
}

/* ---- Projects by status: doughnut with count + % inside segments ---- */
const statusColors = @json(\App\Models\Project::STATUS_COLORS);
// Match the "Ongoing" card's cyan on the pie's Ongoing (in progress) segment
statusColors.in_progress = '#0891B2';
const byStatus = @json($byStatus);
const STATUS_LABELS = @json(\App\Models\Project::STATUS_LABELS);
const statusKeys = Object.keys(byStatus);
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusKeys.map(s => STATUS_LABELS[s] ?? s.replace('_', ' ')),
        datasets: [{
            data: Object.values(byStatus),
            backgroundColor: ctx => glossy(ctx, statusColors[statusKeys[ctx.dataIndex]]),
            hoverBackgroundColor: ctx => glossy(ctx, statusColors[statusKeys[ctx.dataIndex]]),
            borderColor: 'rgba(255,255,255,.9)',
            borderWidth: activeFilters.status ? statusKeys.map(s => s === activeFilters.status ? 4 : 2) : 2,
            hoverOffset: 8,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (evt, els) => { if (els.length) toggleFilter('status', statusKeys[els[0].index]); },
        onHover: (evt, els) => { evt.native.target.style.cursor = els.length ? 'pointer' : 'default'; },
        plugins: {
            legend: { position: 'right' },
            datalabels: {
                display: ctx => ctx.dataset.data[ctx.dataIndex] > 0,
                color: '#fff',
                textAlign: 'center',
                font: { size: 11, weight: 'bold' },
                formatter: (value, ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const pct = total ? Math.round(value / total * 100) : 0;
                    return value + '\n' + pct + '%';
                },
            },
        },
    },
});

/* ---- Projects by District: vertical bars (only 3 districts) ---- */
const byDistrict = @json($byDistrict);
new Chart(document.getElementById('chartDistrict'), {
    type: 'bar',
    data: {
        labels: byDistrict.map(r => r.name),
        datasets: [{
            data: byDistrict.map(r => r.total),
            backgroundColor: ctx => glossy(ctx,
                activeFilters.district_id && String(activeFilters.district_id) === String(byDistrict[ctx.dataIndex].id) ? '#DC2626' : '#0F6E56', true),
            hoverBackgroundColor: ctx => glossy(ctx,
                activeFilters.district_id && String(activeFilters.district_id) === String(byDistrict[ctx.dataIndex].id) ? '#DC2626' : '#0F6E56', true),
            borderRadius: 8,
            borderSkipped: false,
            maxBarThickness: 80,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (evt, els) => { if (els.length) toggleFilter('district_id', byDistrict[els[0].index].id); },
        onHover: (evt, els) => { evt.native.target.style.cursor = els.length ? 'pointer' : 'default'; },
        plugins: { legend: { display: false }, datalabels: barLabels },
        scales: { y: { beginAtZero: true, grace: '15%', ticks: { precision: 0 } } },
    },
});

/* ---- Horizontal bar charts: LGA, Ward, Contractor ---- */
function horizontalChart(canvasId, rows, filterKey, color) {
    new Chart(document.getElementById(canvasId), {
        type: 'bar',
        data: {
            labels: rows.map(r => r.name),
            datasets: [{
                data: rows.map(r => r.total),
                backgroundColor: ctx => glossy(ctx,
                    activeFilters[filterKey] && String(activeFilters[filterKey]) === String(rows[ctx.dataIndex].id) ? '#DC2626' : color, false),
                hoverBackgroundColor: ctx => glossy(ctx,
                    activeFilters[filterKey] && String(activeFilters[filterKey]) === String(rows[ctx.dataIndex].id) ? '#DC2626' : color, false),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 22,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            onClick: (evt, els) => { if (els.length) toggleFilter(filterKey, rows[els[0].index].id); },
            onHover: (evt, els) => { evt.native.target.style.cursor = els.length ? 'pointer' : 'default'; },
            plugins: { legend: { display: false }, datalabels: barLabels },
            scales: { x: { beginAtZero: true, grace: '18%', ticks: { precision: 0 } } },
        },
    });
}
horizontalChart('chartLga', @json($byLga), 'lga_id', '#2563EB');
horizontalChart('chartWard', @json($byWard), 'ward_id', '#7C3AED');
horizontalChart('chartContractor', @json($byContractor), 'contractor_id', '#B45309');
</script>
@endpush

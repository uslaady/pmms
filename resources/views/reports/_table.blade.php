@if ($report === 'financial')
    <table class="table table-sm table-striped mb-0 small" border="1">
        <thead><tr>
            <th>Date</th><th>Project</th><th>Code</th><th>District</th><th>LGA</th><th>Ward</th>
            <th>Contract No</th><th>Certificate</th><th style="text-align:right">Amount (₦)</th><th style="text-align:right">Retention (₦)</th><th>Status</th>
        </tr></thead>
        <tbody>
        @forelse ($rows as $p)
            @php $proj = $p->contract->project; @endphp
            <tr>
                <td>{{ $p->payment_date->format('d M Y') }}</td>
                <td>{{ $proj->name ?? '' }}</td>
                <td>{{ $proj->project_code ?? '' }}</td>
                <td>{{ $proj->district?->name }}</td>
                <td>{{ $proj->lga?->name }}</td>
                <td>{{ $proj->ward?->name }}</td>
                <td>{{ $p->contract->contract_number ?? '' }}</td>
                <td>{{ $p->certificate_number }}</td>
                <td style="text-align:right">{{ number_format((float) $p->amount, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $p->retention_held, 2) }}</td>
                <td>{{ $p->status }}</td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center">No payments match the selected criteria.</td></tr>
        @endforelse
        </tbody>
    </table>
@else
    <table class="table table-sm table-striped mb-0 small" border="1">
        <thead><tr>
            <th>Code</th><th>Name</th><th>District</th><th>LGA</th><th>Ward</th><th>Status</th><th>Contractor</th>
            <th>Award</th><th style="text-align:right">Value (₦)</th><th style="text-align:right">Paid (₦)</th><th style="text-align:right">Outstanding (₦)</th>
            <th style="text-align:right">Planned %</th><th style="text-align:right">Actual %</th>
        </tr></thead>
        <tbody>
        @forelse ($rows as $p)
            <tr>
                <td>{{ $p->project_code }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->district?->name }}</td>
                <td>{{ $p->lga?->name }}</td>
                <td>{{ $p->ward?->name }}</td>
                <td>{{ $p->status_label }}</td>
                <td>{{ $p->contractor?->name }}</td>
                <td>{{ $p->award_date?->format('d M Y') }}</td>
                <td style="text-align:right">{{ number_format((float) ($p->contract->contract_value ?? 0), 2) }}</td>
                <td style="text-align:right">{{ number_format((float) ($p->contract?->total_paid ?? 0), 2) }}</td>
                <td style="text-align:right">{{ number_format((float) ($p->contract?->outstanding_balance ?? 0), 2) }}</td>
                <td style="text-align:right">{{ $p->planned_progress_percent }}</td>
                <td style="text-align:right">{{ $p->actual_progress_percent }}</td>
            </tr>
        @empty
            <tr><td colspan="13" style="text-align:center">No projects match the selected criteria.</td></tr>
        @endforelse
        </tbody>
    </table>
@endif

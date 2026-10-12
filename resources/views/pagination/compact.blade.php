@php
    // Query params to preserve across page / per-page changes
    $preserved = request()->except('page', 'per_page');
    $perPage = $paginator->perPage();
@endphp
<nav aria-label="Table navigation" class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="small text-muted">
        Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }}
        of {{ number_format($paginator->total()) }}
    </div>

    <div class="btn-group btn-group-sm" role="group">
        <a class="btn btn-outline-secondary {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
           href="{{ $paginator->url(1) }}" aria-label="First page" @if($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
            <i class="bi bi-chevron-bar-left"></i><span class="d-none d-md-inline ms-1">First</span>
        </a>
        <a class="btn btn-outline-secondary {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
           href="{{ $paginator->previousPageUrl() ?? '#' }}" aria-label="Previous page" @if($paginator->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
            <i class="bi bi-chevron-left"></i><span class="d-none d-md-inline ms-1">Prev</span>
        </a>
        <span class="btn btn-outline-secondary disabled text-body" aria-current="page" style="min-width:110px">
            Page {{ number_format($paginator->currentPage()) }} of {{ number_format(max(1, $paginator->lastPage())) }}
        </span>
        <a class="btn btn-outline-secondary {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
           href="{{ $paginator->nextPageUrl() ?? '#' }}" aria-label="Next page" @unless($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>
            <span class="d-none d-md-inline me-1">Next</span><i class="bi bi-chevron-right"></i>
        </a>
        <a class="btn btn-outline-secondary {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
           href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Last page" @unless($paginator->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless>
            <span class="d-none d-md-inline me-1">Last</span><i class="bi bi-chevron-bar-right"></i>
        </a>
    </div>

    <form method="GET" class="d-flex align-items-center gap-1 small">
        @foreach ($preserved as $key => $value)
            @if (is_array($value))
                @foreach ($value as $v)<input type="hidden" name="{{ $key }}[]" value="{{ $v }}">@endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label class="text-muted me-1" for="perPageSelect">Rows per page</label>
        <select id="perPageSelect" name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100, 250] as $n)
                <option value="{{ $n }}" @selected($perPage == $n)>{{ $n }}</option>
            @endforeach
        </select>
    </form>
</nav>

{{-- Plain-table document served as .xls (opens in Excel) and reused as the PDF body --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; }
        h1 { font-size: 14px; margin-bottom: 2px; }
        .meta { color: #555; margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>Ministry of Works &amp; Housing — {{ $title }}</h1>
    <div class="meta">
        @foreach ($criteria as $label => $value){{ $label }}: {{ $value }} | @endforeach
        Generated {{ now()->format('d M Y H:i') }}
    </div>
    @include('reports._table')
</body>
</html>

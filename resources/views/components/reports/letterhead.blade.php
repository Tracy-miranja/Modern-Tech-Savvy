
<table class="header-table">
    <tr>
        <td width="50%">
            @php
                $logoUrl = $business->getImageUrl();
                $logoBase64 = null;
                try {
                    $filePath = $logoUrl ? public_path(parse_url($logoUrl, PHP_URL_PATH)) : null;
                    if ($filePath && is_file($filePath)) {
                        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                        $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($filePath));
                    }
                } catch (\Exception $e) {}
@endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="{{ $business->company_name ?? 'Company' }} Logo" class="logo">
            @else
                <div class="logo-placeholder">{{ strtoupper(substr($business->company_name ?? 'Company', 0, 1)) }}</div>
            @endif

            <p class="company-name">{{ $business->company_name ?? 'Company' }}</p>
            <p class="company-detail">{{ $business->physical_address ?? '' }}</p>
            <p class="company-detail">Phone: {{ $business->phone ?? 'N/A' }}</p>
            <p class="company-detail">Email: {{ $business->email ?? optional($business->user)->email ?? '' }}</p>
        </td>
        <td width="50%" class="right">
            <p class="report-title">{{ $reportTitle }}</p>
            @if(!empty($periodLabel))
                <p class="report-meta">Period: {{ $periodLabel }}</p>
            @endif
            <p class="report-meta">Generated: {{ now()->format('F d, Y H:i') }}</p>
            @foreach(($meta ?? []) as $label => $value)
                <p class="report-meta">{{ $label }}: {{ $value }}</p>
            @endforeach
        </td>
    </tr>
</table>
<hr class="divider">

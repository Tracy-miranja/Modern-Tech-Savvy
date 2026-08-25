<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Health</h5>
                    <small class="text-muted">Live diagnostics - platform-governance only.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        @foreach ($checks as $name => $check)
                            @php
                                $badgeClass = match ($check['status']) {
                                    'ok' => 'bg-success',
                                    'warning' => 'bg-warning text-dark',
                                    'error' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
@endphp
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong>{{ $name }}</strong>
                                        <span class="badge {{ $badgeClass }} text-uppercase">{{ $check['status'] }}</span>
                                    </div>
                                    <div class="small">{{ $check['message'] }}</div>
                                    <div class="small text-muted mt-1">{{ $check['detail'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3">Environment</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            @foreach ($environment as $label => $value)
                                                <tr>
                                                    <td class="text-muted">{{ $label }}</td>
                                                    <td class="text-end">{{ $value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3">Platform Stats</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            @foreach ($businessStats as $label => $value)
                                                <tr>
                                                    <td class="text-muted">{{ $label }}</td>
                                                    <td class="text-end">{{ $value }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0">
                        <div class="card-body">
                            <h6 class="fw-bold text-primary mb-3">Recent Errors (log tail)</h6>
                            @if (empty($recentErrors))
                                <p class="text-muted small mb-0">No recent ERROR/CRITICAL log entries found.</p>
                            @else
                                <div class="bg-dark text-light p-3 rounded small" style="max-height:320px; overflow-y:auto; font-family:monospace; white-space:pre-wrap;">{{ implode("\n", $recentErrors) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

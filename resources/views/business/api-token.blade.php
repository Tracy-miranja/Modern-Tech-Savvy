<x-app-layout>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="fw-bold mb-4">
                            Manage API Token for {{ $business->company_name }}
                        </h2>

                        @if (session('message'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if (session('api_token'))
                        <div class="card border-info-subtle mb-4">
                            <div class="card-body">
                                <h5 class="fw-semibold mb-3">Your New API Token</h5>
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                                    <code id="api-token"
                                        class="flex-grow-1 p-2 rounded bg-white border text-monospace text-break">
                                        {{ session('api_token') }}
                                    </code>
                                    <button onclick="copyToClipboard()" class="btn btn-outline-primary">
                                        <i class="bi bi-clipboard me-2"></i>Copy
                                    </button>
                                </div>
                                <p class="mt-3 mb-0 text-muted small">
                                    <i class="bi bi-lock-fill me-1"></i>
                                    Store this token securely. It will not be shown again.
                                </p>
                                @if (session('api_token_warning'))
                                <p class="mt-2 mb-0 text-warning small">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    {{ session('api_token_warning') }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('api.business.generate-token', $business->slug) }}">
                            @csrf
                            <p class="text-muted mb-4">
                                Generate a new API token to allow external systems, such as job boards, to submit
                                applications securely.
                            </p>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key-fill me-2"></i>
                                {{ $business->api_token ? 'Regenerate API Token' : 'Generate API Token' }}
                            </button>
                        </form>

                        @if ($business->api_token)
                        <p class="text-muted small mt-4 mb-0">
                            <i class="bi bi-clock me-1"></i>
                            Last generated: {{ $business->updated_at->diffForHumans() }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const token = document.getElementById('api-token').textContent;
            navigator.clipboard.writeText(token).then(() => {
                alert('Token copied to clipboard!');
            });
        }
    </script>
</x-app-layout>
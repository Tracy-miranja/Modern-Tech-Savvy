<x-app-layout title="Clients Management">
    <meta name="business-slug" content="{{ session('active_business_slug') }}">

    <div class="container py-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                <h2 class="mb-0 fw-bold text-dark">Clients Management</h2>
            </div>
            <div class="card-body p-4">

                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div id="clientsContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading clients...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    // Define currentBusinessSlug globally
    window.currentBusinessSlug = "{{ $business->slug }}";
    </script>
    <script src="{{ asset('js/main/clients.js') }}" type="module"></script>
    <script>
    $(document).ready(function() {
        if (typeof window.getClients === 'function') {
            window.getClients();
        } else {
            console.error('getClients is not defined');
        }
    });
    </script>
    @endpush
</x-app-layout>
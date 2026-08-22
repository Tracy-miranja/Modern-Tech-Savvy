<x-app-layout>
    <div class="container">
        @include('clients._access_nav')
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Request Access</h3>
                        <p>Invite a user to access your business account.</p>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        <form id="requestAccessForm">
                            @csrf
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <button type="button" class="btn btn-primary mt-3" onclick="requestAccess(this)">Send
                                Request</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/clients.js') }}"></script>
    @endpush
</x-app-layout>
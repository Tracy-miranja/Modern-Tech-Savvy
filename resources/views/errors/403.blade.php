<x-auth-layout>
    <div class="authentication-wrapper basic-authentication">
        <div class="authentication-inner">
            <div class="card__wrapper text-center">
                <div class="authentication-top mb-20">
                    <a href="javascript:;" class="authentication-logo logo-black">
                        <img src="{{ asset('media/krest-logo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <a href="javascript:;" class="authentication-logo logo-white">
                        <img src="{{ asset('media/krest-logo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <h4 class="mb-15">403 &mdash; Access Denied</h4>
                    <p class="mb-15">{{ $message ?? "You don't have permission to view that page." }}</p>
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary w-100 mb-10">
                        <i class="bi bi-house me-1"></i> Go to my dashboard
                    </a>
                    <a href="" onclick="event.preventDefault(); logout(this)" class="btn btn-outline-secondary w-100">
                        <i class="fa-solid fa-sign-out-alt me-1"></i> Log out
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Go to login
                    </a>
                @endauth
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/main/logout.js') }}" type="module"></script>
    @endpush
</x-auth-layout>

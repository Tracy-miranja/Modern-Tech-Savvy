<x-auth-layout>
    <div class="authentication-wrapper basic-authentication">
        <div class="authentication-inner">
            <div class="card__wrapper">
                <div class="authentication-top text-center mb-20">
                    <a href="javascript:;" class="authentication-logo logo-black">
                        <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <h4 class="mb-15">{{ config('app.name') }}</h4>
                    <p class="mb-15">{{ $message }}</p>
                </div>
                <p class="text-center">
                    <a href="{{ route('login') }}">Return to Login</a>
                </p>
            </div>
        </div>
    </div>
</x-auth-layout>

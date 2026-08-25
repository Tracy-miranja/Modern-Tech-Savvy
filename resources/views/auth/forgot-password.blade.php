<x-auth-layout>
    <div class="authentication-wrapper basic-authentication">
        <div class="authentication-inner">
            <div class="card__wrapper">
                <div class="mb-4 text-sm text-gray-600">
                    {{ __('Forgot your password? No problem.') }}
                </div>

                @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                    {{ session('status') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" id="forgot-password-form">
                    @csrf

                    <div class="from__input-box mb-4">
                        <div class="form__input-title">
                            <label for="email">{{ __('Email') }}</label>
                        </div>
                        <div class="form__input">
                            <input
                                class="form-control block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                placeholder="Email" name="email" id="email" type="email" value="{{ old('email') }}"
                                required autofocus autocomplete="username">
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-700" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button
                            class="btn btn-primary w-100 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded"
                            type="submit" id="forgot-password-button">
                            <i class="bi bi-check-circle me-1"></i> {{ __('Email Password Reset Link') }}
                        </button>
                    </div>
                </form>

                <p class="text-center mt-4">
                    <a href="{{ route('login') }}" class="fw-bold text-decoration-underline">
                        {{ __('Back to Login') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-auth-layout>
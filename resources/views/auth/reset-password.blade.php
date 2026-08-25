<x-auth-layout>
    <div class="authentication-wrapper basic-authentication">
        <div class="authentication-inner">
            <div class="card__wrapper">
                <div class="authentication-top text-center mb-20">
                    <h5 class="mb-15">Reset your password.</h5>
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

                <form method="POST" action="{{ route('password.store') }}" id="reset-password-form">
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label for="email">{{ __('Email') }}</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control" placeholder="Email" name="email" id="email" type="email"
                                value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-700" />
                        </div>
                    </div>

                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label for="password">{{ __('Password') }}</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control password" placeholder="Password" type="password" name="password"
                                required id="password" autocomplete="new-password">
                            <div class="pass-icon" id="password_toggle">
                                <i class="fa-sharp fa-light fa-eye-slash"></i>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-700" />
                        </div>
                    </div>

                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control password" placeholder="Confirm Password" type="password"
                                name="password_confirmation" required id="password_confirmation"
                                autocomplete="new-password">
                            <x-input-error :messages="$errors->get('password_confirmation')"
                                class="mt-2 text-red-700" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary w-100" type="submit" id="reset-password-button">
                            <i class="bi bi-check-circle me-1"></i> {{ __('Reset Password') }}
                        </button>
                    </div>
                </form>

                <p class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="fw-bold text-decoration-underline">
                        {{ __('Request a new reset link') }}
                    </a>
                    <span class="mx-2">|</span>
                    <a href="{{ route('login') }}" class="fw-bold text-decoration-underline">
                        {{ __('Back to Login') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-auth-layout>
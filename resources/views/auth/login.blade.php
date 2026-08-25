<x-auth-layout>
    <div class="authentication-wrapper basic-authentication">
        <div class="authentication-inner">
            <div class="card__wrapper">
                <div class="authentication-top text-center mb-20">
                    <a href="{{ route('welcome') }}" class="authentication-logo logo-black">
                        <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <a href="{{ route('welcome') }}" class="authentication-logo logo-white">
                        <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <h4 class="mb-15">{{ config('app.name') }}</h4>
                    <p class="mb-15">Log in to continue.</p>
                </div>
                <form class="" id="loginForm">
                    @csrf
                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label for="email">Email</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control" placeholder="Email" name="email" id="email" type="email"
                                required autocomplete="email">
                        </div>
                    </div>
                    <div class="from__input-box">
                        <div class="form__input-title d-flex justify-content-between">
                            <label for="password">Password</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control password" placeholder="Password" type="password" name="password"
                                required id="password">
                            <div class="pass-icon" id="password_toggle">
                                <i class="fa-sharp fa-light fa-eye-slash"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-primary w-100" type="button" onclick="login(this)">
                            <i class="bi bi-check-circle me-1 bg-green-500"></i> Login
                        </button>
                    </div>
                </form>
                <p class="text-center mt-3">
                    <span>Don't have an account?</span>
                    <a href="{{ route('register') }}" class="fw-bold text-decoration-underline">
                        <span>Get started</span>
                    </a>
                </p>
                <p class="text-center mt-2">
                    <a href="{{ route('password.request') }}" class="fw-bold text-decoration-underline">
                        Forgot your password?
                    </a>
                </p>
                <p class="text-center mt-2">
                    <a href="{{ route('welcome') }}" class="d-inline-flex align-items-center justify-content-center gap-1 text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to homepage
                    </a>
                </p>
            </div>
        </div>
    </div>
    <style>
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 6px;
        }

        .btn-primary:hover {
            background-color: #083e84;
            border-color: #083e84;
        }
    </style>
</x-auth-layout>

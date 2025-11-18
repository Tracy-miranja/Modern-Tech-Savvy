<x-auth-layout>
    <div class="authentication-wrapper">
        <div class="authentication-inner row g-0">
            <div class="col-md-6 col-sm-12 marketing-section">
                <div class="marketing-content p-4 p-lg-5">
                    <div class="feature-list">
                        <div class="feature-item mb-4">
                            <div class="feature-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="feature-text">
                                <h5>Complete Control</h5>
                                <p>Manage your entire HR & payroll operations from a single platform with
                                    Africa-specific compliance features.</p>
                            </div>
                        </div>

                        <div class="feature-item mb-4">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="feature-text">
                                <h5>Secure & Reliable</h5>
                                <p>Enterprise-grade security on AWS cloud infrastructure with 99.9% uptime
                                    guarantee.
                                </p>
                            </div>
                        </div>

                        <div class="feature-item mb-4">
                            <div class="feature-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div class="feature-text">
                                <h5>Organizational Insights</h5>
                                <p>Access real-time analytics and reports to make informed workforce decisions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Form (Right Side) -->
            <div class="col-md-6 col-sm-12 form-side">
                <div class="form-container">
                    <div class="authentication-top text-center mb-4">
                        <a href="javascript:;" class="authentication-logo">
                            <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}"
                                class="img-fluid" style="max-height: 60px;">
                        </a>
                        <h4 class="mt-4 mb-3">Sign up as <span class="amsol-text">Krest PayHR</span> Client</h4>
                        <p class="text-muted">Complete the form below to create your account</p>
                    </div>

                    <form class="px-3" id="registerForm">
                        <script>
                            document.getElementById("registerForm").addEventListener("submit", function(e) {
                                const tokenField = document.querySelector('input[name="cf-turnstile-response"]');
                                if (!tokenField) {
                                    alert("Turnstile response is missing!");
                                } else {
                                    console.log("Turnstile token:", tokenField.value);
                                }
                            });
                        </script>
                        @csrf

                        @if (isset($registration_token) && !empty($registration_token))
                        <input type="text" hidden name="registration_token" value="{{ $registration_token }}">
                        @endif

                        <div class="from__input-box mb-3">
                            <div class="form__input-title">
                                <label for="name" class="form-label">Full Name</label>
                            </div>
                            <div class="form__input">
                                <input class="form-control" placeholder="Full Name" name="name" id="name"
                                    :value="old('name')" type="text" required autocomplete="name">
                            </div>
                        </div>
                        <div class="from__input-box mb-3">
                            <div class="form__input-title">
                                <label for="email" class="form-label">Email</label>
                            </div>
                            <div class="form__input">
                                <input class="form-control" placeholder="Email" name="email" id="email" type="email"
                                    required autocomplete="email">
                            </div>
                        </div>
                        <div class="from__input-box mb-3">
                            <div class="form__input-title">
                                <label for="phone" class="form-label">Phone</label>
                            </div>
                            <div class="form__input">
                                <input class="phone-input-control" name="phone" id="phone" type="text" required
                                    autocomplete="phone">
                                <input name="code" id="code" type="text" hidden required autocomplete="code">
                                <input name="country" id="country" type="text" hidden required autocomplete="country">
                            </div>
                        </div>
                        <div class="from__input-box mb-3">
                            <div class="form__input-title d-flex justify-content-between">
                                <label for="password" class="form-label">Password</label>
                            </div>
                            <div class="form__input">
                                <input class="form-control" placeholder="Password" type="password" name="password"
                                    required id="password">
                                <div class="pass-icon" id="passwordToggle"><i
                                        class="fa-sharp fa-light fa-eye-slash"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms">
                                <label class="form-check-label small text-muted" for="terms">
                                    I agree to the <a href="#" class="amsol-link">Terms of Service</a> and <a href="#"
                                        class="amsol-link">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        <div style="max-width: 500px; min-width: 100%; width: 100%; background-color: white;"
                            class="mb-3 text-center">
                            <x-turnstile />
                        </div>
                        @error('cf-turnstile-response') <span class="text-danger">{{ $message }}</span> @enderror
                        <div class="mb-4">
                            <button class="btn btn-primary w-100 py-2" onclick="register(this)" type="button">
                                <i class="bi bi-check-circle me-1"></i> Create Account
                            </button>
                        </div>
                    </form>

                    <p class="text-center mb-0">
                        <span>Have an account?</span>
                        <a href="{{ route('login') }}">
                            <span class="fw-bold amsol-link">Sign In</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Original styles preserved */
        .iti {
            width: 100%;
        }

        .iti__country-name {
            display: inline-block !important;
            margin-left: 6px;
            color: #000;
            font-weight: 400;
            width: 275px;
        }

        /* New styles to match inspiration with full width */
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .authentication-wrapper {
            min-height: 100vh;
            width: 100%;
            margin: 0;
            display: flex;
            align-items: stretch;
        }

        .authentication-inner {
            width: 100%;
            margin: 0;
            min-height: 100vh;
        }

        .marketing-section {
            background: rgb(6, 69, 125);
            /* AMSOL blue color */
            color: white;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .marketing-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='50' cy='50' r='5' fill='%23ffffff20'/%3E%3C/svg%3E");
            background-size: 150px 150px;
            opacity: 0.3;
        }

        .marketing-content {
            position: relative;
            z-index: 2;
            max-width: 500px;
            margin: 0 auto;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
        }

        .feature-icon {
            background: rgba(255, 197, 6, 0.15);
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .feature-icon i {
            font-size: 1.5rem;
        }

        .feature-text h5 {
            font-weight: 600;
            margin-bottom: 8px;
            color: rgb(255, 202, 9);
        }

        .feature-text p {
            opacity: 0.85;
            font-size: 0.95rem;
            line-height: 1.5;
            color: #fff;
        }

        .form-side {
            background-color: white;
        }

        .form-container {
            padding: 2.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 500px;
            margin: 0 auto;
        }

        .form-control {
            padding: 0.6rem 1rem;
            border-radius: 6px;
        }

        .btn-primary {
            background-color: #0A4B9F;
            /* AMSOL blue color */
            border-color: #0A4B9F;
            border-radius: 6px;
        }

        .btn-primary:hover {
            background-color: #083e84;
            border-color: #083e84;
        }

        .amsol-text {
            color: #0A4B9F;
            /* AMSOL blue color */
        }

        .amsol-link {
            color: #0A4B9F;
            /* AMSOL blue color */
            text-decoration: none;
        }

        .amsol-link:hover {
            text-decoration: underline;
        }

        /* Mobile responsiveness fixes */
        @media (max-width: 767px) {
            .form-container {
                padding: 1.5rem;
            }

            /* Show both sections on mobile, stacked */
            .marketing-section {
                min-height: 350px;
            }
        }
    </style>
</x-auth-layout>

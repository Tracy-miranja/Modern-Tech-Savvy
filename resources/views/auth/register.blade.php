<x-auth-layout>
    <div class="authentication-wrapper">
        <div class="authentication-inner row g-0">
            <!-- Marketing Section with Illustration (Left Side) -->
            <div class="col-md-6 col-sm-12 marketing-section">
                <div class="marketing-content p-4 p-lg-5">
                    <h3 class="hero-text"><span class="text-highlight">Where your</span> journey <span class="text-highlight">begins.</span></h3>
                </div>
            </div>

            <!-- Registration Form (Right Side) -->
            <div class="col-md-6 col-sm-12 form-side">
                <div class="form-container">
                    <div class="authentication-top text-center mb-5">
                        <h4 class="form-title">Get Started</h4>
                        <p class="form-subtitle">Create your Krest PayHR account</p>
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

                        <div class="form-input-group mb-4">
                            <input class="form-input-field" placeholder="Full Name" name="name" id="name"
                                :value="old('name')" type="text" required autocomplete="name">
                        </div>

                        <div class="form-input-group mb-4">
                            <input class="form-input-field" placeholder="Email Address" name="email" id="email" type="email"
                                required autocomplete="email">
                        </div>

                        <div class="form-input-group mb-4">
                            <input class="phone-input-control form-input-field" name="phone" id="phone" type="text" required
                                autocomplete="phone">
                            <input name="code" id="code" type="text" hidden required autocomplete="code">
                            <input name="country" id="country" type="text" hidden required autocomplete="country">
                        </div>

                        <div class="form-input-group mb-4">
                            <div class="password-wrapper">
                                <input class="form-input-field" placeholder="Password" type="password" name="password"
                                    required id="password">
                                <div class="pass-icon" id="passwordToggle"><i class="fa-sharp fa-light fa-eye-slash"></i></div>
                            </div>
                        </div>

                        <div class="form-input-group mb-4">
                            <div class="password-wrapper">
                                <input class="form-input-field" placeholder="Confirm Password" type="password" name="password_confirmation"
                                    required id="password_confirmation">
                                <div class="pass-icon"><i class="fa-sharp fa-light fa-eye-slash"></i></div>
                            </div>
                        </div>

                        <div class="form-checkbox mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="checkbox-link">terms of service</a> and <a href="#" class="checkbox-link">privacy statement</a>
                            </label>
                        </div>

                        <div style="max-width: 500px; min-width: 100%; width: 100%; background-color: white;"
                            class="mb-4 text-center">
                            <x-turnstile />
                        </div>
                        @error('cf-turnstile-response') <span class="text-danger">{{ $message }}</span> @enderror

                        <button class="btn-signup w-100 py-3 mb-4" onclick="register(this)" type="button">
                            Sign Up
                        </button>

                        <div class="signin-section mb-4">
                            <p class="signin-text">Already have an account? <a href="{{ route('login') }}" class="signin-link">Sign in</a></p>
                        </div>

                        <div class="divider mb-4">
                            <span>Or sign up with</span>
                        </div>

                        <div class="social-buttons">
                            <button type="button" class="social-btn google-btn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </button>
                            <button type="button" class="social-btn facebook-btn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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

        /* Marketing Section */
        .marketing-section {
            background: url('https://images.unsplash.com/photo-1556761175-4b46a572b786?w=1920&q=80') center/cover;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .marketing-content {
            position: relative;
            z-index: 2;
            max-width: 100%;
            text-align: center;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .hero-text {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.3;
            color: #ffffff;
        }

        .text-highlight {
            color: #47B5D6;
        }

        /* Form Side */
        .form-side {
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
        }

        .authentication-top {
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            margin: 0;
        }

        /* Form Inputs */
        .form-input-group {
            position: relative;
        }

        .form-input-field {
            width: 100%;
            padding: 0.875rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: #fafafa;
            color: #1f2937;
        }

        .form-input-field::placeholder {
            color: #9ca3af;
        }

        .form-input-field:focus {
            outline: none;
            border-color: #47B5D6;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(71, 181, 214, 0.1);
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper .form-input-field {
            width: 100%;
        }

        .pass-icon {
            position: absolute;
            right: 1.25rem;
            cursor: pointer;
            color: #9ca3af;
            font-size: 1.1rem;
        }

        /* Checkbox */
        .form-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .form-check-input {
            margin-top: 0.25rem;
            width: 18px;
            height: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 4px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .form-check-input:checked {
            background-color: #47B5D6;
            border-color: #47B5D6;
        }

        .form-check-label {
            font-size: 0.85rem;
            color: #6b7280;
            cursor: pointer;
            line-height: 1.4;
        }

        .checkbox-link {
            color: #47B5D6;
            text-decoration: none;
            font-weight: 500;
        }

        .checkbox-link:hover {
            text-decoration: underline;
        }

        /* Signup Button */
        .btn-signup {
            background: linear-gradient(135deg, #2d8a9e 0%, #47B5D6 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(71, 181, 214, 0.3);
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(71, 181, 214, 0.4);
        }

        .btn-signup:active {
            transform: translateY(0);
        }

        /* Sign In Section */
        .signin-section {
            text-align: center;
        }

        .signin-text {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }

        .signin-link {
            color: #47B5D6;
            text-decoration: none;
            font-weight: 600;
        }

        .signin-link:hover {
            text-decoration: underline;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider::before {
            margin-right: 0.75rem;
        }

        .divider::after {
            margin-left: 0.75rem;
        }

        /* Social Buttons */
        .social-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background-color: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .social-btn:hover {
            border-color: #47B5D6;
            background-color: #f0f9fc;
        }

        .google-btn:hover svg {
            opacity: 1;
        }

        /* Phone Input Override */
        .iti {
            width: 100%;
        }

        .iti__country-name {
            display: inline-block !important;
            margin-left: 6px;
            color: #1f2937;
            font-weight: 400;
            width: 275px;
        }

        /* Error Message */
        .text-danger {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .marketing-section {
                min-height: 300px;
                padding: 2rem 1rem;
            }

            .hero-text {
                font-size: 1.5rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .authentication-inner {
                flex-direction: column;
            }

            .col-md-6 {
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1rem;
            }

            .form-input-field {
                padding: 0.75rem 1rem;
            }

            .social-buttons {
                gap: 0.75rem;
            }

            .social-btn {
                width: 45px;
                height: 45px;
            }
        }
    </style>
</x-auth-layout>

{{-- <x-auth-layout>
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
</x-auth-layout> --}}

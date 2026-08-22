<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ config('app.url') }}">

    <title>@isset($title) {{ $title }} -@endisset {{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="{{ asset('assets/css/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/apexcharts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/jquery-jvectormap-2.0.5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/icomoon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/fontawesome-pro.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/rating.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/dropzone.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dropify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendor/spacing.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/buttons.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/jquery.timepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/tagify.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/fullcalendar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/ion.rangeSlider.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/waves.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/nano.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/applications-module.css') }}">


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css" />
    <!-- Intro.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/css/intlTelInput.min.css">
    <link rel="shortcut icon" href="{{ asset('media/favicon.png') }}" type="image/png">

    <!-- PWA: installable on desktop and mobile -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#f89616">
    <link rel="apple-touch-icon" href="{{ asset('media/pwa/icon-192.png') }}">

    {{-- Per-page stylesheets/styles (e.g. a library only that one page
         needs) - without this @stack, anything a page pushes to
         @push('styles') is silently dropped. --}}
    @stack('styles')

    <style>
        .impersonation-banner-bar {
            display: flex !important;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-around;
            width: 100%;
            height: auto !important;
            min-height: 65px;
            overflow: visible !important;
            position: relative;
            z-index: 2000;
            line-height: 1.5;
            padding: 10px 24px;
            background-color: #16518D;
            color: #fbfcfd !important;
            border-bottom: 1px solid rgb(252, 248, 248);
        }

        .impersonation-banner-bar span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #impersonation-banner select#switchClientSelect {
            height: 32px;
        }
    </style>

</head>

<body class="body-area">

    <input type="text" id="receipient_id" value="{{ auth()->user()->id }}" hidden>

    <style>
        body {
            visibility: hidden;
        }

        .preloader {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
    height: 100% !important;
        background: linear-gradient(135deg, #0f1729 0%, #1a2847 50%, #0d1525 100%);
        z-index: 999999 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        opacity: 1;
        transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s ease;
        pointer-events: auto;
        overflow: hidden;
    }

    .preloader::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 20% 50%, rgba(243, 159, 4, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(243, 159, 4, 0.06) 0%, transparent 50%);
        pointer-events: none;
        animation: ambientShift 8s ease-in-out infinite;
    }

    @keyframes ambientShift {
        0%, 100% { opacity: 0.8; }
        50% { opacity: 1; }
    }

    .preloader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .loader-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .modern-spinner {
        width: 80px;
        height: 80px;
        position: relative;
        margin-bottom: 2.5rem;
    }



    .modern-spinner::before,
    .modern-spinner::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        border: 2px solid transparent;
        inset: 0;
    }

    .modern-spinner::before {
         border-top-color: #f39f04;
    border-right-color: #f39f04;
    animation: spinOuter 1.2s linear infinite;
        box-shadow:
            0 0 20px rgba(243, 159, 4, 0.4),
            inset 0 0 20px rgba(243, 159, 4, 0.1);
    }

    .modern-spinner::after {
        border-bottom-color: rgba(243, 159, 4, 0.25);
    border-left-color: rgba(243, 159, 4, 0.25);
    animation: spinInner 1.2s linear infinite reverse;
        inset: 8px;
    }

    @keyframes spinOuter {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes spinInner {
        from { transform: rotate(360deg); }
        to { transform: rotate(0deg); }
    }

    .loader-text {
        color: #f39f04;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-top: 2.5rem;
        animation: fadeInOutText 2.5s ease-in-out infinite;
    }

    @keyframes fadeInOutText {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }

    .loader-accent {
        position: absolute;
        background: radial-gradient(circle, #f39f04 0%, rgba(243, 159, 4, 0.4) 100%);
        border-radius: 50%;
        opacity: 0.4;
    }

    .loader-accent-1 {
        width: 8px;
        height: 8px;
        top: 15%;
        left: 10%;
        animation: floatAccent1 6s ease-in-out infinite;
    }

    .loader-accent-2 {
        width: 6px;
        height: 6px;
        top: 20%;
        right: 15%;
        animation: floatAccent2 7s ease-in-out infinite;
    }

    .loader-accent-3 {
        width: 6px;
        height: 6px;
        bottom: 20%;
        left: 12%;
        animation: floatAccent3 8s ease-in-out infinite;
    }

    .loader-accent-4 {
        width: 8px;
        height: 8px;
        bottom: 15%;
        right: 10%;
        animation: floatAccent4 6.5s ease-in-out infinite;
    }

    @keyframes floatAccent1 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(-10px, -15px) scale(1.3); opacity: 0.7; }
    }

    @keyframes floatAccent2 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(12px, -12px) scale(1.3); opacity: 0.6; }
    }

    @keyframes floatAccent3 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(10px, 15px) scale(1.3); opacity: 0.6; }
    }

    @keyframes floatAccent4 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(-12px, 12px) scale(1.3); opacity: 0.7; }
    }
</style>

<script>
    // Show preloader immediately on page load
    document.body.style.visibility = 'visible';

    // Hide preloader when page fully loads
    window.addEventListener('load', function() {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.add('hidden');
            setTimeout(() => {
                preloader.remove();
            }, 800);
        }
    });
</script>

<!-- Modern Preloader -->
<div class="preloader" id="preloader">
    <div class="loader-accent loader-accent-1"></div>
    <div class="loader-accent loader-accent-2"></div>
    <div class="loader-accent loader-accent-3"></div>
    <div class="loader-accent loader-accent-4"></div>
    <div class="loader-container">
        <div class="modern-spinner"></div>
        <div class="loader-text">Loading</div>
    </div>
</div>
    @if(session('original_business_slug'))
    <div id="impersonation-banner" class="impersonation-banner-bar">
        <span>
            <i class="fas fa-user-secret"></i>
            Viewing as <strong>{{ \App\Models\Business::findBySlug(session('active_business_slug'))->company_name }}</strong>
            &mdash; platform admin session
        </span>
        <span>
            <select id="switchClientSelect" class="form-select form-select-sm d-inline-block w-fit me-2">
                <option value="">Switch to another business...</option>
            </select>
            <button class="btn btn-sm btn-dark" onclick="switchBackToAdmin()">
                Return to platform business
            </button>
        </span>
    </div>
    @endif

    @if(session('impersonating_original_user_id'))
    <div id="employee-impersonation-banner" class="impersonation-banner-bar">
        <span>
            <i class="fas fa-user-secret"></i>
            Logged in as <strong>{{ auth()->user()->name }}</strong> &mdash; admin session
        </span>
        <span>
            <button class="btn btn-sm btn-dark" onclick="stopImpersonatingEmployee()">
                Return to My Account
            </button>
        </span>
    </div>
    @endif

    <div class="page__full-wrapper">

        @php
        $activeRole = session('active_role');
        @endphp

        @if (in_array($activeRole, ['business-admin', 'business-hr', 'business-finance', 'restricted-hr', 'general-hr', 'head-of-department', 'chief-of-staff']))
        @include('layouts.partials.navbar')
        @elseif ($activeRole === 'business-employee')
        @include('layouts.partials.navbar-employee')
        @elseif ($activeRole === 'super-admin')
        @include('layouts.partials.navbar-super-admin')
        @endif


        <div class="page__body-wrapper">

            @include('layouts.partials.app-header')

            <div class="app__slide-wrapper">
                {{-- Nothing anywhere in this layout displayed flash
                messages before - every back()->with('error'/'success', ...)
                across the app silently showed nothing, which is why
                validation bounces (e.g. "no employee record for this
                business") looked exactly like a dead link. --}}
                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                {{ $slot }}
            </div>

            @include('layouts.partials.app-footer')

        </div>

    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.body.style.visibility = "visible";
        });
    </script>
    <!-- Intro.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
    <script src="{{ asset('assets/js/vendor/jquery-3.7.0.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1/tinymce.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script src="{{ asset('assets/js/vendor/isotope.pkgd.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/magnific-popup.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/ajax-form.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/jquery.repeater.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/waypoints.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dayjs.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/loader.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/world-merc.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simplebar-active.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/backtotop.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/smooth-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/cleave.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/buttons.colVis.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/steps-form.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dropzone.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/custom.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/typeahead.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bloodhound.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/jquery.timepicker.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/tagify.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/fullcalendar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/ion.rangeSlider.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/custom-tagify.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/height-equal.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/custom-chart.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/rangeslider-script.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/jquery.barrating.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/rating-script.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/sidebar.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js"></script>

    <script src="{{ asset('js/init.js') }}"></script>
    {{-- <script src="{{ asset('js/pusher.js') }}"></script> --}}
    <script>
        // Global so any page's scripts (impersonation banner, switch-business
        // dropdown, etc.) can build /businesses/{slug}/... URLs without each
        // page having to define this itself - previously only a handful of
        // pages set this, so it came back "undefined" everywhere else.
        window.currentBusinessSlug = @json($currentBusiness->slug ?? null);
    </script>
    <script src="{{ asset('js/main/logout.js') }}" type="module"></script>
    <script src="{{ asset('js/main/impersonation.js') }}" type="module"></script>
    <script src="{{ asset('js/pwa-register.js') }}"></script>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('[id^="passwordToggle"]');

            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const fieldId = this.id.replace('toggle', '').toLowerCase();
                    const passwordField = document.getElementById(fieldId);

                    if (passwordField) {
                        const type = passwordField.getAttribute('type') === 'password' ? 'text' :
                            'password';
                        passwordField.setAttribute('type', type);
                        this.innerHTML = type === 'password' ? '👁️' : '👁️‍🗨️';
                    }
                });
            });


            // Initialize all dropdowns with global configuration
            document.querySelectorAll('.dropdown-toggle').forEach(button => {
                if (!button.dataset.initialized) {
                    try {
                        new bootstrap.Dropdown(button, {
                            boundary: document.querySelector('body'),
                            popperConfig: function(defaultBsPopperConfig) {
                                return {
                                    ...defaultBsPopperConfig,
                                    placement: 'bottom-end',
                                    strategy: 'fixed',
                                    modifiers: [{
                                            name: 'preventOverflow',
                                            options: {
                                                boundary: 'viewport',
                                            },
                                        },
                                        {
                                            name: 'offset',
                                            options: {
                                                offset: [0, 2],
                                            },
                                        },
                                    ],
                                };
                            }
                        });
                        button.dataset.initialized = 'true';
                        console.log('Initialized dropdown:', button.id);
                    } catch (error) {
                        console.error('Dropdown initialization failed for', button.id, ':', error);
                    }
                }
            });

            // Handle sub-dropdown hover behavior
            document.querySelectorAll('.dropdown-submenu').forEach(submenu => {
                submenu.addEventListener('mouseenter', function() {
                    const submenuMenu = this.querySelector('.dropdown-menu');
                    if (submenuMenu) {
                        submenuMenu.classList.add('show');
                    }
                });
                submenu.addEventListener('mouseleave', function() {
                    const submenuMenu = this.querySelector('.dropdown-menu');
                    if (submenuMenu) {
                        submenuMenu.classList.remove('show');
                    }
                });
            });


            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.addEventListener('mouseleave', function() {
                    const dropdownMenu = this.querySelector('.dropdown-menu');
                    if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                        const dropdownInstance = bootstrap.Dropdown.getInstance(this.querySelector(
                            '.dropdown-toggle'));
                        if (dropdownInstance) {
                            dropdownInstance.hide();
                        }
                    }
                });
            });
        });


        const phoneInputFields = document.querySelectorAll('.phone-input-control');

        phoneInputFields.forEach((phoneInput, index) => {
            const codeInput = document.querySelector(phoneInput.dataset.codeInput || `#code${index}`);
            const countryInput = document.querySelector(phoneInput.dataset.countryInput || `#country${index}`);

            const iti = window.intlTelInput(phoneInput, {

                separateDialCode: true,
                geoIpLookup: function(callback) {
                    getIp(function(countryCode) {
                        callback(countryCode.toLowerCase());
                    });
                },
                utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js',
            });

            phoneInput.addEventListener('countrychange', function() {
                if (codeInput) {
                    codeInput.value = '+' + iti.getSelectedCountryData().dialCode;
                }
                if (countryInput) {
                    countryInput.value = iti.getSelectedCountryData().name;
                }
                updateSelectedCountryName(phoneInput, iti);
            });

            const initialPhone = phoneInput.value;
            const initialCode = codeInput ? codeInput.value : '';
            const initialCountry = countryInput ? countryInput.value : '';

            if (initialPhone) {
                iti.setNumber(initialPhone);
                if (codeInput && initialCode) {
                    codeInput.value = initialCode;
                }
                if (countryInput && initialCountry) {
                    countryInput.value = initialCountry;
                }
            } else {
                if (codeInput) {
                    codeInput.value = '+' + iti.getSelectedCountryData().dialCode;
                }
                if (countryInput) {
                    countryInput.value = iti.getSelectedCountryData().name;
                }
            }

            const form = phoneInput.closest('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!iti.isValidNumber()) {
                        event.preventDefault();
                        phoneInput.classList.add('is-invalid');
                        let errorSpan = phoneInput.parentElement.querySelector('.text-danger');
                        if (!errorSpan) {
                            errorSpan = document.createElement('span');
                            errorSpan.className = 'text-danger';
                            errorSpan.textContent = 'Please enter a valid phone number.';
                            phoneInput.parentElement.appendChild(errorSpan);
                        }
                    } else {
                        phoneInput.classList.remove('is-invalid');
                        const errorSpan = phoneInput.parentElement.querySelector('.text-danger');
                        if (errorSpan) errorSpan.remove();
                        phoneInput.value = iti.getNumber();
                    }
                });
            }
        });

        function updateSelectedCountryName(phoneInput, iti) {
            const selectedFlag = phoneInput.parentNode.querySelector('.iti__selected-flag');
            let nameSpan = selectedFlag.querySelector('.selected-country-name');

            const countryData = iti.getSelectedCountryData();

            if (!nameSpan) {
                nameSpan = document.createElement('span');
                nameSpan.className = 'selected-country-name';
                nameSpan.style.marginLeft = '6px';
                nameSpan.style.fontWeight = '500';
                selectedFlag.appendChild(nameSpan);
            }

            nameSpan.textContent = countryData.name;
        }

        function getIp(callback) {
            fetch('https://ipinfo.io/json?token=a876c4d470b426', {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then((resp) => resp.json())
                .catch(() => {
                    return {
                        country: 'ke'
                    };
                })
                .then((resp) => callback(resp.country));
        }
    </script>

    @stack('scripts')

</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
 <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PQJZ4ZF5');</script>
<!-- End Google Tag Manager -->

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
    <link rel="shortcut icon" href="media/favicon.ico" type="image/x-icon">
   <style>
        #impersonation-banner.impersonation-banner-bar {
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

        #impersonation-banner span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #impersonation-banner select#switchClientSelect {
            height: 32px;
        }

        body.has-impersonation-banner .app-header,
        body.has-impersonation-banner header,
        body.has-impersonation-banner .navbar-fixed,
        body.has-impersonation-banner [class*="header"] {

        }
    </style>

</head>


<body class="body-area">
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PQJZ4ZF5"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <input type="text" id="receipient_id" value="{{ auth()->user()->id }}" hidden>

    <style>
        body {
            visibility: hidden;
        }
    </style>

    <div class="preloader" id="preloader">
        <div class="loading">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>

    @if(session('original_business_slug'))
<div id="impersonation-banner" class="impersonation-banner-bar">
        <span>
            <i class="fas fa-user-secret"></i>
            Viewing as <strong>{{ \App\Models\Business::findBySlug(session('active_business_slug'))->company_name }}</strong>
            &mdash; krest admin session
        </span>
        <span>
            <select id="switchClientSelect" class="form-select form-select-sm d-inline-block w-fit me-2">
                <option value="">Switch to another business...</option>
            </select>
            <button class="btn btn-sm btn-dark" onclick="switchBackToAdmin()">
                Return to krest
            </button>
        </span>
    </div>
    @endif

    <div class="page__full-wrapper">


        @php
        $activeRole = session('active_role');
        @endphp

        @if (in_array($activeRole, ['business-admin', 'business-hr', 'business-finance', 'krest-admin', 'restricted-hr', 'general-hr', 'head-of-department', 'chief-of-staff']))
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
    <script src="{{ asset('js/main/logout.js') }}" type="module"></script>
    <script>
        window.currentBusinessSlug = "{{ session('active_business_slug') }}";
    </script>
    <script src="{{ asset('js/main/impersonation.js') }}" type="module"></script>

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


            // $(".switch-role").click(function() {
            //     let selectedRole = $(this).data("role");

            //     $.ajax({
            //         url: $("#switchRoleForm").attr("action"),
            //         method: "POST",
            //         data: {
            //             _token: $('meta[name="csrf-token"]').attr("content"),
            //             role: selectedRole
            //         },
            //         success: function(response) {
            //             window.location.href = response.redirect;
            //         },
            //         error: function(error) {
            //             console.log(error);
            //             alert("You do not have permission to switch to this role.");
            //         }
            //     });
            // });


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

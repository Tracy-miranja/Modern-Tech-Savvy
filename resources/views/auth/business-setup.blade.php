<x-setup-layout>
    <div class="authentication-inner row">
        <div class="d-none d-md-flex col-lg-6 col-md-6 p-0">
            <div class="authentication-image d-flex justify-content-center align-items-center">
                <img src="/assets/images/sign/sign-up.png" alt="image">
            </div>
        </div>

        <div class="d-flex col-lg-6 col-md-6 col-12 align-items-center">
            <div class="card__wrapper p-4">
                <div class="authentication-top text-center mb-4">
                    <a href="javascript:;" class="authentication-logo logo-black">
                        <img src="{{ asset('media/krest-logo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <a href="javascript:;" class="authentication-logo logo-white">
                        <img src="{{ asset('media/krest-logo.png') }}" alt="{{ config('app.name') }}">
                    </a>
                    <h4 class="mb-3">Set up your business</h4>
                    <p class="text-muted mb-4">Let's get your organization ready in two simple steps.</p>
                    <div class="progress mb-4" style="height: 8px;">
                        <div id="progressBar" class="progress-bar bg-primary" role="progressbar" style="width: 50%;"
                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div id="stepIndicator" class="text-muted mb-3">Step 1 of 2: Basic Information</div>
                </div>

                <form id="hrmSetupForm" enctype="multipart/form-data">
                    @csrf
                    <div id="step1" class="step active">
                        <div class="from__input-box mb-4">
                            <label for="name" class="form-label">Company / Organization Name</label>
                            <input class="form-control shadow-sm" placeholder="Company / Organization Name" name="name"
                                id="name" value="{{ old('name', $business->company_name ?? '') }}" type="text" required
                                autocomplete="name">
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="company_size" class="form-label">Company Size</label>
                            <select id="company_size" name="company_size" required class="form-select shadow-sm">
                                <option value="">Select Company Size</option>
                                <option value="1-10"
                                    {{ old('company_size', $business->company_size ?? '') === '1-10' ? 'selected' : '' }}>
                                    1-10 employees</option>
                                <option value="11-50"
                                    {{ old('company_size', $business->company_size ?? '') === '11-50' ? 'selected' : '' }}>
                                    11-50 employees</option>
                                <option value="51-200"
                                    {{ old('company_size', $business->company_size ?? '') === '51-200' ? 'selected' : '' }}>
                                    51-200 employees</option>
                                <option value="201-500"
                                    {{ old('company_size', $business->company_size ?? '') === '201-500' ? 'selected' : '' }}>
                                    201-500 employees</option>
                                <option value="500+"
                                    {{ old('company_size', $business->company_size ?? '') === '500+' ? 'selected' : '' }}>
                                    500+ employees</option>
                            </select>
                            @error('company_size') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="industry" class="form-label">Industry</label>
                            <select id="industry" name="industry" required class="form-select shadow-sm">
                                <option value="">Select Industry</option>
                                @foreach ($industries as $industry)
                                <option value="{{ $industry->slug }}"
                                    {{ old('industry', $business->industry ?? '') === $industry->slug ? 'selected' : '' }}>
                                    {{ $industry->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('industry') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <div class="form__input-title">
                                <label for="phone" class="form-label">Contact Phone</label>
                            </div>
                            <div class="form__input">
                                <input class="shadow-sm phone-input-control" name="phone" id="phone" type="tel" required
                                    autocomplete="tel" value="{{ old('phone', $business->phone ?? '') }}">
                                <input name="code" hidden id="code" type="text" autocomplete="code"
                                    value="{{ old('code', $business->code ?? '') }}">
                                <input name="country" hidden id="country" type="text" autocomplete="country"
                                    value="{{ old('country', $business->country ?? '') }}">
                                @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" id="nextBtn" class="btn btn-primary px-4">Next <i
                                    class="ms-2 bi bi-arrow-right"></i></button>
                        </div>
                    </div>

                    <div id="step2" class="step">
                        <div class="from__input-box mb-4">
                            <label for="registration_no" class="form-label">Registration Number</label>
                            <input class="form-control shadow-sm" placeholder="Registration Number"
                                name="registration_no" id="registration_no"
                                value="{{ old('registration_no', $business->registration_no ?? '') }}" type="text"
                                required autocomplete="registration_no">
                            @error('registration_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="tax_pin_no" class="form-label">Tax PIN Number</label>
                            <input class="form-control shadow-sm" placeholder="Tax PIN Number" name="tax_pin_no"
                                id="tax_pin_no" value="{{ old('tax_pin_no', $business->tax_pin_no ?? '') }}" type="text"
                                required autocomplete="tax_pin_no">
                            @error('tax_pin_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="business_license_no" class="form-label">Business License Number</label>
                            <input class="form-control shadow-sm" placeholder="Business License Number"
                                name="business_license_no" id="business_license_no"
                                value="{{ old('business_license_no', $business->business_license_no ?? '') }}"
                                type="text" required autocomplete="business_license_no">
                            @error('business_license_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="physical_address" class="form-label">Physical Address</label>
                            <input class="form-control shadow-sm" placeholder="Search for your address"
                                name="physical_address" id="physical_address" type="text" required
                                autocomplete="physical_address" list="address-suggestions"
                                value="{{ old('physical_address', $business->physical_address ?? '') }}">
                            <datalist id="address-suggestions"></datalist>
                            @error('physical_address') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="from__input-box mb-4">
                            <label for="logo" class="form-label">Upload Your Logo</label>
                            <input class="form-control shadow-sm" type="file" name="logo" required id="logo"
                                accept="image/*">
                            @if($business && $business->hasMedia('businesses'))
                            <div class="mt-2">
                                <img src="{{ $business->getFirstMediaUrl('businesses', 'thumb') }}" alt="Current Logo"
                                    style="max-width: 100px;">
                                <p class="text-muted small">Current logo</p>
                            </div>
                            @endif
                            @error('logo') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" id="prevBtn" class="btn btn-outline-secondary px-4"><i
                                    class="me-2 bi bi-arrow-left"></i> Back</button>
                            <button class="btn btn-primary px-4" type="button" onclick="register(this)">Complete Setup
                                <i class="ms-2 bi bi-check-circle"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .card__wrapper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 450px;
            margin: auto;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
        }

        .btn-primary {
            background: linear-gradient(90deg, #007bff, #0056b3);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #0056b3, #003d80);
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border-radius: 8px;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .progress {
            border-radius: 4px;
            background: #e9ecef;
        }

        .progress-bar {
            transition: width 0.3s ease;
        }

        .step {
            transition: all 0.3s ease;
        }

        .step:not(.active) {
            display: none;
            opacity: 0;
        }

        .step.active {
            display: block;
            opacity: 1;
        }

        label.form-label {
            font-weight: 500;
            color: #333;
        }

        .text-muted {
            color: #6c757d !important;
        }

        #address-suggestions option {
            padding: 8px;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .text-danger {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

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
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const phoneInputField = document.querySelector(".phone-input-control");

            if (phoneInputField) {
                initializePhoneInput();

                phoneInputField.addEventListener("countrychange", function() {
                    const phoneInput = window.intlTelInputGlobals.getInstance(phoneInputField);
                    const selectedCountryData = phoneInput.getSelectedCountryData();
                    document.querySelector("#code").value = selectedCountryData.dialCode;
                    document.querySelector("#country").value = selectedCountryData.name;
                });
            }

            function initializePhoneInput() {
                const phoneInput = window.intlTelInput(phoneInputField, {
                    preferredCountries: ['ke', 'ug', 'gb', 'rw', 'ng', 'za', 'tz', 'tn', 'et', 'za'],
                    initialCountry: "auto",
                    nationalMode: true,
                    geoIpLookup: getIp,
                    separateDialCode: true,
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
                });

                phoneInputField.addEventListener("countrychange", function() {
                    const selectedCountryData = phoneInput.getSelectedCountryData();
                    document.querySelector("#code").value = selectedCountryData.dialCode;
                    document.querySelector("#COUNTRY").value = selectedCountryData.dialCode;
                });
            }

            function getIp(callback) {
                fetch('https://ipinfo.io/json?token=a876c4d470b426', {
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then((resp) => resp.json()).catch(() => {
                    return {
                        country: 'ke',
                    };
                }).then((resp) => callback(resp.country));
            }

            const step1 = document.getElementById("step1");
            const step2 = document.getElementById("step2");
            const nextBtn = document.getElementById("nextBtn");
            const prevBtn = document.getElementById("prevBtn");
            const progressBar = document.getElementById("progressBar");
            const stepIndicator = document.getElementById("stepIndicator");

            function validateStep1() {
                const inputs = step1.querySelectorAll("input[required], select[required]");
                let valid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add("is-invalid");
                        let errorSpan = input.parentElement.querySelector(".text-danger");
                        if (!errorSpan) {
                            errorSpan = document.createElement("span");
                            errorSpan.className = "text-danger";
                            errorSpan.textContent =
                                `${input.name.charAt(0).toUpperCase() + input.name.slice(1)} is required.`;
                            input.parentElement.appendChild(errorSpan);
                        }
                    } else {
                        input.classList.remove("is-invalid");
                        const errorSpan = input.parentElement.querySelector(".text-danger");
                        if (errorSpan) errorSpan.remove();
                    }
                });
                return valid;
            }

            nextBtn.addEventListener("click", () => {
                if (validateStep1()) {
                    step1.classList.remove("active");
                    step2.classList.add("active");
                    progressBar.style.width = "100%";
                    progressBar.setAttribute("aria-valuenow", "100");
                    stepIndicator.textContent = "Step 2 of 2: Additional Details";
                }
            });

            prevBtn.addEventListener("click", () => {
                step2.classList.remove("active");
                step1.classList.add("active");
                progressBar.style.width = "50%";
                progressBar.setAttribute("aria-valuenow", "50");
                stepIndicator.textContent = "Step 1 of 2: Basic Information";
            });

        });
    </script>
</x-setup-layout>

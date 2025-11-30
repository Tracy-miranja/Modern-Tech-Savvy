<x-setup-layout>
    <div class="setup-wrapper">
        <div class="setup-container">
            <!-- Left Section - Visual -->
            <div class="setup-visual">
                <div class="visual-content">
                    <div class="visual-card">
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&q=80" alt="Business Setup">
                    </div>
                    <div class="visual-overlay">
                        <h2 class="text-white">Complete Your Business Setup</h2>
                        <p>Just two simple steps to get your organization ready</p>
                    </div>
                </div>
            </div>

            <!-- Right Section - Form -->
            <div class="setup-form-section">
                <div class="form-wrapper">
                    <!-- Header -->
                    <div class="form-header">
                        <a href="javascript:;" class="logo">
                            <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }}">
                        </a>
                        <div class="header-content">
                            <h3>Set up your business</h3>
                            <p>Let's get your organization ready in two simple steps</p>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="progress-section">
                        <div class="progress-track">
                            <div id="progressBar" class="progress-fill"></div>
                        </div>
                        <div id="stepIndicator" class="step-label">Step 1 of 2: Basic Information</div>
                    </div>

                    <!-- Form -->
                    <form id="hrmSetupForm" enctype="multipart/form-data">
                        @csrf

                        <!-- Step 1 -->
                        <div id="step1" class="form-step active">
                            <div class="form-group">
                                <label for="name" class="form-label">Company / Organization Name</label>
                                <input class="form-input" placeholder="Enter your company name" name="name"
                                    id="name" value="{{ old('name', $business->company_name ?? '') }}" type="text" required>
                                @error('name') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="company_size" class="form-label">Company Size</label>
                                <select id="company_size" name="company_size" required class="form-input">
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
                                @error('company_size') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="industry" class="form-label">Industry</label>
                                <select id="industry" name="industry" required class="form-input">
                                    <option value="">Select Industry</option>
                                    @foreach ($industries as $industry)
                                    <option value="{{ $industry->slug }}"
                                        {{ old('industry', $business->industry ?? '') === $industry->slug ? 'selected' : '' }}>
                                        {{ $industry->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('industry') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input class="form-input phone-input-control" name="phone" id="phone" type="tel" required
                                    value="{{ old('phone', $business->phone ?? '') }}">
                                <input name="code" hidden id="code" type="text" value="{{ old('code', $business->code ?? '') }}">
                                <input name="country" hidden id="country" type="text" value="{{ old('country', $business->country ?? '') }}">
                                @error('phone') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-actions">
                                <button type="button" id="nextBtn" class="">
                                    Next <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div id="step2" class="form-step">
                            <div class="form-group">
                                <label for="registration_no" class="form-label">Registration Number</label>
                                <input class="form-input" placeholder="Enter registration number"
                                    name="registration_no" id="registration_no"
                                    value="{{ old('registration_no', $business->registration_no ?? '') }}" type="text" required>
                                @error('registration_no') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="tax_pin_no" class="form-label">Tax PIN Number</label>
                                <input class="form-input" placeholder="Enter tax PIN number" name="tax_pin_no"
                                    id="tax_pin_no" value="{{ old('tax_pin_no', $business->tax_pin_no ?? '') }}" type="text" required>
                                @error('tax_pin_no') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="business_license_no" class="form-label">Business License Number</label>
                                <input class="form-input" placeholder="Enter business license number"
                                    name="business_license_no" id="business_license_no"
                                    value="{{ old('business_license_no', $business->business_license_no ?? '') }}"
                                    type="text" required>
                                @error('business_license_no') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="physical_address" class="form-label">Physical Address</label>
                                <input class="form-input" placeholder="Search for your address"
                                    name="physical_address" id="physical_address" type="text" required
                                    list="address-suggestions" value="{{ old('physical_address', $business->physical_address ?? '') }}">
                                <datalist id="address-suggestions"></datalist>
                                @error('physical_address') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group">
                                <label for="logo" class="form-label">Upload Your Logo</label>
                                <div class="file-upload">
                                    <input class="form-input file-input" type="file" name="logo" id="logo" accept="image/*" required>
                                    <div class="file-upload-content">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <p>Click to upload or drag and drop</p>
                                        <span>PNG, JPG up to 10MB</span>
                                    </div>
                                </div>
                                @if($business && $business->hasMedia('businesses'))
                                <div class="current-logo">
                                    <img src="{{ $business->getFirstMediaUrl('businesses', 'thumb') }}" alt="Current Logo">
                                    <p>Current logo</p>
                                </div>
                                @endif
                                @error('logo') <span class="error-text">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-actions-dual">
                                <button type="button" id="prevBtn" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back
                                </button>
                                <button class="btn btn-primary" type="button" onclick="register(this)">
                                    Complete Setup <i class="bi bi-check-circle"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        .setup-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            width: 100%;
        }

        .setup-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 1100px;
            width: 100%;
            min-height: 600px;
        }

        /* Left Visual Section */
        .setup-visual {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .setup-visual::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M20 50 Q50 20 80 50 T140 50' stroke='white' stroke-width='0.5' fill='none' opacity='0.1'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .visual-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2rem;
        }

        .visual-card {
            margin-bottom: 2rem;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .visual-card img {
            width: 100%;
            height: auto;
            display: block;
        }

        .visual-overlay {
            color: white;
        }

        .visual-overlay h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .visual-overlay p {
            font-size: 1rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        /* Right Form Section */
        .setup-form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #fafbfc;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo img {
            max-width: 50px;
            height: auto;
        }

        .header-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .header-content p {
            font-size: 0.85rem;
            color: #6b7280;
        }

        /* Progress Section */
        .progress-section {
            margin-bottom: 2rem;
        }

        .progress-track {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            width: 50%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            border-radius: 10px;
            transition: width 0.4s ease;
        }

        .step-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            color: #1f2937;
            font-family: inherit;
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        /* File Upload */
        .file-upload {
            position: relative;
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 2rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }

        .file-upload:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.02);
        }

        .file-input {
            display: none;
        }

        .file-upload-content {
            pointer-events: none;
        }

        .file-upload-content i {
            font-size: 2rem;
            color: #667eea;
            display: block;
            margin-bottom: 0.5rem;
        }

        .file-upload-content p {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .file-upload-content span {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .current-logo {
            margin-top: 1rem;
            text-align: center;
        }

        .current-logo img {
            max-width: 80px;
            max-height: 80px;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .current-logo p {
            font-size: 0.8rem;
            color: #6b7280;
        }

        /* Form Steps */
        .form-step {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Buttons */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .form-actions-dual {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f4ff;
        }

        /* Error Text */
        .error-text {
            display: block;
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.4rem;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }

        /* Phone Input */
        .iti {
            width: 100%;
        }

        .iti__country-name {
            display: inline-block !important;
            margin-left: 6px;
            color: #1f2937;
            font-weight: 400;
        }

        /* Mobile Responsiveness */
        @media (max-width: 968px) {
            .setup-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .setup-visual {
                min-height: 300px;
            }

            .visual-overlay h2 {
                font-size: 1.5rem;
            }

            .form-wrapper {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .setup-wrapper {
                padding: 0.5rem;
            }

            .setup-form-section {
                padding: 1rem;
            }

            .form-header {
                flex-direction: column;
                text-align: center;
            }

            .form-actions-dual {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .visual-overlay h2 {
                font-size: 1.2rem;
            }

            .visual-overlay p {
                font-size: 0.9rem;
            }
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
                    document.querySelector("#country").value = selectedCountryData.name;
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
                        let errorSpan = input.parentElement.querySelector(".error-text");
                        if (!errorSpan) {
                            errorSpan = document.createElement("span");
                            errorSpan.className = "error-text";
                            errorSpan.textContent =
                                `${input.name.charAt(0).toUpperCase() + input.name.slice(1)} is required.`;
                            input.parentElement.appendChild(errorSpan);
                        }
                    } else {
                        input.classList.remove("is-invalid");
                        const errorSpan = input.parentElement.querySelector(".error-text");
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

            // File upload drag and drop
            const fileUpload = document.querySelector('.file-upload');
            const fileInput = document.getElementById('logo');

            if (fileUpload && fileInput) {
                fileUpload.addEventListener('click', () => fileInput.click());

                fileUpload.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    fileUpload.style.borderColor = '#667eea';
                    fileUpload.style.background = 'rgba(102, 126, 234, 0.05)';
                });

                fileUpload.addEventListener('dragleave', () => {
                    fileUpload.style.borderColor = '#e5e7eb';
                    fileUpload.style.background = 'white';
                });

                fileUpload.addEventListener('drop', (e) => {
                    e.preventDefault();
                    fileUpload.style.borderColor = '#e5e7eb';
                    fileUpload.style.background = 'white';
                    fileInput.files = e.dataTransfer.files;
                });
            }
        });
    </script>
</x-setup-layout>

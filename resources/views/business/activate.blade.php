<x-app-layout>
    <div class="breadcrumb__area">
        <div class="breadcrumb__wrapper mb-25">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('business.index', $business->slug) }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $page }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <card class="mb-3">
                <div class="card-body">
                    <h2>{{ $page }}</h2>
                    <p>{{ $description }}</p>

                    @if($business->verified)
                    <div class="alert alert-success">
                        Your business is verified! <a href="{{ route('business.index', $business->slug) }}">Go to
                            Dashboard</a>.
                    </div>
                    @else
                    <div class="alert alert-danger">
                        Your business is pending activation. Please upload the required documents below and ensure all
                        details are correct.
                    </div>

                    <form id="activateBusinessForm" enctype="multipart/form-data">

                        <input type="text" value="{{ $business->slug }}" hidden name="business_slug" id="business_slug">

                        <card class="mb-3">
                            <div class="card-body">

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="name">Company / Organization name</label>
                                    </div>
                                    <div class="form__input">
                                        <input class="form-control" placeholder="Company / organization Name"
                                            name="name" id="name" value="{{ $business->company_name }}" type="text"
                                            required autocomplete="name">
                                    </div>
                                </div>

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="company_size">Company size</label>
                                    </div>
                                    <div class="form__input">
                                        <select id="company_size" name="company_size" required class="form-select">
                                            <option value="">Select Company Size</option>
                                            <option value="1-10"
                                                {{ $business->company_size == '1-10' ? 'selected' : '' }}>1-10
                                                employees</option>
                                            <option value="11-50"
                                                {{ $business->company_size == '11-50' ? 'selected' : '' }}>
                                                11-50 employees</option>
                                            <option value="51-200"
                                                {{ $business->company_size == '51-200' ? 'selected' : '' }}>
                                                51-200 employees</option>
                                            <option value="201-500"
                                                {{ $business->company_size == '201-500' ? 'selected' : '' }}>201-500
                                                employees
                                            </option>
                                            <option value="500+"
                                                {{ $business->company_size == '500+' ? 'selected' : '' }}>500+
                                                employees</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="industry">Industry</label>
                                    </div>
                                    <div class="form__input">
                                        <select id="industry" name="industry" required class="form-select">
                                            <option value="">Select Industry</option>
                                            @foreach($industries as $industry)
                                            <option value="{{ $industry->slug }}"
                                                {{ $industry->slug === $business->industry ? 'selected' : '' }}>
                                                {{ $industry->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="phone0">Contact phone</label>
                                    </div>
                                    <div class="form__input">
                                        <input class="phone-input-control" name="phone" id="phone0"
                                            value="{{ $business->phone }}" type="text" required autocomplete="phone">
                                        <input name="code" hidden id="code0" type="text" required
                                            value="{{ $business->code }}" autocomplete="code">
                                        <input name="country" hidden id="country0" type="text" required
                                            value="{{ $business->country }}" autocomplete="country">
                                    </div>
                                </div>

                                <div class="row mb-3">

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="registration_no">Registration No.</label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" placeholder="Company Registration No"
                                                    name="registration_no" id="registration_no"
                                                    value="{{ $business->registration_no }}" type="text" required
                                                    autocomplete="registration_no">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="registration_certificate">Upload registration certificate
                                                </label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" type="file" name="registration_certificate"
                                                    id="registration_certificate">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="business_license_no">Business License Number</label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" placeholder="Business License Number"
                                                    name="business_license_no" id="business_license_no"
                                                    value="{{ $business->business_license_no }}" type="text" required
                                                    autocomplete="business_license_no">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="business_license_certificate">Upload Business License
                                                    Certificate</label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" type="file"
                                                    name="business_license_certificate"
                                                    id="business_license_certificate">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="physical_address">Physical Address</label>
                                    </div>
                                    <div class="form__input">
                                        <input class="form-control" placeholder="Building, Street, Town"
                                            name="physical_address" id="physical_address"
                                            value="{{ $business->physical_address }}" type="text" required
                                            autocomplete="physical_address">
                                    </div>
                                </div>

                                <div class="row mb-3">

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="tax_pin_no">Tax Pin No.</label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" placeholder="Tax Pin No" name="tax_pin_no"
                                                    id="tax_pin_no" value="{{ $business->tax_pin_no }}" type="text"
                                                    required autocomplete="tax_pin_no">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="from__input-box">
                                            <div class="form__input-title">
                                                <label for="tax_pin_certificate">Tax Pin Certificate </label>
                                            </div>
                                            <div class="form__input">
                                                <input class="form-control" type="file" name="tax_pin_certificate"
                                                    id="tax_pin_certificate">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="from__input-box">
                                    <div class="form__input-title">
                                        <label for="logo">Upload your logo</label>
                                    </div>
                                    <div class="form__input">
                                        <input class="form-control" type="file" name="logo" id="logo">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <button class="btn btn-primary w-100" onclick="updateBusiness(this)" type="button">
                                        Complete
                                        Setup <i class="ms-2 bi bi-check-circle"></i> </button>
                                </div>

                            </div>
                        </card>

                    </form>
                    @endif
                </div>
            </card>
        </div>
        <div class="col-md-3">
            <p>Business Details</p>
            <div class="card mb-3">
                <div class="card-body text-center">
                    <h6>Verification Status</h6>
                    <i id="verificationIcon"
                        class="bi {{ $business->verified ? 'bi-check-circle text-success' : 'bi-x-circle text-danger' }}"
                        title="{{ $business->verified ? 'Verified' : 'Not Verified' }}"></i>
                    <hr>
                    <h6>Business Logo</h6>
                    <div style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $business->getImageUrl() }}" alt="{{ $business->company_name }}" class="img-fluid"
                            style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                </div>
            </div>

            <!-- Registration Certificate -->
            <p>Registration Certificate</p>
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    @if($business->getFirstMediaUrl('registration_certificates'))
                    <a id="registrationCertificateLink"
                        href="{{ $business->getFirstMediaUrl('registration_certificates') }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center" target="_blank" download>
                        <i class="bi bi-file-earmark-text-fill me-2 fs-5"></i>
                        Registration Certificate
                    </a>
                    @else
                    <div class="text-muted">No registration certificate uploaded.</div>
                    @endif
                </div>
            </div>

            <!-- Tax Pin Certificate -->
            <p>Tax Pin Certificate</p>
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    @if($business->getFirstMediaUrl('tax_pin_certificates'))
                    <a id="taxPinCertificateLink" href="{{ $business->getFirstMediaUrl('tax_pin_certificates') }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center" target="_blank" download>
                        <i class="bi bi-file-earmark-text-fill me-2 fs-5"></i>
                        Tax Pin Certificate
                    </a>
                    @else
                    <div class="text-muted">No tax pin certificate uploaded.</div>
                    @endif
                </div>
            </div>

            <!-- Business License Certificate -->
            <p>Business License Certificate</p>
            <div class="card mb-3 shadow-sm">
                <div class="card-body text-center">
                    @if($business->getFirstMediaUrl('business_license_certificates'))
                    <a id="businessLicenseCertificateLink"
                        href="{{ $business->getFirstMediaUrl('business_license_certificates') }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center" target="_blank" download>
                        <i class="bi bi-file-earmark-text-fill me-2 fs-5"></i>
                        Business License Certificate
                    </a>
                    @else
                    <div class="text-muted">No business license certificate uploaded.</div>
                    @endif
                </div>
            </div>

            @if($business->verified && $business->slug === 'krest')
            <!-- API Token -->
            <p>API Token</p>
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <a href="{{ route('business.api-token', $business->slug) }}"
                        class="btn btn-outline-primary d-inline-flex align-items-center">
                        <i class="bi bi-key me-2 fs-5"></i>
                        Manage API Token
                    </a>
                </div>
            </div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('/js/main/businesses.js') }}" type="module"></script>
    @endpush
</x-app-layout>

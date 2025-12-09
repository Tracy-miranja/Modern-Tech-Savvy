<x-auth-layout>
    <div class="container-xxl">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-12 mx-auto">
                <div class="authentication-top mb-20 text-center">
                    <a href="javascript:;" class="authentication-logo logo-black">
                        <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }} - Logo">
                    </a>
                    <a href="javascript:;" class="authentication-logo logo-white">
                        <img src="{{ asset('media/krstlogo.png') }}" alt="{{ config('app.name') }} - Logo">
                    </a>
                    <h4 class="mb-15">Choose Your Modules - Pick at least one</h4>
                    <p class="mb-15">
                        Begin by selecting the modules that best suit your organization’s needs. Personalize the
                        platform
                        and maximize its capabilities.
                    </p>
                </div>

                <form id="modulesForm" enctype="multipart/form-data">
                    @csrf
                    <input hidden type="text" value="{{ $business->slug }}" id="business_slug" name="business_slug">

                    <div class="row g-2 p-3" style="height: 60vh; overflow-y: auto;">
                        @foreach ($modules as $module)
                        <div class="col-md-4">
                            <div class="card h-100 module-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon bg-primary text-white me-3 p-1 rounded-2">
                                            <i class="bi bi-{{ $module->icon }}" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-0">{{ $module->name }}</h5>
                                    </div>
                                    <p class="card-text mb-3">{{ $module->description }}</p>
                                    <ul class="list-unstyled mb-3">
                                        @foreach ($module->features as $feature)
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-auto">
                                        @if ($module->price_monthly > 0)
                                        <p class="text-muted mb-2">
                                            <strong>Monthly:</strong> ${{ number_format($module->price_monthly, 2) }}
                                        </p>
                                        <p class="text-muted mb-3">
                                            <strong>Yearly:</strong> ${{ number_format($module->price_yearly, 2) }}
                                        </p>
                                        @else
                                        <p class="text-success mb-3">Included in Core Features</p>
                                        @endif
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                id="module-{{ $module->slug }}" name="modules[]"
                                                value="{{ $module->slug }}" @if ($module->is_core) checked
                                            disabled @endif>
                                            <label class="form-check-label" for="module-{{ $module->slug }}">
                                                Enable {{ $module->name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="row g-2">
                        <div class="col-md-10">
                            <button type="button" id="saveModulesBtn" class="btn btn-primary px-5 w-100">
                                <i class="bi bi-check-circle"></i> Activate Selected Modules
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" onclick="logout(this)" class="btn btn-danger px-5 w-100">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/businesses.js') }}" type="module"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("modulesForm");
            const saveButton = document.getElementById("saveModulesBtn");

            saveButton.addEventListener("click", async function(e) {
                const checkboxes = form.querySelectorAll(
                    'input[name="modules[]"]:checked:not([disabled])');
                if (checkboxes.length === 0) {
                    await Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Please select at least one non-core module to continue.",
                        confirmButtonText: "OK",
                    });
                    return;
                }
                console.log("Triggering module setup submission");
                saveModules(saveButton);
            });

            // Disable core module checkboxes visually
            const coreCheckboxes = form.querySelectorAll('input[name="modules[]"][disabled]');
            coreCheckboxes.forEach(checkbox => {
                checkbox.parentElement.classList.add('text-muted');
                checkbox.parentElement.title = "This module is included by default.";
            });
        });
    </script>
    @endpush
</x-auth-layout>

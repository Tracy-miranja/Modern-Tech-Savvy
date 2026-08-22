<x-app-layout>
    @include('components.phone-input-assets')

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center mb-0">
            <h5 class="mb-0"> <i class="fa-solid fa-tools me-2"></i> Account Settings</h5>
        </div>
    </div>

    <div class="py-4">
        <div class="row">
            <div class="col-md-7">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="col-md-7">
                @include('profile.partials.update-password-form')
            </div>
            <div class="col-md-7">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>


    @push('scripts')
        <script src="{{ asset('js/main/profile.js') }}" type="module"></script>
    @endpush
</x-app-layout>

<x-app-layout>
    @include('components.phone-input-assets')

    <div class="card">
        <div class="card-body" id="applicantFormContainer">
            @include('applicants._form');
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/job-applicants.js') }}" type="module"></script>

    <script>
    window.businessSlug = '{{ $currentBusiness->slug }}';
    </script>
    @endpush

</x-app-layout>
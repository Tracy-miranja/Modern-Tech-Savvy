<x-app-layout title="Create Job Post">
    <div class="row g-3">
        <div class="col-12">
            <h5 class="mb-4">Create Job Post</h5>
            <div id="jobPostFormContainer">
                @include('job-posts._form')
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    window.businessSlug = "{{ $currentBusiness->slug }}";
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1/tinymce.min.js"></script>
    <script src="{{ asset('js/main/job-posts.js') }}" type="module"></script>
    @endpush
</x-app-layout>
<x-app-layout title="Job Posts">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between text-white">
                    <h5 class="mb-0">Job Posts</h5>
                    <a href="{{ route('business.recruitment.jobs.create', $currentBusiness->slug) }}"
                        class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-square-dotted me-2"></i> Add Job Post
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="jobFilter" class="form-control"
                            placeholder="Filter by title, type, or location...">
                    </div>
                    <div id="jobPostsContainer">
                        {{ loader() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.6.1/tinymce.min.js"></script>
    <script src="{{ asset('js/main/job-posts.js') }}" type="module"></script>
    <script>
        $(document).ready(() => getJobPosts());
    </script>
    @endpush
</x-app-layout>
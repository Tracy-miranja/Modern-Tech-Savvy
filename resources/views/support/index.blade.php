<x-app-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Help & Support</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Contact Information</h4>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Email:</strong> support@krest.com</p>
                                        <p><strong>Phone:</strong> +254 711 903 289</p>
                                        <p><strong>Address:</strong>Nairobi, Kenya</p>
                                        <p><strong>Support Hours:</strong> Mon-Fri, 9AM-5PM</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Submit a Support Issue</h4>
                                    </div>
                                    <div class="card-body">
                                        <form id="supportForm" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-group">
                                                <label for="title">Issue Title</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    required>
                                            </div>
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control" id="description" name="description"
                                                    rows="5" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="screenshot">Attach Screenshot</label>
                                                <input type="file" class="form-control-file" id="screenshot"
                                                    name="screenshot" accept="image/*">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Submit Issue</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Your Support Issues</h4>
                                    </div>
                                    <div class="card-body">
                                        <table id="issuesTable" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                    <th>Screenshot</th>
                                                    <th>Solved By</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
    <script>
    window.currentBusinessSlug = '{{ $currentBusiness->slug }}';
    </script>
    <script src="{{ asset('js/main/support.js') }}"></script>
    @endsection
</x-app-layout>

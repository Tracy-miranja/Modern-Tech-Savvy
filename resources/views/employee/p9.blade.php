<x-app-layout title="Download P9 Form">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <h2 class="fw-bold text-dark mb-4">My P9 Forms</h2>
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="p9FormsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Year</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($years as $year)
                                    <tr>
                                        <td>{{ $year }}</td>
                                        <td>
                                            <a href="{{ route('myaccount.p9', ['business' => $business->slug, 'year' => $year]) }}"
                                                class="btn btn-sm btn-primary" title="Download P9">
                                                <i class="fa fa-download"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No P9 forms available yet - a year needs at least one closed payroll run first.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        $('#p9FormsTable').DataTable({
            responsive: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            paging: true,
            language: { search: "Filter:" },
            order: [[0, 'desc']],
        });
    });
    </script>
    @endpush
</x-app-layout>
<x-app-layout :title="$page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <h2 class="fw-bold text-dark mb-4">{{ $page }}</h2>
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="myAssetsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Asset</th>
                                        <th>Tag</th>
                                        <th>Category</th>
                                        <th>Serial Number</th>
                                        <th>Condition</th>
                                        <th>Assigned On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $assignment)
                                    <tr>
                                        <td>{{ $assignment->asset->name ?? 'Unknown asset' }}</td>
                                        <td>{{ $assignment->asset->asset_tag ?? '-' }}</td>
                                        <td>{{ $assignment->asset->category ?? '-' }}</td>
                                        <td>{{ $assignment->asset->serial_number ?? '-' }}</td>
                                        <td>{{ $assignment->asset->condition ? ucfirst($assignment->asset->condition) : '-' }}</td>
                                        <td>{{ $assignment->assigned_at?->format('d M Y') ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">You have no assets currently assigned to you.</td>
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
        $('#myAssetsTable').DataTable({
            responsive: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            paging: true,
            language: { search: "Filter:" },
            order: [[5, 'desc']],
        });
    });
    </script>
    @endpush
</x-app-layout>

<x-app-layout :title="$page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <h2 class="fw-bold text-dark mb-4">{{ $page }}</h2>
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="payslipsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Year</th>
                                        <th>Month</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payslips as $payslip)
                                    <tr>
                                        <td>{{ $payslip['year'] }}</td>
                                        <td>{{ $payslip['month_name'] }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $payslip['status'] === 'closed' ? 'bg-success' : 'bg-warning' }}">
                                                {{ ucfirst($payslip['status']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($payslip['status'] === 'closed')
                                            <a href="{{ route('myaccount.payslips.download', ['business' => $business->slug, 'id' => $payslip['payroll_id']]) }}"
                                                class="btn btn-sm btn-primary" title="Download Payslip">
                                                <i class="fa fa-download"></i> Download
                                            </a>
                                            @else
                                            <span class="text-muted">Not Available</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No payslips found.</td>
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
        $('#payslipsTable').DataTable({
            responsive: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            paging: true,
            language: {
                search: "Filter:"
            },
            order: [
                [0, 'desc'],
                [1, 'desc']
            ]
        });
    });
    </script>
    @endpush
</x-app-layout>

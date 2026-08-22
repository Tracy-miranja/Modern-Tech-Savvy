<x-app-layout :title="$page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <h2 class="fw-bold text-dark mb-4">{{ $page }}</h2>
                <div class="card shadow-sm border-0 rounded-3 bg-white">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="myLearningTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>Category</th>
                                        <th>Provider</th>
                                        <th>Session</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Certificate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($enrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->course->title ?? 'Unknown course' }}</td>
                                        <td>{{ $enrollment->course->category->name ?? '-' }}</td>
                                        <td>{{ $enrollment->course->provider ?? '-' }}</td>
                                        <td>
                                            @if ($enrollment->session)
                                                {{ $enrollment->session->start_date?->format('d M Y') }}
                                                @if ($enrollment->session->location)
                                                    <span class="text-muted small">· {{ $enrollment->session->location }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'enrolled' => 'bg-info-subtle text-info',
                                                    'in_progress' => 'bg-warning-subtle text-warning',
                                                    'completed' => 'bg-success-subtle text-success',
                                                    'dropped' => 'bg-secondary-subtle text-secondary',
                                                ];
                                            @endphp
                                            <span class="badge {{ $statusColors[$enrollment->status] ?? 'bg-secondary-subtle text-secondary' }}">
                                                {{ ucwords(str_replace('_', ' ', $enrollment->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $enrollment->score !== null ? $enrollment->score : '-' }}</td>
                                        <td>
                                            @if ($enrollment->certificate_issued)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="bi bi-award-fill me-1"></i>{{ $enrollment->certificate_number ?? 'Issued' }}
                                                </span>
                                                @if ($enrollment->certificate_expiry_date)
                                                    <div class="text-muted small">Expires {{ $enrollment->certificate_expiry_date->format('d M Y') }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">You have no courses or training assigned to you yet.</td>
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
        $('#myLearningTable').DataTable({
            responsive: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            paging: true,
            language: { search: "Filter:" },
        });
    });
    </script>
    @endpush
</x-app-layout>

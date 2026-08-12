<x-app-layout>
    <div class="row g-20">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">My Team</h5>

                    @if ($directReports->isEmpty())
                        <p class="text-muted">You don't have any direct reports.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Employee No</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($directReports as $report)
                                        <tr>
                                            <td>{{ $report->employee_code }}</td>
                                            <td>{{ $report->user->name ?? 'N/A' }}</td>
                                            <td>{{ $report->department->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">
                            You can approve leave requests from anyone on this list from the Leave Requests tab.
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

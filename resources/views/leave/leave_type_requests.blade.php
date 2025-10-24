<x-app-layout>
    <div class="row mb-3">
        <h2>Leave Requests for: {{ $leaveType->name }}</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="leaveTypeRequestsTable">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Employee</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // simple in-request cache to avoid repeated role queries
                            static $roleCache = [];
                            $getUserPrimaryRole = function($userId) use (&$roleCache) {
                                if (!$userId) return null;
                                if (array_key_exists($userId, $roleCache)) return $roleCache[$userId];
                                $u = \App\Models\User::with('roles')->find($userId);
                                $roleCache[$userId] = $u?->roles?->pluck('name')?->first();
                                return $roleCache[$userId];
                            };
                        @endphp

                        @foreach ($leaveType->leaveRequests as $req)
                            @php
                                $history = is_array($req->approval_history ?? null) ? $req->approval_history : [];
                                $lastApproval = !empty($history) ? $history[array_key_last($history)] : null;
                                $lastApproverName = $lastApproval['approver_name'] ?? null;
                                $lastApproverId   = $lastApproval['approver_id'] ?? null;

                                // Prefer role saved in history; else look up approver's current primary role
                                $lastApproverRole = $lastApproval['approver_role'] ?? $getUserPrimaryRole($lastApproverId);

                                $requiredLevels = (int) (optional($req->leaveType)->approval_levels ?? 1);
                                $currentLevel   = (int) ($req->current_approval_level ?? 0);
                                $isFinalApproved = !is_null($req->approved_by) && is_null($req->rejection_reason);
                                $isRejected = !is_null($req->rejection_reason) && is_null($req->approved_by);
                                $isPending  = is_null($req->approved_by) && is_null($req->rejection_reason);
                                $awaitingNext = $isPending && $currentLevel > 0 && $currentLevel < $requiredLevels;
                            @endphp

                            <tr>
                                <td>{{ $req->reference_number }}</td>
                                <td>{{ optional(optional($req->employee)->user)->name ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->start_date)->format('Y-m-d') }}</td>
                                <td>{{ \Carbon\Carbon::parse($req->end_date)->format('Y-m-d') }}</td>
                                <td>{{ $req->total_days }}</td>
                                <td>
                                    @if ($isFinalApproved)
                                        <span class="badge bg-success">Approved</span>
                                        @if($lastApproverName || $lastApproverRole)
                                            <div class="small text-muted mt-1">
                                                Final approval by
                                                @if($lastApproverRole)<strong>{{ ucfirst(str_replace('-', ' ', $lastApproverRole)) }}</strong>@endif
                                                @if($lastApproverName) ({{ $lastApproverName }}) @endif
                                            </div>
                                        @endif
                                    @elseif ($isPending)
                                        @if ($awaitingNext)
                                            <span class="badge bg-info text-dark">In Progress</span>
                                            <div class="small text-muted mt-1">
                                                Approved by
                                                @if($lastApproverRole)
                                                    <strong>{{ ucfirst(str_replace('-', ' ', $lastApproverRole)) }}</strong>
                                                @else
                                                    an approver
                                                @endif
                                                @if($lastApproverName) ({{ $lastApproverName }}) @endif
                                                — awaiting next approval (level {{ $currentLevel + 1 }} of {{ $requiredLevels }})
                                            </div>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                            <div class="small text-muted mt-1">
                                                Awaiting first approval (level 1 of {{ $requiredLevels }})
                                            </div>
                                        @endif
                                    @elseif ($isRejected)
                                        <span class="badge bg-danger">Rejected</span>
                                        @if($req->rejection_reason)
                                            <div class="small text-muted mt-1">Reason: {{ $req->rejection_reason }}</div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </td>
                                <td>{{ $req->reason ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(function () {
            $('#leaveTypeRequestsTable').DataTable({
                responsive: true,
                autoWidth: false
            });
        });
    </script>
    @endpush
</x-app-layout>

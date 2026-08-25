@php

    $lt = $leaveType;
    $ex = is_array($lt->excluded_days) ? $lt->excluded_days : [];

    $updatedAt = $lt->updated_at ?? null;
    $isFresh = false;
    $updatedText = '';
    if ($updatedAt) {
        $diffSeconds = now()->diffInSeconds($updatedAt);
        $isFresh = $diffSeconds <= 120;
        $updatedText = $updatedAt->format('Y-m-d H:i');
    }
@endphp

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">{{ $lt->name }}</h5>

        @if($updatedAt)
            @if($isFresh)
                <span class="badge bg-success-subtle text-success border border-success">
                    Updated just now
                </span>
            @else
                <span class="badge bg-light text-muted border">
                    Updated: {{ $updatedText }}
                </span>
            @endif
        @endif
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-2">General</h6>
                    <dl class="row mb-0">
                        <dt class="col-6">Description</dt>
                        <dd class="col-6">{{ $lt->description ?: '—' }}</dd>

                        <dt class="col-6">Requires Approval</dt>
                        <dd class="col-6">{{ $lt->requires_approval ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Is Paid</dt>
                        <dd class="col-6">{{ $lt->is_paid ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Allowance Accruable</dt>
                        <dd class="col-6">{{ $lt->allowance_accruable ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Allows Half Day</dt>
                        <dd class="col-6">{{ $lt->allows_half_day ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Requires Attachment</dt>
                        <dd class="col-6">{{ $lt->requires_attachment ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Max Continuous Days</dt>
                        <dd class="col-6">{{ $lt->max_continuous_days ?? '—' }}</dd>

                        <dt class="col-6">Min Notice Days</dt>
                        <dd class="col-6">{{ $lt->min_notice_days ?? '—' }}</dd>

                        <dt class="col-6">Excluded Days</dt>
                        <dd class="col-6">
                            {{ empty($ex) ? '—' : implode(', ', array_map('ucfirst',$ex)) }}
                        </dd>

                        <dt class="col-6">Allows Backdating</dt>
                        <dd class="col-6">{{ $lt->allows_backdating ? 'Yes' : 'No' }}</dd>

                        <dt class="col-6">Approval Levels</dt>
                        <dd class="col-6">{{ $lt->approval_levels ?? 0 }}</dd>

                        <dt class="col-6">Stepwise</dt>
                        <dd class="col-6">{{ $lt->is_stepwise ? 'Yes' : 'No' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="mb-2">Policies</h6>
                    @if($lt->leavePolicies->isEmpty())
                        <p class="text-muted mb-0">No policies defined.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Job Category</th>
                                        <th>Gender</th>
                                        <th>Default Days</th>
                                        <th>Accrual</th>
                                        <th>Carryover</th>
                                        <th>Effective</th>
                                        <th>End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($lt->leavePolicies as $p)
                                    <tr>
                                        <td>{{ $p->department->name ?? 'All' }}</td>
                                        <td>{{ $p->jobCategory->name ?? 'All' }}</td>
                                        <td>{{ ucfirst($p->gender_applicable ?? 'all') }}</td>
                                        <td>{{ $p->default_days }}</td>
                                        <td>{{ $p->accrual_frequency }} ({{ $p->accrual_amount }})</td>
                                        <td>{{ $p->max_carryover_days }}</td>
                                        <td>{{ optional($p->effective_date)->format('Y-m-d') }}</td>
                                        <td>{{ optional($p->end_date)->format('Y-m-d') ?: '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

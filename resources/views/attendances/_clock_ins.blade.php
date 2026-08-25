
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0">
            <div class="card-body">
                <h6 class="card-title mb-3 text-muted">
                    <i class="bi bi-info-circle me-2"></i>Status Guide
                </h6>
                <div class="row g-2">
                    @php
                    $statuses = [
                    ['Absent', 'danger'],
                    ['Present', 'info'],
                    ['Clocked In', 'warning'],
                    ['Clocked Out', 'success'],
                    ];
@endphp
                    @foreach ($statuses as [$label, $color])
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center">
                            <div class="border-start border-{{ $color }} border-4 me-2"
                                style="height: 20px; width: 4px;"></div>
                            <small class="text-{{ $color }} fw-medium">{{ $label }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-3">
    @foreach ($clockins as $clockin)
    @php
    $employee = $clockin->employee ?? null;
    $user = $employee?->user ?? null;
    $imageUrl = $employee?->getFirstMediaUrl('avatars');

    $status = match (true) {
    $clockin->is_absent => ['Absent', 'danger', 'bi-x-circle'],
    $clockin->clock_out => ['Clocked Out', 'success', 'bi-check-circle'],
    $clockin->clock_in => ['Clocked In', 'warning', 'bi-clock'],
    default => ['Present', 'info', 'bi-person-check'],
    };

    [$statusText, $statusColor, $statusIcon] = $status;
@endphp

    <div class="col">
        <div class="card shadow-sm border-start border-4 border-{{ $statusColor }} h-100">
            <div class="card-body d-flex flex-column p-3">

                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0 me-3">
                        @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="User Avatar" class="rounded-circle border object-fit-cover"
                            style="width: 48px; height: 48px;">
                        @else
                        <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center"
                            style="width: 48px; height: 48px; font-size: 18px;">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                        </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-semibold lh-sm text-wrap break-word" style="white-space: normal;">
                            {{ $user?->name ?? 'Unknown' }}
                        </h6>
                        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} fs-7">
                            <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                        </span>
                    </div>
                </div>

                <div class="mb-3 flex-grow-1">
                    <div class="small">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Clock In:</span>
                            <span>
                                @if ($clockin->is_absent)
                                <span class="text-danger">Absent</span>
                                @else
                                {{ $clockin->clock_in ? \Carbon\Carbon::parse($clockin->clock_in)->format('H:i') : '-' }}
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Clock Out:</span>
                            <span>{{ $clockin->clock_out ? \Carbon\Carbon::parse($clockin->clock_out)->format('H:i') : '-' }}</span>
                        </div>
                        @if ($clockin->clock_in && $clockin->clock_out)
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Duration:</span>
                            <span class="text-success">
                                @php
                                $duration =
                                \Carbon\Carbon::parse($clockin->clock_in)->diffInMinutes(\Carbon\Carbon::parse($clockin->clock_out));
                                $hours = floor($duration / 60);
                                $minutes = $duration % 60;
@endphp
                                {{ $hours }}h {{ $minutes }}m
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mt-auto">
                    @if ($clockin->clock_out)
                    <div class="d-flex justify-content-center text-success small">
                        <i class="bi bi-check-circle me-1"></i><span>Completed</span>
                    </div>
                    @elseif ($employee && (!auth()->user()->hasRole('business-employee') || (auth()->user()->employee &&
                    $clockin->employee_id === auth()->user()->employee->id)))
                    <button type="button" onclick="clockOut(this)" data-employee="{{ $employee->id }}"
                        class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center">
                        <i class="bi bi-box-arrow-right me-2"></i><span>Clock Out</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<style>
.card {
    min-width: 0;
    max-width: 100%;
    word-wrap: break-word;
}

.card-body .d-flex {
    flex-wrap: wrap;
}

.flex-grow-1 {
    min-width: 0;
}

.card h6,
.card .badge,
.card small {
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}

.object-fit-cover {
    object-fit: cover;
}
</style>
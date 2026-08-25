<table class="table table-striped table-nowrap mb-0" id="attendancesTable">
    <thead>
        <tr>
            <th>Employee</th>
            @for ($day = 1; $day <= $daysInMonth; $day++) <th>{{ $day }}</th>
                @endfor
        </tr>
    </thead>
    <tbody class="table__body">
        @foreach($attendanceData as $employeeId => $employeeAttendances)

        @php
        $employeeAttendancesCollection = collect($employeeAttendances);
@endphp
        <tr>
            @php
            $employeeModel = $employeeAttendancesCollection->first()?->employee;
            $user = $employeeModel?->user;
            $imageUrl = $employeeModel?->getFirstMediaUrl('avatars');
            $userName = $user?->name ?? 'Unknown';
            $initial = strtoupper(substr($userName, 0, 1));
@endphp
            <td class="align-middle">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                        @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="Avatar" class="rounded-circle border object-fit-cover"
                            style="width: 36px; height: 36px;">
                        @else
                        <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center"
                            style="width: 36px; height: 36px; font-size: 14px;">
                            {{ $initial }}
                        </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <span class="fw-medium">{{ $userName }}</span>
                    </div>
                </div>
            </td>

            @for ($day = 1; $day <= $daysInMonth; $day++) <td>
                @if (isset($employeeAttendances[$day]))
                @if ($employeeAttendances[$day]->is_on_leave)
                <i class="fa fa-plane text-info" title="On Leave"></i>
                @elseif ($employeeAttendances[$day]->is_absent)
                <i class="fa fa-times text-danger"></i>
                @else
                <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#attendance_info">
                    <i class="fa fa-check text-success"></i>
                </a>
                @endif
                @else
                -
                @endif
                </td>
                @endfor
        </tr>
        @endforeach
    </tbody>
</table>
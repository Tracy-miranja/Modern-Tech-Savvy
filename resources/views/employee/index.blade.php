<x-app-layout>
    <div class="container py-4">
        <h2 class="fw-bold mb-4">Welcome {{ explode(' ', Auth::user()->name)[0] }}</h2>

        <!-- Stats Overview -->
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Total Leaves</h5>
                    <h4 class="fw-bold text-dark">{{ $leave_count ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                    <i class="bi bi-clock text-success" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Days Worked</h5>
                    <h4 class="fw-bold text-dark">{{ $work_days ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                    <i class="bi bi-calendar-check text-warning" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Pending Leaves</h5>
                    <h4 class="fw-bold text-dark">{{ $pending_leaves ?? 0 }}</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 text-center">
                    <i class="bi bi-receipt text-info" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Payslips</h5>
                    <h4 class="fw-bold text-dark">{{ $payslips ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <!-- Leave Balance -->
<div class="row g-4 mt-4">
    <h3 class="fw-bold mb-3">Leave Balance</h3>
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-3 p-3">
            <div class="form-group mb-3">
                <label for="leaveTypeSelect" class="fw-semibold">Select Leave Type</label>
                <select id="leaveTypeSelect" class="form-select">
                    <option value="">-- Select Leave Type --</option>
                   @foreach(($leave_balances ?? []) as $index => $balance)
                        <option value="{{ $index }}">{{ $balance['leave_type'] }}</option>
                    @endforeach
                </select>
            </div>

            <div id="leaveBalanceDetails" class="mt-3 d-none">
                <h6 class="fw-bold" id="leaveTypeName"></h6>
                <p>Total Days: <span id="entitledDays"></span></p>
                <p>Days Taken: <span id="daysTaken"></span></p>
                <p>Days Remaining: <span id="daysRemaining"></span></p>
            </div>
        </div>
    </div>
</div>


        <!-- Quick Actions -->
        <div class="row g-4 mt-4">
            <h3 class="fw-bold mb-4">Quick Actions</h3>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <i class="bi bi-person-circle text-primary" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-2">Profile</h6>
                        <a href="{{ route('myaccount.profile', $currentBusiness->slug) }}"
                            class="btn btn-outline-primary btn-sm mt-3">View
                            Profile</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-warning" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-2">Leave Requests</h6>
                        <a href="{{ route('myaccount.leave.requests.create', $currentBusiness->slug) }}"
                            class="btn btn-outline-warning btn-sm mt-3">Request
                            Leave</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center">
                        <i class="bi bi-clock text-success" style="font-size: 2.5rem;"></i>
                        <h6 class="mt-2">Sign-in</h6>
                        <a href="{{ route('myaccount.attendances.clock-in-out.index', $currentBusiness->slug) }}"
                            class="btn btn-outline-success btn-sm mt-3">Check In / Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const leaveBalances = @json($leave_balances);
    const select = document.getElementById('leaveTypeSelect');
    const details = document.getElementById('leaveBalanceDetails');
    const typeName = document.getElementById('leaveTypeName');
    const entitled = document.getElementById('entitledDays');
    const taken = document.getElementById('daysTaken');
    const remaining = document.getElementById('daysRemaining');

    select.addEventListener('change', function () {
        const index = this.value;
        if (index !== '') {
            const balance = leaveBalances[index];
            typeName.textContent = balance.leave_type;
            entitled.textContent = balance.total_days;
            taken.textContent = balance.days_taken;
            remaining.textContent = balance.days_remaining;
            details.classList.remove('d-none');
        } else {
            details.classList.add('d-none');
        }
    });
});
</script>


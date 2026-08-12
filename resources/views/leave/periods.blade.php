<x-app-layout>
    <div class="row g-20">

        <div class="col-md-4">
            <div class="card">
                <div class="card-body" id="leavePeriodsFormContainer">
                    @include('leave._leave_period_form')
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-2">Process Carryover</h6>
                    <p class="text-muted small">Rolls each employee's unused balance from one period into the next, capped by that leave type's policy. Only updates entitlements that already exist in the destination period.</p>
                    <form id="processCarryoverForm">
                        <div class="mb-2">
                            <label class="form-label">From Period</label>
                            <select name="from_period_id" class="form-select" required>
                                <option value="">Select period</option>
                                @foreach ($leavePeriods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Period</label>
                            <select name="to_period_id" class="form-select" required>
                                <option value="">Select period</option>
                                @foreach ($leavePeriods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" onclick="processCarryover(this)" class="btn btn-outline-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Process Carryover
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div id="leavePeriodsContainer">
                <div class="card">
                    <div class="card-body"> {{ loader() }} </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        @include('modals.leave-periods')
        <script src="{{ asset('js/main/leave-periods.js') }}" type="module"></script>
        <script>
            $(document).ready(() => {
                getLeavePeriods()
            })
        </script>

    @endpush

</x-app-layout>

<x-app-layout>

    <div class="row g-20">

        <div class="col-md-">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>{{ $page }}</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="row">
                                <div class="col-md-7">
                                    <form action="">
                                        <select id="work_month" name="work_month" class="form-select">
                                            @foreach(range(1, 12) as $month)
                                                @php
                                                    $isDisabled = now()->year == request('payrun_year', now()->year) && $month > now()->month;
@endphp
                                                <option value="{{ $month }}" {{ now()->month == $month ? 'selected' : '' }} {{ $isDisabled ? 'disabled' : '' }}>
                                                    {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="col-md-5">
                                    <a class="btn btn-primary btn-sm" href="{{ route('business.attendances.clock-in', $currentBusiness->slug) }}"> <i class="bi bi-calendar-check me-2"></i> Record Attendances</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-body">
                    <div class="row g-20 gy-20 mb-20 justify-content-between align-items-end">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center">
                                <h6 class="">Note:</h6>
                                <div class="attendant__info-wrapper">
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-star text-theme"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Holiday</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-calendar-week text-secondary"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Day Off</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-check text-success"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Present</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-star-half-alt text-info"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Half Day</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-exclamation-circle text-warning"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Late</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-times text-danger"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">Absent</h6>
                                    </div>
                                    <div class="attendant__info-icon">
                                        <i class="fa fa-plane-departure text-link"></i>
                                        <span class="attachment__info-arrow"><i
                                                class="fa fa-arrow-right text-lightest"></i></span>
                                        <h6 class="text-dark small">On Leave</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="attendancesContainer">
                        {{ loader() }}
                    </div>

                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script src="{{ asset('js/main/attendances.js') }}" type="module"></script>
        <script>
            $(document).ready(() => {
                let month = $('#work_month').val();
                getMonthly(month);

                $('#work_month').on('change', function () {
                    let selectedMonth = $(this).val();
                    getMonthly(selectedMonth);
                });
            });
        </script>
    @endpush

</x-app-layout>

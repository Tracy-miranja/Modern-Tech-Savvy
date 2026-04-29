<x-app-layout>
    <style>
    .clock-display {
        font-size: 2rem;
        font-weight: bold;
        color: #fff;
        background: linear-gradient(45deg, #007bff, #00d4ff);
        padding: 10px 20px;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        text-align: center;
        width: fit-content;
        transition: all 0.3s ease-in-out;
        margin-bottom: 30px;
    }

    .clock-display:hover {
        transform: scale(1.05);
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.3);
    }
    </style>
    <div class="row g-20">
        <!-- Page Title with Clock -->
        <div class="col-12 d-flex align-items-center justify-content-center mb-5">
            <div id="currentClock" class="clock-display"></div>
        </div>
        <div class="col-md-5">
            <div class="card__wrapper">
                <div class="employee__wrapper text-center">
                    <div class="employee__thumb_2 mb-15 overflow-hidden">
                        @php
                        $employee = auth()->user()->employee;
                        $imageUrl = $employee?->getFirstMediaUrl('avatars');
                        @endphp

                        @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="User {{ auth()->user()->name }}"
                            class="rounded-circle border object-fit-cover" style="width: 80px; height: 80px;">
                        @else
                        <div class="user__initials d-flex align-items-center justify-content-center rounded-circle border bg-secondary text-white"
                            style="width: 80px; height: 80px; font-size: 32px;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        @endif
                    </div>
                    <div class="employee__content">
                        <div class="employee__meta mb-15">
                            <h4 class="mb-8"><a href="">{{ auth()->user()->name }}</a></h4>
                            <p>{{ formatStatus(session('active_role')) }}</p>
                        </div>
                        <div class="employee__btn">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <button type="button" class="btn w-100 btn-primary" onclick="clockIn(this)"
                                        data-employee="{{ auth()->user()->employee->id }}">Check In</button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn w-100 btn-danger" onclick="clockOut(this)"
                                        data-employee="{{ auth()->user()->employee->id }}">Check Out</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7" id="clockinsContainer">
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/attendances.js') }}" type="module"></script>
    <script>
    $(document).ready(() => {
        function updateClock() {
            let now = new Date();
            let options = {
                weekday: 'long'
            };
            let dayString = now.toLocaleDateString(undefined, options);
            let day = now.getDate();
            let suffix = getOrdinalSuffix(day);
            let timeString = now.toLocaleTimeString();
            document.getElementById('currentClock').textContent = `${dayString}, ${day}${suffix} ${timeString}`;
        }

        function getOrdinalSuffix(day) {
            if (day >= 11 && day <= 13) return "th";
            switch (day % 10) {
                case 1:
                    return "st";
                case 2:
                    return "nd";
                case 3:
                    return "rd";
                default:
                    return "th";
            }
        }

        setInterval(updateClock, 1000);
        updateClock();
        getClockins();
    });
    </script>
    @endpush
</x-app-layout>

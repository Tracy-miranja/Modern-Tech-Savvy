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

        <div class="col-12 d-flex align-items-center justify-content-center mb-5">
            <div id="currentClock" class="clock-display"></div>
        </div>

        <div class="col-md-5 mb-5">
            <div class="card rounded-4 shadow-sm bg-white border-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5>{{ $page }}</h5>
                    <div>
                        <a class="btn btn-primary btn-sm"
                            href="{{ route('business.attendances.clock-out', $currentBusiness->slug) }}"> <i
                                class="bi bi-calendar me-2"></i> Clock Out</a>
                    </div>
                </div>
                <div class="card-body" id="jobApplicationsContainer">

                    @include('attendances._clock_in_form')

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
@php
    use App\Models\Business;
    use Illuminate\Support\Facades\Auth;

    $business = Business::findBySlug(session('active_business_slug'));
    $hidePayrollMenus =
        Auth::check() &&
        Auth::user()->hasRole('business-hr') &&
        $business &&
        $business->slug === '3rd-park-hospital-ltd';
@endphp

<x-app-layout title="{{ $page }}">

    <input type="hidden" id="active_business_slug" value="{{ session('active_business_slug') }}">

    <div class="row g-20 mb-2" id="settingUpGuideRow" style="display:none;">
        <div class="col-12">
            <div class="card__wrapper border" id="settingUpGuideCard">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-rocket-takeoff me-1"></i> Getting Started</h6>
                        <small class="text-muted" id="settingUpGuideProgressLabel"></small>
                    </div>
                    <button type="button" class="btn-close" id="dismissSetupGuideBtn" title="Hide this checklist" aria-label="Hide"></button>
                </div>
                <div class="progress mb-3" style="height:6px;">
                    <div class="progress-bar bg-success" id="settingUpGuideProgressBar" style="width:0%"></div>
                </div>
                <div id="settingUpGuideSteps" class="row g-2"></div>
            </div>
        </div>
    </div>
    <div class="mb-2" id="reopenSetupGuideRow" style="display:none;">
        <button type="button" class="btn btn-link btn-sm p-0" id="reopenSetupGuideBtn">
            <i class="bi bi-rocket-takeoff me-1"></i> Show setup guide
        </button>
    </div>

    <div class="row g-20">

        {{-- ------------------------------------------------ --}}
        {{-- 1. BUSINESS EMPLOYEES CARD (Dark background)     --}}
        {{-- ------------------------------------------------ --}}
        @php
            $businessCard = collect($cards)->first(fn($c) => strtolower($c['title']) === 'business employees');
        @endphp

        @if ($businessCard)
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 pb-4">

                <div class="p-3 rounded-4 shadow-sm d-flex flex-column justify-content-between"
                     style="background:#27303b; min-height:240px;">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0 d-flex align-items-center gap-2 text-white">
                            <span class="d-inline-flex justify-content-center align-items-center rounded-circle"
                                  style="width:26px;height:26px;background:rgba(255,255,255,0.15);">
                                <i class="{{ $businessCard['icon'] }}" style="color:white;"></i>
                            </span>
                            {{ $businessCard['title'] }}
                        </h6>

                        <div class="d-flex gap-1">
                            <span class="rounded-circle" style="width:6px;height:6px;background:#fff;"></span>
                            <span class="rounded-circle" style="width:6px;height:6px;background:#fff;"></span>
                            <span class="rounded-circle" style="width:6px;height:6px;background:#fff;"></span>
                        </div>
                    </div>

                    <p class="text-white mb-1">{{ $businessCard['title'] }}</p>

                    <h1 class="fw-bold mb-4" style="font-size:40px;color:white;">
                        {{ $businessCard['value'] ?? 0 }}
                    </h1>

                    {{-- Buttons --}}
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn fw-semibold px-3 py-2"
                                style="background:#F4A12E; color:#fff; border-radius:10px;">
                            <i class="fa-solid fa-id-badge me-1"></i>
                            On Employee
                        </button>

                        <button class="btn px-3 py-2"
                                style="background:#3A3F45; color:#fff; border-radius:10px;">
                            <i class="fa-solid fa-check-circle me-1"></i>
                            On Track
                        </button>
                    </div>

                    <a href="{{ url('/business/' . $business->slug . '/employees') }}" class="btn p-0 text-white">
    <i class="fa-solid fa-user-plus me-1"></i>
    New Employee
</a>

                </div>

            </div>
        @endif



        {{-- ------------------------------------------------ --}}
        {{-- 2. ON LEAVE EMPLOYEES (White background + green) --}}
        {{-- ------------------------------------------------ --}}

        <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 pb-4">

            <div class="p-3 rounded-4 shadow-sm d-flex flex-column justify-content-between"
                 style="background:#ffffff; min-height:240px;">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="m-0 d-flex align-items-center gap-2">
                        <span class="d-inline-flex justify-content-center align-items-center rounded-circle"
                              style="width:26px;height:26px;background:#e9ecef;">
                            <i class="fa-solid fa-plane-departure"></i>
                        </span>
                        On Leave Employees
                    </h6>
                </div>

                <p class="text-muted mb-1">Currently On Leave</p>

                <h1 class="fw-bold mb-4" style="font-size:40px;color:#000;">
                    {{ $onLeaveCount ?? 0 }}
                </h1>

                {{-- Progress --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="d-flex align-items-center gap-2">
                            <span class="rounded-circle" style="width:10px;height:10px;background:#F4A12E;"></span>
                            On Leave
                        </span>
                        <span>{{ $leavePercentage ?? 0 }}%</span>
                    </div>

                    <div class="progress mt-2" style="height:8px;">
                        <div class="progress-bar"
                             style="width: {{ $leavePercentage ?? 0 }}%; background:#F4A12E;">
                        </div>
                    </div>
                </div>

  <a href="{{ url('/business/' . $business->slug . '/leave/reports') }}"
   class="btn fw-semibold px-3 py-2 w-100 text-center"
   style="background:#3AAE8D; color:#fff; border-radius:10px;">
    View Leave Report
</a>


            </div>

        </div>



        {{-- ------------------------------------------------ --}}
        {{-- 3. OTHER CARDS (White background)                --}}
        {{-- ------------------------------------------------ --}}
        @foreach ($cards as $card)

            @php $title = strtolower($card['title']); @endphp

            {{-- Skip Business Employees & On Leave Employees --}}
            @if (in_array($title, ['business employees', 'on leave employees']))
                @continue
            @endif

            {{-- Skip restricted menus --}}
            @if ($hidePayrollMenus &&
                in_array($title, ['payroll', 'total clients', 'payroll trends', 'loan trends', 'active loans', 'active advances']))
                @continue
            @endif

            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 pb-4">

                <div class="p-3 rounded-4 shadow-sm d-flex flex-column justify-content-between"
                     style="background:#ffffff; min-height:240px;">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="m-0 d-flex align-items-center gap-2" style="color:black;">
                            <span class="d-inline-flex justify-content-center align-items-center rounded-circle"
                                  style="width:26px;height:26px;background:#e9ecef;">
                                <i class="{{ $card['icon'] }}" style="color:black;"></i>
                            </span>
                            {{ $card['title'] }}
                        </h6>

                        <div class="d-flex gap-1">
                            <span class="rounded-circle" style="width:6px;height:6px;background:#777;"></span>
                            <span class="rounded-circle" style="width:6px;height:6px;background:#777;"></span>
                            <span class="rounded-circle" style="width:6px;height:6px;background:#777;"></span>
                        </div>
                    </div>

                    <p class="text-muted mb-1">{{ $card['title'] }}</p>

                    <h1 class="fw-bold mb-4" style="font-size:40px;color:black;">
                        {{ $card['value'] ?? 0 }}
                    </h1>

                </div>

            </div>

        @endforeach



        {{-- ------------------------------------------------ --}}
        {{-- REMAINING SECTIONS DIRECTLY BELOW                --}}
        {{-- ------------------------------------------------ --}}



        {{-- PAYROLL TRENDS --}}
        @if (!$hidePayrollMenus)
            <div class="col-xxl-8 col-xl-6 col-lg-8">
                <div class="card__wrapper height-equal" style="min-height: 459px;">
                    <div class="card-header border-0">
                        <h5 class="card__heading-title">
                            <i class="fa-solid fa-bar-chart"></i> Payroll Trends
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="payrollChart"></div>
                    </div>
                </div>
            </div>
        @endif

          {{-- USER ACTIVITIES --}}
        <div class="col-xxl-4 col-xl-6 col-lg-8">
            <div class="card__wrapper height-equal" style="min-height: 459px;">
                <div class="card__title-wrap d-flex align-items-center justify-content-between mb-20">
                    <h5 class="card__heading-title">User Activities</h5>
                </div>

                <div class="overflow-auto" style="max-height: 380px;" id="activityLogsContainer">
                    {{ loader() }}
                </div>
            </div>
        </div>

        {{-- ATTENDANCE --}}
        <div class="col-md-4">
            <div class="card__wrapper height-equal" style="min-height: 459px;">
                <div class="card-header border-0">
                    <h5 class="card__heading-title">
                        <i class="fa-solid fa-calendar-check"></i> Attendance Trends
                    </h5>
                </div>
                <div class="card-body">
                    <div id="attendanceChart"></div>
                </div>
            </div>
        </div>

        {{-- LEAVE --}}
        <div class="col-md-4">
            <div class="card__wrapper height-equal" style="min-height: 459px;">
                <div class="card-header border-0">
                    <h5 class="card__heading-title">
                        <i class="fa-solid fa-plane-departure"></i> Leave Trends
                    </h5>
                </div>
                <div class="card-body">
                    <div id="leaveChart"></div>
                </div>
            </div>
        </div>

        {{-- LOAN --}}
        @if (!$hidePayrollMenus)
            <div class="col-md-4">
                <div class="card__wrapper height-equal" style="min-height: 459px;">
                    <div class="card-header border-0">
                        <h5 class="card__heading-title">
                            <i class="fa-solid fa-money-check-alt"></i> Loan Trends
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="loanChart"></div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src="{{ asset('/js/main/businesses.js') }}" type="module"></script>
        <script src="{{ asset('/js/main/trends.js') }}" type="module"></script>
        <script src="{{ asset('/js/main/log-activities.js') }}" type="module"></script>

        <script>
            $(document).ready(() => {
                @if (!$hidePayrollMenus) payrollTrends(); @endif
                logActivities();
                loadTrends(new Date().getFullYear());
            });
        </script>

        <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const businessSlug = @json($business->slug);
            const fetchUrl = @json(route('business.setup-progress.fetch', ['business' => $business->slug]));
            const dismissUrl = @json(route('business.setup-progress.dismiss', ['business' => $business->slug]));
            const reopenUrl = @json(route('business.setup-progress.reopen', ['business' => $business->slug]));

            async function loadSetupGuide() {
                try {
                    const resp = await fetch(fetchUrl, { headers: { 'Accept': 'application/json' } });
                    if (!resp.ok) return;
                    const payload = await resp.json();
                    const { dismissed, steps } = payload.data;

                    const allDone = steps.every(s => s.done);

                    if (dismissed || allDone) {
                        document.getElementById('settingUpGuideRow').style.display = 'none';
                        document.getElementById('reopenSetupGuideRow').style.display = dismissed && !allDone ? 'block' : 'none';
                        return;
                    }

                    document.getElementById('reopenSetupGuideRow').style.display = 'none';
                    document.getElementById('settingUpGuideRow').style.display = '';

                    const doneCount = steps.filter(s => s.done).length;
                    document.getElementById('settingUpGuideProgressLabel').textContent =
                        `${doneCount} of ${steps.length} steps done`;
                    document.getElementById('settingUpGuideProgressBar').style.width =
                        `${Math.round((doneCount / steps.length) * 100)}%`;

                    document.getElementById('settingUpGuideSteps').innerHTML = steps.map(step => `
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex align-items-start gap-2 p-2 border rounded ${step.done ? 'bg-light' : ''}">
                                <i class="bi ${step.done ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'} mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">${step.label}</div>
                                    <div class="text-muted" style="font-size:0.78rem;">${step.description}</div>
                                    ${step.done ? '' : `<a href="${step.route}" class="btn btn-sm btn-outline-primary mt-1">Go</a>`}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } catch (e) {
                    console.error('Could not load setup guide:', e);
                }
            }

            document.getElementById('dismissSetupGuideBtn').addEventListener('click', async function () {
                document.getElementById('settingUpGuideRow').style.display = 'none';
                document.getElementById('reopenSetupGuideRow').style.display = 'block';
                try {
                    await fetch(dismissUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                } catch (e) { console.error(e); }
            });

            document.getElementById('reopenSetupGuideBtn').addEventListener('click', async function () {
                try {
                    await fetch(reopenUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                } catch (e) { console.error(e); }
                loadSetupGuide();
            });

            loadSetupGuide();
        })();
        </script>
    @endpush

</x-app-layout>

<x-app-layout>
    <div class="row g-20">
        <div class="col-md-12">
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                @foreach ($leave_periods as $leave_period)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $leave_period->slug }}-tab"
                        data-bs-toggle="tab" data-bs-target="#{{ $leave_period->slug }}" type="button" role="tab"
                        aria-controls="{{ $leave_period->slug }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-leave-period-slug="{{ $leave_period->slug }}">
                        {{ $leave_period->name }}
                    </button>
                </li>
                @endforeach
            </ul>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $page }}</h5>
                    <a href="{{ route('business.leave.entitlements.create', $currentBusiness->slug) }}"
                        class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-square-dotted me-2"></i> Leave Entitlements
                    </a>
                </div>
                <div class="card-body table-responsive" id="leaveEntitlementsContainer">
    {{ loader() }}
</div>
            </div>
        </div>
    </div>

   @push('scripts')
<script src="{{ asset('js/main/leave-entitlement.js') }}" type="module"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        const firstTab = document.querySelector('#myTab .nav-link.active');
        if (firstTab) {
            const firstLeavePeriodSlug = firstTab.dataset.leavePeriodSlug;
            console.log('First tab found, calling getLeaveEntitlements with:', firstLeavePeriodSlug);
            getLeaveEntitlements(1, firstLeavePeriodSlug);
        } else {
            console.error('No active tab found');
        }
    });

    document.getElementById('myTab').addEventListener('click', function(event) {
        const clickedTab = event.target.closest('.nav-link');
        if (clickedTab) {
            const leavePeriodSlug = clickedTab.dataset.leavePeriodSlug;
            console.log('Tab clicked, calling getLeaveEntitlements with:', leavePeriodSlug);
            getLeaveEntitlements(1, leavePeriodSlug);
        }
    });

    window.businessSlug = '{{ request()->route('business') }}';
    console.log('Business Slug set to:', window.businessSlug);
</script>
@endpush
</x-app-layout>

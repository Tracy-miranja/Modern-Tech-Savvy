<x-app-layout>
  <div class="card">
    <div class="card-header">
      <div class="row g-2 align-items-end">
            <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date"
                    class="form-control"
                    id="ph_start"
                    value="{{ now()->startOfMonth()->format('Y-m-d') }}">
            </div>

            <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date"
                    class="form-control"
                    id="ph_end"
                    value="{{ now()->endOfMonth()->format('Y-m-d') }}">
            </div>

        <div class="col-md-3">
          <label class="form-label">Employee (optional)</label>
          <select class="form-control" id="ph_employee">
            <option value="">All</option>
            @foreach($employees as $e)
              <option value="{{ $e->id }}">{{ $e->user->name ?? 'N/A' }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <button class="btn btn-primary w-100" onclick="getPayrollHoursSummary()">
            <i class="bi bi-search"></i> View Summary
          </button>
        </div>
      </div>
    </div>

    <div class="modal fade" id="payrollEmployeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Employee Attendance Breakdown</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="payrollEmployeeContainer">
        <div class="text-center p-4">Loading...</div>
      </div>
    </div>
  </div>
</div>

    <div class="card-body" id="payrollHoursContainer">
      {{ loader() }}
    </div>
  </div>

@push('scripts')
    <script>
        window.businessSlug = @json($currentBusiness->slug);
    </script>

    <script src="{{ asset('js/main/payroll-hours.js') }}" type="module"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof window.getPayrollHoursSummary === "function") {
                window.getPayrollHoursSummary();
            }
        });
    </script>
@endpush
</x-app-layout>

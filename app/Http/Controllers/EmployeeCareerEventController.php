<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use App\Http\RequestResponse;
use App\Services\EmployeeCareerEventService;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * Employee career history - promotions and salary increments. See the
 * employee_career_events migration's docblock for the full design
 * rationale (structured old/new fields, deferred effective-dating).
 */
class EmployeeCareerEventController extends Controller
{
    use HandleTransactions;

    /**
     * "Career Growth" - a business-wide nav item (previously career events
     * were only ever reachable by drilling into one employee's own detail
     * page, with no standalone list anywhere).
     */
    public function index(Business $business)
    {
        $page = 'Career Growth';

        return view('employees.career-growth', compact('page', 'business'));
    }

    public function businessFetch(Request $request, Business $business)
    {
        $query = EmployeeCareerEvent::where('business_id', $business->id)
            ->with(['employee.user', 'oldJobCategory', 'newJobCategory', 'oldDepartment', 'newDepartment', 'issuedBy']);

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        $events = $query->orderByDesc('effective_date')->get();

        return RequestResponse::ok('Career events fetched.', $events);
    }

    public function fetch(Request $request, Business $business, Employee $employee)
    {
        if ((int) $employee->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        $events = EmployeeCareerEvent::where('employee_id', $employee->id)
            ->with(['oldJobCategory', 'newJobCategory', 'oldDepartment', 'newDepartment', 'issuedBy'])
            ->orderByDesc('effective_date')
            ->get();

        return RequestResponse::ok('Career events fetched.', $events);
    }

    public function store(Request $request, Business $business, Employee $employee)
    {
        if ((int) $employee->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        $validated = $request->validate([
            'event_type' => 'required|in:' . implode(',', EmployeeCareerEvent::TYPES),
            'effective_date' => 'required|date',
            'new_job_category_id' => 'required_if:event_type,promotion|nullable|integer|exists:job_categories,id',
            'new_department_id' => 'nullable|integer|exists:departments,id',
            'new_salary' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validated['event_type'] === 'salary_increment' && empty($validated['new_salary'])) {
            return RequestResponse::badRequest('A salary increment requires a new salary amount.', [
                'errors' => ['new_salary' => 'A new salary amount is required.'],
            ]);
        }

        return $this->handleTransaction(function () use ($validated, $employee) {
            $validated['issued_by_id'] = auth()->id();
            $event = app(EmployeeCareerEventService::class)->record($employee, $validated);

            $message = $event->status === 'applied'
                ? 'Recorded and applied immediately.'
                : 'Recorded - will apply on ' . $event->effective_date->format('M d, Y') . '.';

            return RequestResponse::created($message, $event);
        });
    }

    public function destroy(Request $request, Business $business, Employee $employee, EmployeeCareerEvent $careerEvent)
    {
        if ((int) $employee->business_id !== (int) $business->id || (int) $careerEvent->employee_id !== (int) $employee->id) {
            return RequestResponse::badRequest('Career event not found for this employee.', 404);
        }

        if ($careerEvent->status !== 'pending') {
            return RequestResponse::badRequest("Only a still-pending event can be removed - an applied change is now part of the employee's history.");
        }

        $careerEvent->delete();

        return RequestResponse::ok('Career event removed.');
    }
}

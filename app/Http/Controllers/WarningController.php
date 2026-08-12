<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Warning;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployeeWarningIssued;
use App\Mail\EmployeeWarningResolved;

class WarningController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Disciplinary';
        $description = 'Track disciplinary process from informal action through appeal';

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $employees = $business->employees;
        $warnings = Warning::where('business_id', $business->id)
            ->with('employee.user', 'issuedBy')
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('employees.warning.index', compact('page', 'description', 'employees', 'warnings'));
    }

    protected function filtered(Business $business, Request $request)
    {
        $query = Warning::where('business_id', $business->id)->with('employee.user', 'issuedBy');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('case_id', 'like', "%{$search}%")
                  ->orWhere('offence', 'like', "%{$search}%")
                  ->orWhereHas('employee.user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('stage')) {
            $query->where('stage', $request->input('stage'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        return $query->orderBy('issue_date', 'desc');
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $warnings = $this->filtered($business, $request)->get();

            $counts = [
                'open'            => Warning::where('business_id', $business->id)->where('stage', '!=', 'closed')->count(),
                'investigation'   => Warning::where('business_id', $business->id)->where('stage', 'investigation')->count(),
                'pending_hearing' => Warning::where('business_id', $business->id)->whereIn('stage', ['notification_to_hearing', 'disciplinary_hearing'])->count(),
                'closed'          => Warning::where('business_id', $business->id)->where('stage', 'closed')->count(),
            ];

            $rows = view('employees.warning._rows', compact('warnings'))->render();

            return RequestResponse::ok('Warnings fetched successfully.', [
                'html'   => $rows,
                'count'  => $warnings->count(),
                'counts' => $counts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch warnings:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch warnings.', ['errors' => [$e->getMessage()]]);
        }
    }

    public function store(Request $request, Business $business)
    {
        $validatedData = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'category'         => 'required|in:' . implode(',', Warning::CATEGORIES),
            'offence'          => 'required|string',
            'reported_by_name' => 'nullable|string|max:255',
            'issue_date'       => 'required|date',
            'stage'            => 'required|in:' . implode(',', Warning::STAGES),
            'hearing_date'     => 'nullable|date',
            'decision_outcome' => 'nullable|in:' . implode(',', Warning::DECISION_OUTCOMES),
            'appeal_status'    => 'nullable|in:' . implode(',', Warning::APPEAL_STATUSES),
            'description'      => 'nullable|string',
            'attachment'       => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
        ]);

       return $this->handleTransaction(function () use ($validatedData, $request, $business) {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('disciplinary', 'public');
            }

            $warning = Warning::create([
                'employee_id'      => $validatedData['employee_id'],
                'business_id'      => $business->id,
                'category'         => $validatedData['category'],
                'offence'          => $validatedData['offence'],
                'reason'           => $validatedData['offence'],
                'reported_by_name' => $validatedData['reported_by_name'] ?? null,
                'issue_date'       => $validatedData['issue_date'],
                'stage'            => $validatedData['stage'],
                'hearing_date'     => $validatedData['hearing_date'] ?? null,
                'decision_outcome' => $validatedData['decision_outcome'] ?? 'pending',
                'appeal_status'    => $validatedData['appeal_status'] ?? null,
                'description'      => $validatedData['description'] ?? null,
                'attachment'       => $attachmentPath,
                'status'           => $validatedData['stage'] === 'closed' ? 'resolved' : 'active',
                'issued_by'        => auth()->user()->id,
            ]);

            $employee = $warning->employee;
            if ($employee && $employee->user && $employee->user->email) {
                Mail::to($employee->user->email)->send(new EmployeeWarningIssued($warning));
            }

            return RequestResponse::created('Disciplinary case opened successfully.', $warning->id);
        });
    }

    public function acknowledge(Request $request, $id)
    {
        $employee = auth()->user()->activeEmployee();
        if (!$employee) {
            return RequestResponse::badRequest('No employee record for the current user.');
        }

        return $this->handleTransaction(function () use ($id, $employee) {
            $warning = Warning::where('id', $id)->where('employee_id', $employee->id)->first();
            if (!$warning) {
                return RequestResponse::badRequest('Disciplinary case not found for this employee.', 404);
            }
            if (!$warning->acknowledged_at) {
                $warning->update(['acknowledged_at' => now(), 'acknowledged_by' => $employee->id]);
            }
            return RequestResponse::ok('Acknowledged.');
        });
    }

   public function edit(Request $request, Business $business)
    {
        $validatedData = $request->validate(['warning_id' => 'nullable|exists:warnings,id']);

        $employees = $business->employees;
        $warning = null;

        if (!empty($validatedData['warning_id'])) {
            $warning = Warning::where('business_id', $business->id)
                ->where('id', $validatedData['warning_id'])
                ->firstOrFail();
        }

        $form = view('employees.warning._form', compact('warning', 'employees'))->render();
        return RequestResponse::ok('Warning form loaded successfully.', $form);
    }

   public function show(Request $request, Business $business, $id)
    {
        $warning = Warning::where('business_id', $business->id)
            ->with('employee.user', 'issuedBy')
            ->where('id', $id)
            ->firstOrFail();

        $html = view('employees.warning._show', compact('warning'))->render();
        return RequestResponse::ok('Warning details loaded.', $html);
    }

    public function update(Request $request, Business $business, $id)
    {
        $validatedData = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'category'         => 'required|in:' . implode(',', Warning::CATEGORIES),
            'offence'          => 'required|string',
            'reported_by_name' => 'nullable|string|max:255',
            'issue_date'       => 'required|date',
            'stage'            => 'required|in:' . implode(',', Warning::STAGES),
            'hearing_date'     => 'nullable|date',
            'decision_outcome' => 'nullable|in:' . implode(',', Warning::DECISION_OUTCOMES),
            'appeal_status'    => 'nullable|in:' . implode(',', Warning::APPEAL_STATUSES),
            'description'      => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $business, $id) {
            $warning = Warning::where('business_id', $business->id)->where('id', $id)->firstOrFail();
            $previousStage = $warning->stage;

            $warning->update([
                'employee_id'      => $validatedData['employee_id'],
                'category'         => $validatedData['category'],
                'offence'          => $validatedData['offence'],
                'reason'           => $validatedData['offence'],
                'reported_by_name' => $validatedData['reported_by_name'] ?? $warning->reported_by_name,
                'issue_date'       => $validatedData['issue_date'],
                'stage'            => $validatedData['stage'],
                'hearing_date'     => $validatedData['hearing_date'] ?? null,
                'decision_outcome' => $validatedData['decision_outcome'] ?? $warning->decision_outcome,
                'appeal_status'    => $validatedData['appeal_status'] ?? $warning->appeal_status,
                'description'      => $validatedData['description'] ?? null,
                'status'           => $validatedData['stage'] === 'closed' ? 'resolved' : 'active',
                'issued_by'        => auth()->user()->id,
            ]);

            $employee = $warning->employee;
            if ($employee && $employee->user && $employee->user->email) {
                if ($validatedData['stage'] === 'closed' && $previousStage !== 'closed') {
                    Mail::to($employee->user->email)->send(new EmployeeWarningResolved($warning));
                } elseif ($previousStage !== $validatedData['stage']) {
                    Mail::to($employee->user->email)->send(new EmployeeWarningIssued($warning));
                }
            }

            return RequestResponse::ok('Warning updated successfully.');
        });
    }

   public function destroy(Request $request, Business $business, $id)
    {
        return $this->handleTransaction(function () use ($business, $id) {
            $warning = Warning::where('business_id', $business->id)->where('id', $id)->firstOrFail();
            $warning->delete();
            return RequestResponse::ok('Warning deleted successfully.');
        });
    }
}

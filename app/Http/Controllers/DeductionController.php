<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Deduction;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;

class DeductionController extends Controller
{
    use HandleTransactions;

    private $computationMethods = ['fixed', 'rate', 'formula'];

    public function index(Request $request)
    {
        $page = 'Deductions';
        $description = 'Manage custom deductions for employees.';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $deductions = Deduction::where('business_id', $business->id)->get();
        return view('deductions.index', compact('page', 'description', 'deductions'));
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $deductions = Deduction::where('business_id', $business->id)->get();
            $deductionsTable = view('deductions._table', compact('deductions'))->render();

            return RequestResponse::ok('Deductions fetched successfully.', [
                'html' => $deductionsTable,
                'count' => $deductions->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch deductions:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch deductions.', ['errors' => [$e->getMessage()]]);
        }
    }

public function store(Request $request)
{
    $validatedData = $this->validateDeduction($request);

    return $this->handleTransaction(function () use ($validatedData) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $deduction = Deduction::create([
            'name' => $validatedData['name'],
            'slug' => \Str::slug($validatedData['name']),
            'description' => $validatedData['description'],
            'calculation_basis' => $validatedData['calculation_basis'],
            'computation_method' => $validatedData['computation_method'],
            'amount' => $validatedData['computation_method'] === 'fixed' ? $validatedData['amount'] : null,
            'rate' => $validatedData['computation_method'] === 'rate' ? $validatedData['rate'] : null,
            'formula' => $validatedData['computation_method'] === 'formula' ? $validatedData['formula'] : null,
            'actual_amount' => $validatedData['actual_amount'] ?? false,
            'fraction_to_consider' => $validatedData['fraction_to_consider'],
            'limit' => $validatedData['limit'] ?? 0, // Default to 0 if limit is not nullable
            'employer_rate'  => $this->resolveEmployerRate($validatedData),
                'employer_limit' => $this->resolveEmployerLimit($validatedData),
            'round_off' => $validatedData['round_off'],
            'decimal_places' => $validatedData['decimal_places'],
            'is_statutory' => false,
            'is_optional' => true,
            'business_id' => $business->id,
            'created_by' => auth()->id(),
        ]);

        return RequestResponse::created('Deduction created successfully.', $deduction->id);
    });
}

    public function edit(Request $request)
    {
        $validatedData = $request->validate(['deduction_id' => 'nullable|exists:deductions,id']);
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $deduction = null;
        if (!empty($validatedData['deduction_id'])) {
            $deduction = Deduction::where('business_id', $business->id)
                ->where('id', $validatedData['deduction_id'])
                ->firstOrFail();
        }

        $form = view('deductions._form', compact('deduction'))->render();
        return RequestResponse::ok('Deduction form loaded successfully.', $form);
    }

    public function show(Request $request)
    {
        $validatedData = $request->validate(['deduction_id' => 'required|exists:deductions,id']);
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $deduction = Deduction::where('business_id', $business->id)
            ->where('id', $validatedData['deduction_id'])
            ->firstOrFail();

        $modal = view('deductions._modal', compact('deduction'))->render();
        return RequestResponse::ok('Deduction details loaded successfully.', $modal);
    }

public function update(Request $request, $id)
{
    $validatedData = $this->validateDeduction($request, true);

    return $this->handleTransaction(function () use ($validatedData, $id) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $deduction = Deduction::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($deduction->id != $validatedData['deduction_id']) {
            return RequestResponse::badRequest('Deduction ID mismatch.');
        }

        $deduction->update([
            'name' => $validatedData['name'],
            'slug' => \Str::slug($validatedData['name']),
            'description' => $validatedData['description'],
            'calculation_basis' => $validatedData['calculation_basis'],
            'computation_method' => $validatedData['computation_method'],
            'amount' => $validatedData['computation_method'] === 'fixed' ? $validatedData['amount'] : null,
            'rate' => $validatedData['computation_method'] === 'rate' ? $validatedData['rate'] : null,
            'formula' => $validatedData['computation_method'] === 'formula' ? $validatedData['formula'] : null,
            'actual_amount' => $validatedData['actual_amount'] ?? false,
            'fraction_to_consider' => $validatedData['fraction_to_consider'],
            'limit' => $validatedData['limit'] ?? 0, // Default to 0 if limit is not nullable
             // ── Employer-specific fields ────────────────────────────────────
                'employer_rate'  => $this->resolveEmployerRate($validatedData),
                'employer_limit' => $this->resolveEmployerLimit($validatedData),
            'round_off' => $validatedData['round_off'],
            'decimal_places' => $validatedData['decimal_places'],
        ]);

        return RequestResponse::ok('Deduction updated successfully.');
    });
}
    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate(['deduction_id' => 'required|exists:deductions,id']);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $deduction = Deduction::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($deduction->id != $validatedData['deduction_id']) {
                return RequestResponse::badRequest('Deduction ID mismatch.');
            }

            $deduction->delete();
            return RequestResponse::ok('Deduction deleted successfully.');
        });
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Resolve employer_rate to store:
     *   - NULL  when not applicable (employee_only, or method != rate)
     *   - NULL  when field was blank (means "mirror employee rate")
     *   - float when explicitly provided and differs from employee rate
     */
    private function resolveEmployerRate(array $data): ?float
    {
        $fraction = $data['fraction_to_consider'] ?? 'employee_only';
        $method   = $data['computation_method']   ?? '';

        if ($method !== 'rate') {
            return null; // only relevant for rate-based deductions
        }

        if (!in_array($fraction, ['employee_and_employer', 'employer_only'])) {
            return null; // no employer side at all
        }

        $raw = $data['employer_rate'] ?? null;

        // Blank or zero → NULL (will fall back to employee rate at runtime)
        if ($raw === null || $raw === '' || floatval($raw) <= 0) {
            return null;
        }

        return floatval($raw);
    }

    /**
     * Resolve employer_limit to store:
     *   - NULL  when not applicable (employee_only)
     *   - NULL  when field was blank (means "mirror employee limit")
     *   - float when explicitly provided
     */
    private function resolveEmployerLimit(array $data): ?float
    {
        $fraction = $data['fraction_to_consider'] ?? 'employee_only';

        if (!in_array($fraction, ['employee_and_employer', 'employer_only'])) {
            return null;
        }

        $raw = $data['employer_limit'] ?? null;

        if ($raw === null || $raw === '') {
            return null; // mirror employee limit at runtime
        }

        return floatval($raw);
    }


private function validateDeduction(Request $request, $isUpdate = false)
{
    $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'calculation_basis' => 'required|in:basic_pay,gross_pay,cash_pay,taxable_pay,custom',
        'computation_method' => 'required|in:' . implode(',', $this->computationMethods),
        'amount' => 'nullable|numeric|min:0|required_if:computation_method,fixed',
        'rate' => 'nullable|numeric|min:0|max:100|required_if:computation_method,rate',
        'formula' => 'nullable|string|max:255|required_if:computation_method,formula',
        'actual_amount' => 'nullable|boolean',
        'fraction_to_consider' => 'required|in:employee_only,employee_and_employer,employer_only',
        'limit' => 'nullable|numeric|min:0',
        'employer_rate' => 'nullable|numeric|min:0|max:100',  // ADD THIS
        'employer_limit' => 'nullable|numeric|min:0',          // ADD THIS
        'round_off' => 'required|in:round_off_up,round_off_down',
        'decimal_places' => 'required|integer|min:0|max:5',
    ];

    if ($isUpdate) {
        $rules['deduction_id'] = 'required|exists:deductions,id';
    }

    return $request->validate($rules);
}
}

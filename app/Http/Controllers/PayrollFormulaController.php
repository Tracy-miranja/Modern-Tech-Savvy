<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\PayrollFormula;
use App\Models\PayrollFormulaBracket;
use App\Models\EmployeePayrollDetail;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayrollFormulaController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = "Payroll Formulas";
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->back()->with('error', 'Business not found.');
        }

        // Fetch formulas based on business slug
        $formulas = $this->getFormulasForBusiness($business);

        $employees = Employee::where('business_id', $business->id)->with('payrollDetail')->get();
        $countries = [
            'Kenya' => 'Kenya',
            'Nigeria' => 'Nigeria',
            'Uganda' => 'Uganda',
            'Tanzania' => 'Tanzania',
            'Rwanda' => 'Rwanda',
            'Senegal' => 'Senegal',
            'South Africa' => 'South Africa',
            'Ethiopia' => 'Ethiopia'
        ];

        return view('payroll-formulas.index', compact('formulas', 'business', 'page', 'employees', 'countries'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'country' => 'required|in:Kenya,Nigeria,Uganda,Tanzania,Rwanda,Senegal,South Africa,Ethiopia',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'formula_type' => 'required|in:rate,fixed,progressive,expression',
        'calculation_basis' => 'required|in:basic_pay,gross_pay,taxable_pay,net_pay,custom',
        'is_statutory' => 'boolean',
        'is_progressive' => 'boolean',
        'minimum_amount' => 'nullable|numeric|min:0',
        'limit' => 'nullable|numeric|min:0',
        'round_off' => 'nullable|in:round_up,round_down,nearest',
        'applies_to' => 'required|in:all,specific',
        'expression_rate' => 'nullable|numeric|min:0|required_if:formula_type,expression',
        'expression_minimum' => 'nullable|numeric|min:0',
        'brackets' => 'array|required_if:is_progressive,1',
        'brackets.*.min' => 'nullable|numeric|min:0',
        'brackets.*.max' => 'nullable|numeric|min:0',
        'brackets.*.rate' => 'nullable|numeric|min:0',
        'brackets.*.amount' => 'nullable|numeric|min:0',
    ]);

    return $this->handleTransaction(function () use ($validated, $request) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $expression = $validated['formula_type'] === 'expression'
            ? "max({$validated['calculation_basis']} * " . ($validated['expression_rate'] / 100) . ", " . ($validated['expression_minimum'] ?? 0) . ")"
            : null;

        $formula = PayrollFormula::create([
            'business_id' => $business->id,
            'country' => $validated['country'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'formula_type' => $validated['formula_type'],
            'calculation_basis' => $validated['calculation_basis'],
            'is_statutory' => $validated['is_statutory'] ?? false,
            'is_progressive' => $validated['is_progressive'] ?? false,
            'minimum_amount' => $validated['minimum_amount'],
            'limit' => $validated['limit'],
            'round_off' => $validated['round_off'],
            'applies_to' => $validated['applies_to'],
            'expression' => $expression,
        ]);

        if (($validated['is_progressive'] ?? false) && !empty($validated['brackets'])) {
            foreach ($validated['brackets'] as $bracket) {
                PayrollFormulaBracket::create([
                    'payroll_formula_id' => $formula->id,
                    'min' => $bracket['min'],
                    'max' => $bracket['max'],
                    'rate' => $bracket['rate'],
                    'amount' => $bracket['amount'],
                ]);
            }
        }

        return RequestResponse::created('Payroll formula created.', $formula->id);
    });
}

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'country' => 'required|in:Kenya,Nigeria,Uganda,Tanzania,Rwanda,Senegal,South Africa,Ethiopia',
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'formula_type' => 'required|in:rate,fixed,progressive,expression',
    //         'calculation_basis' => 'required|in:basic_pay,gross_pay,taxable_pay,net_pay,custom',
    //         'is_statutory' => 'boolean',
    //         'is_progressive' => 'boolean',
    //         'minimum_amount' => 'nullable|numeric|min:0',
    //         'limit' => 'nullable|numeric|min:0',
    //         'round_off' => 'nullable|in:round_up,round_down,nearest',
    //         'applies_to' => 'required|in:all,specific',
    //         'expression_rate' => 'nullable|numeric|min:0|required_if:formula_type,expression',
    //         'expression_minimum' => 'nullable|numeric|min:0',
    //         'brackets' => 'array|required_if:is_progressive,1',
    //         'brackets.*.min' => 'nullable|numeric|min:0',
    //         'brackets.*.max' => 'nullable|numeric|min:0',
    //         'brackets.*.rate' => 'nullable|numeric|min:0',
    //         'brackets.*.amount' => 'nullable|numeric|min:0',
    //     ]);

    //     return $this->handleTransaction(function () use ($validated, $request) {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         $expression = $validated['formula_type'] === 'expression'
    //             ? "max({$validated['calculation_basis']} * " . ($validated['expression_rate'] / 100) . ", " . ($validated['expression_minimum'] ?? 0) . ")"
    //             : null;

    //         $formula = PayrollFormula::create([
    //             'business_id' => $business->id,
    //             'country' => $validated['country'],
    //             'name' => $validated['name'],
    //             'slug' => Str::slug($validated['name']),
    //             'description' => $validated['description'],
    //             'formula_type' => $validated['formula_type'],
    //             'calculation_basis' => $validated['calculation_basis'],
    //             'is_statutory' => $validated['is_statutory'] ?? false,
    //             'is_progressive' => $validated['is_progressive'] ?? false,
    //             'minimum_amount' => $validated['minimum_amount'],
    //             'limit' => $validated['limit'],
    //             'round_off' => $validated['round_off'],
    //             'applies_to' => $validated['applies_to'],
    //             'expression' => $expression,
    //         ]);

    //         if ($validated['is_progressive'] && !empty($validated['brackets'])) {
    //             foreach ($validated['brackets'] as $bracket) {
    //                 PayrollFormulaBracket::create([
    //                     'payroll_formula_id' => $formula->id,
    //                     'min' => $bracket['min'],
    //                     'max' => $bracket['max'],
    //                     'rate' => $bracket['rate'],
    //                     'amount' => $bracket['amount'],
    //                 ]);
    //             }
    //         }

    //         return RequestResponse::created('Payroll formula created.', $formula->id);
    //     });
    // }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            // Fetch formulas based on business slug
            $formulas = $this->getFormulasForBusiness($business);

            $formulasTable = view('payroll-formulas._table', compact('formulas', 'business'))->render();

            return RequestResponse::ok('Payroll formulas fetched successfully.', [
                'html' => $formulasTable,
                'count' => $formulas->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch payroll formulas:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch payroll formulas.', ['errors' => [$e->getMessage()]]);
        }
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'formula_id' => 'nullable|exists:payroll_formulas,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        // Restrict edit to the platform business
        if ($business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can edit payroll formulas.');
        }

        $formula = null;
        if (!empty($validatedData['formula_id'])) {
            $formula = PayrollFormula::with('brackets')
                ->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)->orWhereNull('business_id');
                })
                ->where('id', $validatedData['formula_id'])
                ->firstOrFail();
        }

        $countries = [
            'Kenya' => 'Kenya',
            'Nigeria' => 'Nigeria',
            'Uganda' => 'Uganda',
            'Tanzania' => 'Tanzania',
            'Rwanda' => 'Rwanda',
            'Senegal' => 'Senegal',
            'South Africa' => 'South Africa',
            'Ethiopia' => 'Ethiopia'
        ];
      $form = view('payroll-formulas._form', compact('formula', 'countries', 'business'))->render();
        return RequestResponse::ok('Payroll formula form loaded successfully.', $form);
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'formula_id' => 'required|exists:payroll_formulas,id',
        'country' => 'required|in:Kenya,Nigeria,Uganda,Tanzania,Rwanda,Senegal,South Africa,Ethiopia',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'formula_type' => 'required|in:rate,fixed,progressive,expression',
        'calculation_basis' => 'required|in:basic_pay,gross_pay,taxable_pay,net_pay,custom',
        'is_statutory' => 'nullable|boolean',
        'is_progressive' => 'nullable|boolean', // Updated
        'minimum_amount' => 'nullable|numeric|min:0',
        'limit' => 'nullable|numeric|min:0',
        'round_off' => 'nullable|in:round_up,round_down,nearest',
        'applies_to' => 'required|in:all,specific',
        'expression_rate' => 'nullable|numeric|min:0|required_if:formula_type,expression',
        'expression_minimum' => 'nullable|numeric|min:0',
        'brackets' => 'array|required_if:is_progressive,1',
        'brackets.*.min' => 'nullable|numeric|min:0',
        'brackets.*.max' => 'nullable|numeric|min:0',
        'brackets.*.rate' => 'nullable|numeric|min:0',
        'brackets.*.amount' => 'nullable|numeric|min:0',
    ]);

    return $this->handleTransaction(function () use ($validated, $id) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        if ($business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can update payroll formulas.');
        }

        $formula = PayrollFormula::where('id', $id)
            ->where(function ($query) use ($business) {
                $query->where('business_id', $business->id)->orWhereNull('business_id');
            })
            ->firstOrFail();

        if ($formula->id != $validated['formula_id']) {
            return RequestResponse::badRequest('Formula ID mismatch.');
        }

        $expression = $validated['formula_type'] === 'expression'
            ? "max({$validated['calculation_basis']} * " . ($validated['expression_rate'] / 100) . ", " . ($validated['expression_minimum'] ?? 0) . ")"
            : null;

        // Force SHIF and NHIF settings for compliance
        $data = [
            'country' => $validated['country'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'formula_type' => $validated['formula_type'],
            'calculation_basis' => $validated['calculation_basis'],
            'is_statutory' => $validated['is_statutory'] ?? false,
            'is_progressive' => $validated['is_progressive'] ?? false, // Default to false
            'minimum_amount' => $validated['minimum_amount'],
            'limit' => $validated['limit'],
            'round_off' => $validated['round_off'],
            'applies_to' => $validated['applies_to'],
            'expression' => $expression,
        ];

        if ($data['slug'] === 'shif') {
            $data['formula_type'] = 'expression';
            $data['calculation_basis'] = 'gross_pay';
            $data['is_statutory'] = true;
            $data['is_progressive'] = false;
            $data['minimum_amount'] = 300;
            $data['limit'] = null;
            $data['expression'] = "max(gross_pay * 0.0275, 300)";
        } elseif ($data['slug'] === 'nhif') {
            $data['formula_type'] = 'fixed';
            $data['is_statutory'] = true;
            $data['is_progressive'] = false;
            $data['minimum_amount'] = 0;
            $data['limit'] = 0;
            $data['expression'] = '0';
        }

        $formula->update($data);

        if (($validated['is_progressive'] ?? false) && !empty($validated['brackets'])) {
            $formula->brackets()->delete();
            foreach ($validated['brackets'] as $bracket) {
                PayrollFormulaBracket::create([
                    'payroll_formula_id' => $formula->id,
                    'min' => $bracket['min'],
                    'max' => $bracket['max'],
                    'rate' => $bracket['rate'],
                    'amount' => $bracket['amount'],
                ]);
            }
        } else {
            $formula->brackets()->delete(); // Clear brackets if not progressive
        }

        return RequestResponse::ok('Payroll formula updated.');
    });
}

    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         'formula_id' => 'required|exists:payroll_formulas,id',
    //         'country' => 'required|in:Kenya,Nigeria,Uganda,Tanzania,Rwanda,Senegal,South Africa,Ethiopia',
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'formula_type' => 'required|in:rate,fixed,progressive,expression',
    //         'calculation_basis' => 'required|in:basic_pay,gross_pay,taxable_pay,net_pay,custom',
    //         'is_statutory' => 'boolean',
    //         'is_progressive' => 'boolean',
    //         'minimum_amount' => 'nullable|numeric|min:0',
    //         'limit' => 'nullable|numeric|min:0',
    //         'round_off' => 'nullable|in:round_up,round_down,nearest',
    //         'applies_to' => 'required|in:all,specific',
    //         'expression_rate' => 'nullable|numeric|min:0|required_if:formula_type,expression',
    //         'expression_minimum' => 'nullable|numeric|min:0',
    //         'brackets' => 'array|required_if:is_progressive,1',
    //         'brackets.*.min' => 'nullable|numeric|min:0',
    //         'brackets.*.max' => 'nullable|numeric|min:0',
    //         'brackets.*.rate' => 'nullable|numeric|min:0',
    //         'brackets.*.amount' => 'nullable|numeric|min:0',
    //     ]);

    //     return $this->handleTransaction(function () use ($validated, $id) {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         // Restrict update to the platform business
    //         if ($business->slug !== config('business.main_slug')) {
    //             return RequestResponse::forbidden('Only the platform business can update payroll formulas.');
    //         }

    //         $formula = PayrollFormula::where('id', $id)
    //             ->where(function ($query) use ($business) {
    //                 $query->where('business_id', $business->id)->orWhereNull('business_id');
    //             })
    //             ->firstOrFail();

    //         if ($formula->id != $validated['formula_id']) {
    //             return RequestResponse::badRequest('Formula ID mismatch.');
    //         }

    //         $expression = $validated['formula_type'] === 'expression'
    //             ? "max({$validated['calculation_basis']} * " . ($validated['expression_rate'] / 100) . ", " . ($validated['expression_minimum'] ?? 0) . ")"
    //             : null;

    //         $formula->update([
    //             'country' => $validated['country'],
    //             'name' => $validated['name'],
    //             'slug' => Str::slug($validated['name']),
    //             'description' => $validated['description'],
    //             'formula_type' => $validated['formula_type'],
    //             'calculation_basis' => $validated['calculation_basis'],
    //             'is_statutory' => $validated['is_statutory'] ?? false,
    //             'is_progressive' => $validated['is_progressive'] ?? false,
    //             'minimum_amount' => $validated['minimum_amount'],
    //             'limit' => $validated['limit'],
    //             'round_off' => $validated['round_off'],
    //             'applies_to' => $validated['applies_to'],
    //             'expression' => $expression,
    //         ]);

    //         if ($validated['is_progressive'] && !empty($validated['brackets'])) {
    //             $formula->brackets()->delete();
    //             foreach ($validated['brackets'] as $bracket) {
    //                 PayrollFormulaBracket::create([
    //                     'payroll_formula_id' => $formula->id,
    //                     'min' => $bracket['min'],
    //                     'max' => $bracket['max'],
    //                     'rate' => $bracket['rate'],
    //                     'amount' => $bracket['amount'],
    //                 ]);
    //             }
    //         }

    //         return RequestResponse::ok('Payroll formula updated.');
    //     });
    // }

    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate([
            'formula_id' => 'required|exists:payroll_formulas,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            // Restrict delete to the platform business
            if ($business->slug !== config('business.main_slug')) {
                return RequestResponse::forbidden('Only the platform business can delete payroll formulas.');
            }

            $formula = PayrollFormula::where('id', $id)
                ->where(function ($query) use ($business) {
                    $query->where('business_id', $business->id)->orWhereNull('business_id');
                })
                ->firstOrFail();

            if ($formula->id != $validatedData['formula_id']) {
                return RequestResponse::badRequest('Formula ID mismatch.');
            }

            if ($formula->is_statutory) {
                return RequestResponse::badRequest('Statutory formulas cannot be deleted.');
            }

            $formula->brackets()->delete();
            $formula->delete();

            return RequestResponse::ok('Payroll formula deleted successfully.');
        });
    }

    public function subscribe(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'formula_id' => 'required|exists:payroll_formulas,id',
            'subscribe' => 'required|boolean',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $employee = Employee::where('business_id', $business->id)
                ->where('id', $validatedData['employee_id'])
                ->firstOrFail();
            $formula = PayrollFormula::where(function ($query) use ($business) {
                $query->where('business_id', $business->id)->orWhereNull('business_id');
            })
                ->where('id', $validatedData['formula_id'])
                ->firstOrFail();

            $payrollDetail = EmployeePayrollDetail::firstOrCreate(
                ['employee_id' => $employee->id],
                ['business_id' => $business->id]
            );

            if ($formula->slug === 'helb') {
                $payrollDetail->update(['has_helb' => $validatedData['subscribe']]);
            }

            return RequestResponse::ok($validatedData['subscribe'] ? 'Employee subscribed to formula.' : 'Employee unsubscribed from formula.');
        });
    }

    public function bracketTemplate(Request $request)
    {
        $index = $request->input('index', 0);
        $bracket = new PayrollFormulaBracket();
        // return response()->json([
        //     'html' => view('payroll-formulas._bracket', compact('index', 'bracket'))->render()
        // ]);
         return view('payroll-formulas._bracket', compact('index', 'bracket'));
    }

    /**
     * Fetch payroll formulas based on business slug and country.
     */
    protected function getFormulasForBusiness(Business $business)
    {
        if ($business->slug === config('business.main_slug')) {
            // The platform business fetches all formulas
            return PayrollFormula::with('brackets')->get();
        }

        // Other businesses fetch formulas matching their country
        return PayrollFormula::with('brackets')
            ->where(function ($query) use ($business) {
                $query->where('business_id', $business->id)
                    ->orWhereNull('business_id');
            })
            ->where('country', $business->country)
            ->get();
    }
}

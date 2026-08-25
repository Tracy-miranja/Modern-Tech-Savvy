<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Allowance;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Str;

class AllowanceController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Allowances';
        $description = 'Manage allowances.';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $allowances = Allowance::where('business_id', $business->id)->get();

        return view('allowances.index', compact('page', 'description', 'allowances'));
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $allowances = Allowance::where('business_id', $business->id)->get();
            $allowancesTable = view('allowances._table', compact('allowances'))->render();

            return RequestResponse::ok('Allowances fetched successfully.', [
                'html' => $allowancesTable,
                'count' => $allowances->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch allowances:', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to fetch allowances.', [
                'errors' => [$e->getMessage()]
            ]);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:fixed,rate',
            'calculation_basis' => 'required|in:basic_pay,gross_pay,custom',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'rate' => 'required_if:type,rate|nullable|numeric|min:0|max:100',
            'is_taxable' => 'nullable|boolean',
            'applies_to' => 'required|in:all,specific',
        ]);

       return $this->handleTransaction(
    function () use ($request, $validatedData) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $slug = \Str::slug($validatedData['name']);

        $amount = $validatedData['type'] === 'fixed' && ($validatedData['amount'] ?? '') !== ''
            ? $validatedData['amount']
            : null;

        $rate = $validatedData['type'] === 'rate' && ($validatedData['rate'] ?? '') !== ''
            ? $validatedData['rate']
            : null;

        $allowance = Allowance::create([
            'name'              => $validatedData['name'],
            'slug'              => $slug,
            'type'              => $validatedData['type'],
            'calculation_basis' => $validatedData['calculation_basis'],
            'amount'            => $amount,
            'rate'              => $rate,

            'is_taxable'        => $request->boolean('is_taxable'),
            'applies_to'        => $validatedData['applies_to'],
            'business_id'       => $business->id,
        ]);

        return RequestResponse::created('Allowance created successfully.', $allowance->id);
    },
    function ($e) {
        return RequestResponse::badRequest('Failed to create allowance.', [
            'errors' => $e instanceof \Illuminate\Validation\ValidationException
                ? $e->errors()
                : [$e->getMessage()]
        ]);
    }
);
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'allowance_id' => 'nullable|exists:allowances,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $allowance = null;
        if (!empty($validatedData['allowance_id'])) {
            $allowance = Allowance::where('business_id', $business->id)
                ->where('id', $validatedData['allowance_id'])
                ->firstOrFail();
        }

        $form = view('allowances._form', compact('allowance'))->render();
        return RequestResponse::ok('Allowance form loaded successfully.', $form);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'allowance_id' => 'required|exists:allowances,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:fixed,rate',
            'calculation_basis' => 'required|in:basic_pay,gross_pay,custom',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'rate' => 'required_if:type,rate|nullable|numeric|min:0|max:100',
            'is_taxable' => 'nullable|boolean',
            'applies_to' => 'required|in:all,specific',
        ]);

        \Log::info('Validated data for update:', $validatedData);

        return $this->handleTransaction(function () use ($request, $validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $allowance = Allowance::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($allowance->id != $validatedData['allowance_id']) {
                return RequestResponse::badRequest('Allowance ID mismatch.');
            }

            $slug = \Str::slug($validatedData['name']);
            $updateData = [
    'name' => $validatedData['name'],
    'slug' => $slug,
    'type' => $validatedData['type'],
    'calculation_basis' => $validatedData['calculation_basis'],
    'amount' => $validatedData['type'] === 'fixed' && $validatedData['amount'] !== '' ? $validatedData['amount'] : null,
    'rate' => $validatedData['type'] === 'rate' && $validatedData['rate'] !== '' ? $validatedData['rate'] : null,

    'is_taxable' => $request->boolean('is_taxable'),
    'applies_to' => $validatedData['applies_to'],
];

            \Log::info('Data to update allowance:', $updateData);

            \Log::info('Dirty attributes before update:', $allowance->getDirty());

            $updated = $allowance->update($updateData);

            \Log::info('Update result:', ['success' => $updated]);
            \Log::info('Allowance after update (in-memory):', $allowance->toArray());
            \Log::info('Allowance after update (fresh):', $allowance->fresh()->toArray());

            if (!$updated) {
                \Log::warning('Update method returned false.');
                return RequestResponse::badRequest('Failed to persist allowance update.');
            }

            $rowsAffected = \DB::table('allowances')
                ->where('id', $allowance->id)
                ->where('business_id', $business->id)
                ->update(['applies_to' => $validatedData['applies_to'], 'updated_at' => now()]);

            \Log::info('Direct SQL update rows affected:', ['rows' => $rowsAffected]);
            \Log::info('Allowance after direct update (fresh):', $allowance->fresh()->toArray());

            return RequestResponse::ok('Allowance updated successfully.');
        });
    }

    public function destroy(Request $request, $id)
    {
        $validatedData = $request->validate([
            'allowance_id' => 'required|exists:allowances,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $allowance = Allowance::where('business_id', $business->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($allowance->id != $validatedData['allowance_id']) {
                return RequestResponse::badRequest('Allowance ID mismatch.');
            }

            $allowance->delete();

            return RequestResponse::ok('Allowance deleted successfully.');
        });
    }
}

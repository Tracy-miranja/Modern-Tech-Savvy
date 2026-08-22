<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Business;
use App\Models\Employee;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * Asset Management - one of the 3 modules that previously existed only as
 * a name/price entry in ModulesSeeder with no real implementation. One
 * page, modal-CRUD for assets, plus assign/return actions that track a
 * full check-in/check-out history per asset (AssetAssignment).
 */
class AssetController extends Controller
{
    use HandleTransactions;

    public function index(Business $business)
    {
        $page = 'Asset Management';

        return view('assets.index', compact('page', 'business'));
    }

    public function fetch(Request $request, Business $business)
    {
        $query = Asset::where('business_id', $business->id)
            ->with('currentAssignment.employee.user');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $assets = $query->orderBy('name')->get();

        return RequestResponse::ok('Assets fetched.', $assets);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_tag' => 'required|string|max:100',
            'category' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'condition' => 'nullable|in:new,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            if (Asset::where('business_id', $business->id)->where('asset_tag', $validated['asset_tag'])->exists()) {
                return RequestResponse::badRequest('An asset with that tag already exists.', [
                    'errors' => ['asset_tag' => 'An asset with that tag already exists.'],
                ]);
            }

            $asset = Asset::create($validated + ['business_id' => $business->id, 'status' => 'available']);

            return RequestResponse::created('Asset created.', $asset);
        });
    }

    public function update(Request $request, Business $business, Asset $asset)
    {
        if ((int) $asset->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Asset not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'condition' => 'nullable|in:new,good,fair,poor',
            'status' => 'nullable|in:available,maintenance,retired',
            'notes' => 'nullable|string',
        ]);

        $asset->update($validated);

        return RequestResponse::ok('Asset updated.', $asset->fresh());
    }

    public function destroy(Request $request, Business $business, Asset $asset)
    {
        if ((int) $asset->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Asset not found for this business.', 404);
        }

        if ($asset->status === 'assigned') {
            return RequestResponse::badRequest('This asset is currently assigned - return it before deleting.');
        }

        $asset->delete();

        return RequestResponse::ok('Asset deleted.');
    }

    public function assign(Request $request, Business $business, Asset $asset)
    {
        if ((int) $asset->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Asset not found for this business.', 404);
        }
        if ($asset->status !== 'available') {
            return RequestResponse::badRequest('Only an available asset can be assigned.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $business, $asset) {
            $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
            if (!$employee) {
                return RequestResponse::badRequest('Employee not found for this business.', 404);
            }

            AssetAssignment::create([
                'asset_id' => $asset->id,
                'business_id' => $business->id,
                'employee_id' => $employee->id,
                'assigned_at' => now(),
                'assigned_by_user_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $asset->update(['status' => 'assigned']);

            return RequestResponse::created('Asset assigned.', $asset->fresh());
        });
    }

    public function returnAsset(Request $request, Business $business, Asset $asset)
    {
        if ((int) $asset->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Asset not found for this business.', 404);
        }

        $assignment = $asset->currentAssignment;
        if (!$assignment) {
            return RequestResponse::badRequest('This asset is not currently assigned.');
        }

        $validated = $request->validate([
            'return_condition' => 'nullable|in:new,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $asset, $assignment) {
            $assignment->update([
                'returned_at' => now(),
                'return_condition' => $validated['return_condition'] ?? null,
                'notes' => $validated['notes'] ?? $assignment->notes,
            ]);

            $asset->update([
                'status' => 'available',
                'condition' => $validated['return_condition'] ?? $asset->condition,
            ]);

            return RequestResponse::ok('Asset returned.', $asset->fresh());
        });
    }

    /**
     * Currently-assigned assets for one employee - used by the Offboarding
     * checklist modal so HR is reminded which assets still need collecting.
     */
    public function employeeAssets(Request $request, Business $business, Employee $employee)
    {
        if ((int) $employee->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        $assignments = AssetAssignment::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->whereNull('returned_at')
            ->with('asset')
            ->get();

        return RequestResponse::ok('Assigned assets fetched.', $assignments);
    }
}

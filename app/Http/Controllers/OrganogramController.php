<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\OrganogramPosition;
use Illuminate\Http\Request;

class OrganogramController extends Controller
{
    /**
     * Display organogram page with tree data
     */
    public function index($slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        // Fetch root positions (no parent) with their hierarchy
        $positions = OrganogramPosition::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->with([
                'children',
                'holders.employee.user'
            ])
            ->orderBy('sort_order')
            ->get();

        return view('business.organogram.index', compact('business', 'positions'));
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        $slug = $request->route('business');
        $business = Business::where('slug', $slug)->firstOrFail();

        // Get all positions for parent dropdown
        $parents = OrganogramPosition::where('business_id', $business->id)
            ->orderBy('title')
            ->get();

        // Get all employees with their user relationship
        // These are from the 'employees' table
        $employees = Employee::whereNotNull('user_id')
            ->with('user')
            ->whereHas('user')  // Only include employees that have an actual user
            ->orderBy('id')
            ->get();

        \Log::info('Organogram Create - Loading Employees', [
            'count' => $employees->count(),
            'employee_ids' => $employees->pluck('id')->toArray(),
            'table' => (new Employee())->getTable(),
        ]);

        return view(
            'business.organogram.create',
            compact('business', 'parents', 'employees')
        );
    }

    /**
     * Store new position with employee holder
     */
    public function store(Request $request)
    {
        $business = Business::where('slug', $request->route('business'))->firstOrFail();

        // Validate incoming data
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'parent_id' => 'nullable|exists:organogram_positions,id',
            'employee_id' => 'nullable|exists:employees,id',  // ✅ Validate against correct table
        ]);

        // Create the organogram position
        // $position = OrganogramPosition::create([
        //     'business_id' => $business->id,
        //     'title' => $data['title'],
        //     'parent_id' => $data['parent_id'] ?? null,
        //     'level' => $this->calculateLevel($data['parent_id'] ?? null),
        //     'is_active' => 1,
        // ]);

        $name = 'Vacant';

if (!empty($data['employee_id'])) {
    $employee = Employee::with('user')->find($data['employee_id']);
    $name = $employee?->user?->name ?? 'Vacant';
}

$position = OrganogramPosition::create([
    'business_id' => $business->id,
    'title'       => $data['title'],
    'name'        => $name,
    'parent_id'   => $data['parent_id'] ?? null,
    'level'       => $this->calculateLevel($data['parent_id'] ?? null),
    'is_active'   => 1,
]);


        // Attach employee if selected
        if (!empty($data['employee_id'])) {
            // Verify the employee exists (validation already did this, but double-check)
            $employee = Employee::find($data['employee_id']);

            if ($employee) {
                // Create the holder record
                $position->holders()->create([
                    'employee_id' => $employee->id,
                    'start_date' => now(),
                    'is_primary' => 1,
                ]);

                \Log::info('Organogram Position Created with Employee', [
                    'position_id' => $position->id,
                    'position_title' => $position->title,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->user?->name,
                ]);
            }
        }

        // AJAX response for OrgChart libraries
        if ($request->expectsJson()) {
            $holder = $position->currentHolder();
            return response()->json([
                'id' => $position->id,
                'parent_id' => $position->parent_id,
                'title' => $position->title,
                'name' => $holder ? $holder->employee->user->name : 'Vacant',
                'photo' => $holder && $holder->employee->getImageUrl()
                    ? $holder->employee->getImageUrl()
                    : null,
            ]);
        }

        return redirect()
            ->route('business.organogram.index', $business->slug)
            ->with('success', 'Position added successfully');
    }

    /**
     * Show edit form
     */
    public function edit(Request $request, OrganogramPosition $position)
    {
        $slug = $request->route('business');
        $business = Business::where('slug', $slug)->firstOrFail();

        if ($position->business_id !== $business->id) {
            abort(403);
        }

        // Get positions for parent dropdown
        $parents = OrganogramPosition::where('business_id', $business->id)
            ->where('id', '!=', $position->id)
            ->orderBy('title')
            ->get();

        // Get all employees
        $employees = Employee::whereNotNull('user_id')
            ->with('user')
            ->whereHas('user')
            ->orderBy('id')
            ->get();

        // Get current holder if any
        $currentHolder = $position->currentHolder();

        return view(
            'business.organogram.edit',
            compact('business', 'position', 'parents', 'employees', 'currentHolder')
        );
    }

    /**
     * Update position
     */
    public function update(Request $request, OrganogramPosition $position)
    {
        $slug = $request->route('business');
        $business = Business::where('slug', $slug)->firstOrFail();

        if ($position->business_id !== $business->id) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:organogram_positions,id|not_in:' . $position->id,
            'employee_id' => 'nullable|exists:employees,id',  // ✅ Validate against correct table
            'sort_order' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Update position
        $position->update([
            'title' => $data['title'],
            'code' => $data['code'] ?? $position->code,
            'parent_id' => $data['parent_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? $position->sort_order,
            'description' => $data['description'] ?? $position->description,
            'is_active' => $data['is_active'] ?? $position->is_active,
            'level' => $this->calculateLevel($data['parent_id'] ?? null),
            // 'name' => $employee->user?->name ?? 'Vacant'
        ]);

        // Update employee holder if changed
        if (!empty($data['employee_id'])) {
            $employee = Employee::find($data['employee_id']);

            if ($employee) {
                $currentHolder = $position->currentHolder();

                if ($currentHolder && $currentHolder->employee_id !== $employee->id) {
                    // End the previous holder
                    $currentHolder->update(['end_date' => now()]);

                    // Create new holder
                    $position->holders()->create([
                        'employee_id' => $employee->id,
                        'start_date' => now(),
                        'is_primary' => 1,
                    ]);
                } elseif (!$currentHolder) {
                    // No existing holder, create one
                    $position->holders()->create([
                        'employee_id' => $employee->id,
                        'start_date' => now(),
                        'is_primary' => 1,
                    ]);
                }
            }
        } elseif (empty($data['employee_id'])) {
            // If employee_id is empty, end current holder
            $currentHolder = $position->currentHolder();
            if ($currentHolder) {
                $currentHolder->update(['end_date' => now()]);
            }
        }

        if ($request->expectsJson()) {
            $holder = $position->currentHolder();
            return response()->json([
                'id' => $position->id,
                'parent_id' => $position->parent_id,
                'title' => $position->title,
                'name' => $holder ? $holder->employee->user->name : 'Vacant',
                'photo' => $holder && $holder->employee->getImageUrl()
                    ? $holder->employee->getImageUrl()
                    : null,
            ]);
        }

        return redirect()
            ->route('business.organogram.index', $business->slug)
            ->with('success', 'Position updated successfully');
    }

    /**
     * Delete position
     */
    public function destroy(Request $request, OrganogramPosition $position)
    {
        $slug = $request->route('business');
        $business = Business::where('slug', $slug)->firstOrFail();

        if ($position->business_id !== $business->id) {
            abort(403);
        }

        // Prevent deletion if position has subordinates
        if ($position->children()->exists()) {
            return redirect()
                ->route('business.organogram.index', $business->slug)
                ->with('error', 'Cannot delete position with subordinate positions. Reassign or delete them first.');
        }

        // Delete all holders for this position
        $position->holders()->delete();

        // Delete the position
        $position->delete();

        return redirect()
            ->route('business.organogram.index', $business->slug)
            ->with('success', 'Position removed successfully');
    }

    /**
     * Get organogram tree as JSON
     * Used by frontend chart/org chart libraries
     */
    public function treeJson($slug)
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        // Fetch all active positions with their holders and employees
        $positions = OrganogramPosition::with([
            'holders.employee.user'
        ])
            ->where('business_id', $business->id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        // Map to format needed by org chart libraries
        return response()->json(
            $positions->map(function ($position) {
                $holder = $position->currentHolder();
                $employee = $holder?->employee;

                // Get employee name from relationship
                $name = $employee && $employee->user
                    ? $employee->user->name
                    : 'Vacant';

                // Get initials from employee name
                $initials = $employee && $employee->user
                    ? collect(explode(' ', $employee->user->name))
                        ->map(fn($n) => strtoupper($n[0] ?? ''))
                        ->filter(fn($i) => $i !== '')
                        ->join('')
                    : '—';

                // Get photo URL
                $photo = $employee
                    ? $employee->getImageUrl()
                    : null;

                return [
                    'id' => $position->id,
                    'pid' => $position->parent_id,
                    'title' => $position->title,
                    'name' => $name,
                    'photo' => $photo,
                    'initials' => $initials,
                    'level' => $position->level,
                ];
            })
        );
    }

    /**
     * Calculate hierarchy level based on parent
     */
    private function calculateLevel(?int $parentId): int
    {
        if (!$parentId) {
            return 1;
        }

        $parent = OrganogramPosition::find($parentId);

        return $parent ? $parent->level + 1 : 1;
    }
}

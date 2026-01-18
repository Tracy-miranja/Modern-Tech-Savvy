<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\OrganogramPosition;
use Illuminate\Http\Request;

class OrganogramController extends Controller
{
    /**
     * Display organogram page
     */
    public function index($slug)
{
    $business = \App\Models\Business::where('slug', $slug)->firstOrFail();

    // Fetch all positions for the parent dropdown
    $parents = $business->positions()->get();

    return view('business.organogram.index', compact('business', 'parents'));
}


    /**
     * Show create form
     */
public function create(Request $request)
{
    $slug = $request->route('business');

    $business = Business::where('slug', $slug)->firstOrFail();

    $parents = OrganogramPosition::where('business_id', $business->id)
        ->orderBy('title')
        ->get();

    $employees = \App\Models\Employee::whereNotNull('user_id')
    ->with('user')
    ->orderBy('id')
    ->get();



    return view(
        'business.organogram.create',
        compact('business', 'parents', 'employees')
    );
}


    /**
     * Store new position
     */
public function store(Request $request)
{
    $business = Business::where('slug', $request->route('business'))->firstOrFail();

    $data = $request->validate([
        'title' => 'required|string|max:150',
        'parent_id' => 'nullable|exists:organogram_positions,id',
        'user_id' => 'nullable|exists:users,id',
    ]);

    $position = OrganogramPosition::create([
        'business_id' => $business->id,
        'title' => $data['title'],
        'parent_id' => $data['parent_id'] ?? null,
        'level' => $this->calculateLevel($data['parent_id'] ?? null),
        'is_active' => 1,
    ]);

    // Attach employee if selected
 if (!empty($data['user_id'])) {
    $user = \App\Models\User::find($data['user_id']);

    // Validate employee record exists
    if (!empty($data['employee_id'])) {
    $position->holders()->create([
        'employee_id' => $data['employee_id'],
        'start_date' => now(),
        'is_primary' => 1,
    ]);
}

}


    // AJAX response for OrgChart
    if ($request->expectsJson()) {
        return response()->json([
            'id' => $position->id,
            'parent_id' => $position->parent_id,
            'title' => $position->title,
            'name' => $position->currentHolder()?->name ?? 'Vacant',
            'photo' => $position->currentHolder()?->photo
                ? asset('storage/' . $position->currentHolder()->photo)
                : null,
        ]);
    }

    return redirect()
        ->route('business.organogram.index', $business->slug)
        ->with('success', 'Position added successfully');
}



    /**
     * Edit form
     */
    public function edit(Request $request, OrganogramPosition $position)
    {
        $business = $request->route('business');

        abort_if($position->business_id !== $business->id, 403);

        $parents = OrganogramPosition::where('business_id', $business->id)
            ->where('id', '!=', $position->id)
            ->get();

        return view('business.organogram.edit', compact('business', 'position', 'parents'));
    }

    /**
     * Update position
     */
    public function update(Request $request, OrganogramPosition $position)
    {
        $business = $request->route('business');

        abort_if($position->business_id !== $business->id, 403);

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:organogram_positions,id|not_in:' . $position->id,
            'personnel_position_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data['level'] = $this->calculateLevel($data['parent_id'] ?? null);

        $position->update($data);

        return redirect()
            ->route('business.organogram.index', $business->slug)
            ->with('success', 'Position updated successfully');
    }

    /**
     * Delete position
     */
    public function destroy(Request $request, OrganogramPosition $position)
    {
        $business = $request->route('business');

        abort_if($position->business_id !== $business->id, 403);

        $position->delete();

        return back()->with('success', 'Position removed');
    }

    /**
     * JSON tree for frontend (drag & drop / chart libs)
     */
public function treeJson($slug)
{
    $business = Business::where('slug', $slug)->firstOrFail();

    $positions = OrganogramPosition::with('holders')
        ->where('business_id', $business->id)
        ->where('is_active', 1)
        ->orderBy('sort_order')
        ->get();

    return response()->json(
        $positions->map(function ($position) {
           $holder = $position->currentHolder();
$name = $holder?->employee?->user?->name ?? 'Vacant';


            return [
                'id'    => $position->id,
                'pid'   => $position->parent_id,
                'title' => $position->title,
                'name'  => $holder?->employee?->user?->name ?? 'Vacant',
'photo' => $holder?->employee?->user?->profile_photo_path
    ? asset('storage/' . $holder->employee->user->profile_photo_path)
    : null,
'initials' => $holder?->employee?->user
    ? collect(explode(' ', $holder->employee->user->name))->map(fn($n) => strtoupper($n[0]))->join('')
    : '—',

            ];
        })
    );
}


    /**
     * Calculate hierarchy level
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


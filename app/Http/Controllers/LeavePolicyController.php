<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Http\RequestResponse;
use Illuminate\Http\Request;

class LeavePolicyController extends Controller
{
    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $currentPeriod = LeavePeriod::currentlyOpenFor($business->id);
        $rangeStart = $currentPeriod ? $currentPeriod->start_date->toDateString() : now()->toDateString();
        $rangeEnd   = $currentPeriod ? $currentPeriod->end_date->toDateString() : now()->toDateString();

        $query = LeavePolicy::whereHas('leaveType', fn ($q) => $q->where('business_id', $business->id))
            ->whereDate('effective_date', '<=', $rangeEnd)
            ->where(function ($q) use ($rangeStart) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $rangeStart);
            });

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', (int) $request->input('leave_type_id'));
        }

        $policies = $query->with(['leaveType:id,name', 'department:id,name', 'jobCategory:id,name'])
            ->orderBy('leave_type_id')
            ->orderByDesc('effective_date')
            ->get();

        $html = view('leave._leave_policies_table', compact('policies', 'currentPeriod'))->render();

        return RequestResponse::ok('Leave policies fetched.', $html);
    }
}

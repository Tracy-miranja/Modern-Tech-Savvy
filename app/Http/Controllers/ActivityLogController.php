<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('No active business selected.');
        }

        $logs = $this->logsQueryFor($business)->paginate(20);

        $logs_card = view('components.activities', compact('logs'))->render();
        return RequestResponse::ok("Activity logs fetched successfully.", $logs_card);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'business_slug' => 'required|string|exists:businesses,slug',
        ]);

        $business = Business::findBySlug($request->business_slug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $logs = $this->logsQueryFor($business)->paginate(20);

        $logs_card = view('components.activities', compact('logs'))->render();

        return RequestResponse::ok("Activity logs fetched successfully.", $logs_card);
    }

    // Excludes activity caused by platform (main-business) users from client dashboards
    protected function logsQueryFor(Business $business)
    {
        $query = ActivityLog::forBusiness($business->id)->latest();

        if ($business->slug !== config('business.main_slug')) {
            $query->whereNotIn('user_id', $this->platformUserIds());
        }

        return $query;
    }

    protected function platformUserIds()
    {
        $mainBusiness = Business::findBySlug(config('business.main_slug'));
        if (!$mainBusiness) {
            return [];
        }

        return Employee::where('business_id', $mainBusiness->id)
            ->pluck('user_id')
            ->push($mainBusiness->user_id)
            ->filter()
            ->unique()
            ->all();
    }
}

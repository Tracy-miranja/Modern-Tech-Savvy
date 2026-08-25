<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeaveDelegation;
use App\Notifications\LeaveDelegationResponded;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Notification;

class LeaveDelegationController extends Controller
{
    use HandleTransactions;

    public function myDelegations(Business $business)
    {
        $employee = auth()->user()->activeEmployee();

        $delegations = ($employee && (int) $employee->business_id === (int) $business->id)
            ? LeaveDelegation::where('business_id', $business->id)
                ->where('delegate_id', $employee->id)
                ->with(['employee.user', 'leaveRequest'])
                ->latest()
                ->get()
            : collect();

        return view('organogram.my-delegations', [
            'business' => $business,
            'delegations' => $delegations,
        ]);
    }

    public function accept(Request $request, Business $business, LeaveDelegation $delegation)
    {
        return $this->handleTransaction(function () use ($business, $delegation) {
            if (!$this->authorizeDelegate($business, $delegation)) {
                return RequestResponse::badRequest('You are not the reliever for this delegation.', 403);
            }

            $delegation->delegate_accepted = true;
            $delegation->accepted_at = now();
            $delegation->declined_at = null;
            $delegation->save();

            $this->notifyRequester($delegation, 'accepted');

            return RequestResponse::ok('Delegation accepted.');
        });
    }

    public function decline(Request $request, Business $business, LeaveDelegation $delegation)
    {
        return $this->handleTransaction(function () use ($business, $delegation) {
            if (!$this->authorizeDelegate($business, $delegation)) {
                return RequestResponse::badRequest('You are not the reliever for this delegation.', 403);
            }

            $delegation->delegate_accepted = false;
            $delegation->declined_at = now();
            $delegation->save();

            $this->notifyRequester($delegation, 'declined');

            return RequestResponse::ok('Delegation declined.');
        });
    }

    private function authorizeDelegate(Business $business, LeaveDelegation $delegation): bool
    {
        $employee = auth()->user()->activeEmployee();

        return $employee
            && (int) $delegation->business_id === (int) $business->id
            && (int) $delegation->delegate_id === (int) $employee->id;
    }

    private function notifyRequester(LeaveDelegation $delegation, string $outcome): void
    {
        $requesterUser = optional($delegation->employee)->user;

        if ($requesterUser) {
            Notification::send($requesterUser, new LeaveDelegationResponded($delegation, $outcome));
        }
    }
}

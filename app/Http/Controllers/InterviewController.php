<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Enum\Status;
use App\Models\Business;
use App\Models\Interview;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use App\Notifications\InterviewScheduledNotification;

class InterviewController extends Controller
{
    use HandleTransactions;

    public function index()
    {
        $page = 'Interviews';
        return view('interviews.index', compact('page'));
    }

    public function store(Request $request)
    {
        if ($request->interview_date && $request->interview_time) {
        $request->merge([
            'scheduled_at' => $request->interview_date . ' ' . $request->interview_time . ':00'
        ]);
    }
        Log::debug($request->all());
        $validatedData = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'type' => 'required|in:phone,video,in-person',
            'scheduled_at' => 'required|date|after:now',
            'location' => 'nullable|string|required_if:type,in-person',
            'meeting_link' => 'nullable|url|required_if:type,video',
            'interviewer_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $interview = Interview::create(array_merge($validatedData, [
                'status' => 'scheduled',
                'created_by' => auth()->id(),
            ]));

            $interview->setStatus(Status::SCHEDULED);

            $application = Application::with('applicant')->findOrFail($validatedData['application_id']);
            $application->update(['stage' => 'interviewed']);

            $application->applicant->user->notify(new InterviewScheduledNotification($interview));
            if ($interview->interviewer) {
                $interview->interviewer->notify(new InterviewScheduledNotification($interview));
            }

            return RequestResponse::created('Interview scheduled successfully.');
        });
    }

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

        $interviews = Interview::whereHas('jobApplication', function ($query) use ($business) {
            $query->where('business_id', $business->id);
        })->with('jobApplication.applicant.user', 'interviewer')->latest()->paginate(10);

        $interviews_table = view('interviews._table', compact('interviews'))->render();

        return RequestResponse::ok('Interviews fetched successfully.', $interviews_table);
    }

    public function show($id)
    {
        $interview = Interview::with('jobApplication.applicant.user', 'interviewer')->findOrFail($id);
        return RequestResponse::ok('Interview details fetched successfully.', $interview);
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
        ]);

        $interview = Interview::with('jobApplication.applicant.user')->findOrFail($validatedData['interview_id']);
        $interview_form = view('interviews._form', compact('interview'))->render();
        return RequestResponse::ok('Ok', $interview_form);
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
            'type' => 'required|in:phone,video,in-person',
            'scheduled_at' => 'required|date|after:now',
            'location' => 'nullable|string|required_if:type,in-person',
            'meeting_link' => 'nullable|url|required_if:type,video',
            'interviewer_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $interview = Interview::findOrFail($validatedData['interview_id']);
            $oldScheduledAt = $interview->scheduled_at;

            $interview->update([
                'type' => $validatedData['type'],
                'scheduled_at' => $validatedData['scheduled_at'],
                'location' => $validatedData['location'],
                'meeting_link' => $validatedData['meeting_link'],
                'interviewer_id' => $validatedData['interviewer_id'],
                'notes' => $validatedData['notes'],
            ]);

            if ($oldScheduledAt != $validatedData['scheduled_at']) {
                $interview->reschedule($validatedData['scheduled_at']);
            }

            return RequestResponse::ok('Interview updated successfully.', $interview);
        });
    }

    public function reschedule(Request $request)
    {
        $validatedData = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $interview = Interview::findOrFail($validatedData['interview_id']);
            $interview->reschedule($validatedData['scheduled_at']);
            return RequestResponse::ok('Interview rescheduled successfully.', $interview);
        });
    }

    public function cancel(Request $request)
    {
        $validatedData = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
            'reason' => 'required|string',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $interview = Interview::findOrFail($validatedData['interview_id']);
            $interview->cancel($validatedData['reason']);
            return RequestResponse::ok('Interview canceled successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $validatedData = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $interview = Interview::findOrFail($validatedData['interview_id']);
            $interview->delete();
            return RequestResponse::ok('Interview deleted successfully.');
        });
    }
}

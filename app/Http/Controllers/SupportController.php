<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SupportIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function index($businessSlug)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        return view('support.index', compact('business'));
    }

    public function fetch(Request $request, $businessSlug)
    {
        $business = Business::findBySlug($businessSlug);
        $issues = SupportIssue::where('business_id', $business->id)
            ->with(['user', 'solvedBy'])
            ->get();

        return response()->json([
            'data' => $issues->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'status' => $issue->status,
                    'screenshot' => $issue->screenshot_path ? Storage::url($issue->screenshot_path) : null,
                    'solved_by' => $issue->solvedBy ? $issue->solvedBy->name : 'N/A',
                    'can_mark_solved' => Auth::user()->hasRole('business-admin'),
                ];
            })
        ]);
    }

    public function store(Request $request, $businessSlug)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $business = Business::findBySlug($businessSlug);

        $issue = new SupportIssue();
        $issue->user_id = Auth::id();
        $issue->business_id = $business->id;
        $issue->title = $request->title;
        $issue->description = $request->description;
        $issue->status = 'open';

        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store('screenshots', 'public');
            $issue->screenshot_path = $path;
        }

        $issue->save();

        return response()->json(['message' => 'Issue submitted successfully']);
    }

    public function markSolved(Request $request, $businessSlug, $issueId)
    {
        $this->authorize('markSolved', SupportIssue::class);

        $issue = SupportIssue::findOrFail($issueId);
        $issue->status = 'solved';
        $issue->solved_by_id = Auth::id();
        $issue->solved_at = now();
        $issue->save();

        return response()->json(['message' => 'Issue marked as solved']);
    }
}

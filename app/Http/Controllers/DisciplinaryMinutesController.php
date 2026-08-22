<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\DisciplinaryMinutes;
use App\Models\Warning;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * hasMany per case (see DisciplinaryMinutes migration) - a case can have
 * multiple hearing sessions.
 */
class DisciplinaryMinutesController extends Controller
{
    use HandleTransactions;

    private function ownedWarning(int $warningId): ?Warning
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return null;
        }

        return Warning::where('business_id', $business->id)->find($warningId);
    }

    public function store(Request $request, int $warningId)
    {
        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'attendees' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
        ]);

        return $this->handleTransaction(function () use ($validated, $request, $warningId) {
            $warning = $this->ownedWarning($warningId);
            if (!$warning) {
                return RequestResponse::badRequest('Disciplinary case not found for this business.', 404);
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('disciplinary', 'public');
            }

            $minutes = DisciplinaryMinutes::create([
                'warning_id' => $warning->id,
                'business_id' => $warning->business_id,
                'meeting_date' => $validated['meeting_date'],
                'attendees' => $validated['attendees'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'attachment' => $attachmentPath,
            ]);

            return RequestResponse::created('Minutes recorded.', $minutes);
        });
    }

    public function update(Request $request, int $warningId, DisciplinaryMinutes $minute)
    {
        $warning = $this->ownedWarning($warningId);
        if (!$warning || (int) $minute->warning_id !== (int) $warning->id) {
            return RequestResponse::badRequest('Minutes not found for this case.', 404);
        }

        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'attendees' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated, $minute) {
            $minute->update($validated);

            return RequestResponse::ok('Minutes updated.', $minute->fresh());
        });
    }

    public function destroy(Request $request, int $warningId, DisciplinaryMinutes $minute)
    {
        $warning = $this->ownedWarning($warningId);
        if (!$warning || (int) $minute->warning_id !== (int) $warning->id) {
            return RequestResponse::badRequest('Minutes not found for this case.', 404);
        }

        $minute->delete();

        return RequestResponse::ok('Minutes removed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\DisciplinaryInvestigation;
use App\Models\Warning;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

class DisciplinaryInvestigationController extends Controller
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
            'investigator_id' => 'nullable|integer|exists:employees,id',
            'started_at' => 'nullable|date',
            'concluded_at' => 'nullable|date|after_or_equal:started_at',
            'findings' => 'nullable|string',
            'outcome' => 'nullable|string|max:100',
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

            $investigation = DisciplinaryInvestigation::create([
                'warning_id' => $warning->id,
                'business_id' => $warning->business_id,
                'investigator_id' => $validated['investigator_id'] ?? null,
                'started_at' => $validated['started_at'] ?? null,
                'concluded_at' => $validated['concluded_at'] ?? null,
                'findings' => $validated['findings'] ?? null,
                'outcome' => $validated['outcome'] ?? null,
                'attachment' => $attachmentPath,
            ]);

            return RequestResponse::created('Investigation recorded.', $investigation->load('investigator.user'));
        });
    }

    public function update(Request $request, int $warningId, DisciplinaryInvestigation $investigation)
    {
        $warning = $this->ownedWarning($warningId);
        if (!$warning || (int) $investigation->warning_id !== (int) $warning->id) {
            return RequestResponse::badRequest('Investigation not found for this case.', 404);
        }

        $validated = $request->validate([
            'investigator_id' => 'nullable|integer|exists:employees,id',
            'started_at' => 'nullable|date',
            'concluded_at' => 'nullable|date|after_or_equal:started_at',
            'findings' => 'nullable|string',
            'outcome' => 'nullable|string|max:100',
        ]);

        return $this->handleTransaction(function () use ($validated, $investigation) {
            $investigation->update($validated);

            return RequestResponse::ok('Investigation updated.', $investigation->fresh());
        });
    }

    public function destroy(Request $request, int $warningId, DisciplinaryInvestigation $investigation)
    {
        $warning = $this->ownedWarning($warningId);
        if (!$warning || (int) $investigation->warning_id !== (int) $warning->id) {
            return RequestResponse::badRequest('Investigation not found for this case.', 404);
        }

        $investigation->delete();

        return RequestResponse::ok('Investigation removed.');
    }
}

<div class="row g-3">
    <div class="col-6"><strong>Case ID:</strong> {{ $warning->case_id }}</div>
    <div class="col-6"><strong>Employee:</strong> {{ $warning->employee->full_name ?? '—' }}</div>
    <div class="col-6"><strong>Category:</strong> {{ \App\Models\Warning::label($warning->category) }}</div>
    <div class="col-6"><strong>Stage:</strong> {{ \App\Models\Warning::label($warning->stage) }}</div>
    <div class="col-12"><strong>Offence:</strong><br>{{ $warning->offence }}</div>
    <div class="col-6"><strong>Reported By:</strong> {{ $warning->reported_by_name ?? '—' }}</div>
    <div class="col-6"><strong>Reported On:</strong> {{ $warning->issue_date?->format('Y-m-d') }}</div>
    <div class="col-6"><strong>Hearing Date:</strong> {{ $warning->hearing_date?->format('Y-m-d') ?? '—' }}</div>
    <div class="col-6"><strong>Decision Outcome:</strong> {{ \App\Models\Warning::label($warning->decision_outcome) }}</div>
    <div class="col-6"><strong>Appeal Status:</strong> {{ $warning->appeal_status ? \App\Models\Warning::label($warning->appeal_status) : '—' }}</div>
    <div class="col-12"><strong>Notes:</strong><br>{{ $warning->description ?: '—' }}</div>
</div>
<div class="mt-4 text-end">
    <button type="button" class="btn btn-light" onclick="closeWarningModal()">Close</button>
</div>

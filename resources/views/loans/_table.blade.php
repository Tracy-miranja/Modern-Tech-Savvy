<table class="table table-striped table-bordered" id="loansTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Employee Code</th>
            <th>Employee Name</th>
            <th>Loan Amount</th>
            <th>Interest Rate (%)</th>
            <th>Term (Months)</th>
            <th>Start Date</th>
            <th>Amount Repaid</th>
            <th>Balance Remaining</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($loans as $key => $loan)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $loan->employee->employee_code ?? 'N/A' }}</td>
                <td>{{ $loan->employee->user->name ?? 'N/A' }}</td>
                <td>{{ number_format($loan->amount, 2) }}</td>
                <td>{{ number_format($loan->interest_rate, 2) }}</td>
                <td>{{ $loan->term_months }}</td>
                <td>{{ $loan->start_date ? \Carbon\Carbon::parse($loan->start_date)->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ number_format($loan->repayments()->sum('amount_paid'), 2) }}</td>
                <td>{{ number_format($loan->amount - $loan->repayments()->sum('amount_paid'), 2) }}</td>
                <td>{{ ucfirst($loan->status) }}</td>
                <td>{{ $loan->notes ?? 'N/A' }}</td>
                <td>
                    <div style="display:flex; gap: 2px">
                        <button type="button" class="btn btn-primary btn-sm" data-loan="{{ $loan->id }}" onclick="editLoan(this)">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" data-loan="{{ $loan->id }}" onclick="deleteLoan(this)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-loan="{{ $loan->id }}" onclick="viewLoan(this)">
                            <i class="bi bi-view-list"></i> Repayments
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

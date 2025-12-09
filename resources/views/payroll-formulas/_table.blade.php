<div id="formulasTable" class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="bg-light">
            <tr>
                <th scope="col" class="text-dark fw-semibold">Name</th>
                <th scope="col" class="text-dark fw-semibold">Country</th>
                <th scope="col" class="text-dark fw-semibold">Type</th>
                <th scope="col" class="text-dark fw-semibold">Basis</th>
                <th scope="col" class="text-dark fw-semibold">Statutory</th>
                <th scope="col" class="text-dark fw-semibold">Progressive</th>
                <th scope="col" class="text-dark fw-semibold">Amount</th>
                <th scope="col" class="text-dark fw-semibold">Limit</th>
                <th scope="col" class="text-dark fw-semibold">Round Off</th>
                <th scope="col" class="text-dark fw-semibold">Applies To</th>
                @if($business->slug === 'krest')
                <th scope="col" class="text-dark fw-semibold text-end">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($formulas as $formula)
            <tr>
                <td>{{ $formula->name }}</td>
                <td>{{ $formula->country }}</td>
                <td>{{ ucfirst($formula->formula_type) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $formula->calculation_basis)) }}</td>
                <td>{{ $formula->is_statutory ? 'Yes' : 'No' }}</td>
                <td>{{ $formula->is_progressive ? 'Yes' : 'No' }}</td>
                <td>{{ is_null($formula->minimum_amount) || $formula->minimum_amount == 0 ? 'N/A' : number_format($formula->minimum_amount, 2) }}
                </td>
                <td>{{ $formula->limit ? number_format($formula->limit, 2) : 'N/A' }}</td>
                <td>{{ $formula->round_off ? ucwords(str_replace('_', ' ', $formula->round_off)) : 'None' }}</td>
                <td>{{ ucfirst($formula->applies_to) }}</td>
                <td class="text-end">
                    <div class="btn-group" role="group">
                        @if($business->slug === 'krest')
                        <button class="btn btn-sm btn-outline-warning me-2" data-formula="{{ $formula->id }}"
                            onclick="editFormula(this)">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-formula="{{ $formula->id }}"
                            onclick="deleteFormula(this)" {{ $formula->is_statutory ? 'disabled' : '' }}>
                            <i class="fa fa-trash"></i> Delete
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i> No payroll formulas defined yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

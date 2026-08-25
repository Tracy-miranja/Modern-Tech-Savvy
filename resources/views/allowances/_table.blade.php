@php
    $isTanzaniaBusiness = isset($business) && in_array(strtoupper(trim($business->country ?? '')), ['TANZANIA', 'TZ']);
    $columnCount = $isTanzaniaBusiness ? 9 : 8;
@endphp
<div id="allowancesTable" class="table-responsive">
    <table class="table table-hover table-bordered align-middle">
        <thead class="bg-light">
            <tr>
                <th scope="col" class="text-dark fw-semibold">Name</th>
                <th scope="col" class="text-dark fw-semibold">Type</th>
                <th scope="col" class="text-dark fw-semibold">Basis</th>
                <th scope="col" class="text-dark fw-semibold">Amount/Rate</th>
                <th scope="col" class="text-dark fw-semibold">Taxable</th>
                <th scope="col" class="text-dark fw-semibold">NSSF</th>
                @if ($isTanzaniaBusiness)
                <th scope="col" class="text-dark fw-semibold">SDL</th>
                @endif
                <th scope="col" class="text-dark fw-semibold">Applies To</th>
                <th scope="col" class="text-dark fw-semibold text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allowances as $allowance)
            <tr>
                <td>{{ $allowance->name }}</td>
                <td>{{ ucfirst($allowance->type) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $allowance->calculation_basis)) }}</td>
                <td>{{ $allowance->type === 'fixed' ? number_format($allowance->amount, 2) : $allowance->rate . '%' }}
                </td>
                <td>{{ $allowance->is_taxable ? 'Yes' : 'No' }}</td>
                <td>{{ $allowance->is_nssf_applicable ? 'Yes' : 'No' }}</td>
                @if ($isTanzaniaBusiness)
                <td>{{ $allowance->is_sdl_applicable ? 'Yes' : 'No' }}</td>
                @endif
                <td>{{ ucfirst($allowance->applies_to) }}</td>
                <td class="text-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-warning me-2" data-allowance="{{ $allowance->id }}"
                            onclick="editAllowance(this)">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-allowance="{{ $allowance->id }}"
                            onclick="deleteAllowance(this)">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $columnCount }}" class="text-center text-muted py-4">
                    <i class="fa fa-info-circle me-2"></i> No allowances defined yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
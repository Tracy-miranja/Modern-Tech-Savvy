<table id="clientsTable" class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Industry</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($businesses as $biz)
        <tr>
            <td>
                <a href="{{ route('business.clients.view', [session('active_business_slug'), $biz->slug]) }}">
                    {{ $biz->company_name }}
                </a>
            </td>
            <td>{{ $biz->industry ?? 'N/A' }}</td>
            <td>
                @if (is_null($biz->verified))
                <span class="badge badge-secondary">Unknown</span>
                @elseif ($biz->verified)
                <span class="badge badge-success">Verified</span>
                @else
                <span class="badge badge-warning">Pending</span>
                @endif
            </td>
            <td>
                @if (auth()->user()->hasRole('super-admin'))
                    @if (!$biz->verified)
                    <button class="btn btn-sm btn-success" onclick="verifyBusiness(this, '{{ $biz->slug }}')">
                        Verify
                    </button>
                    @else
                    <button class="btn btn-sm btn-danger" onclick="deactivateBusiness(this, '{{ $biz->slug }}')">
                        Deactivate
                    </button>
                    @endif
                @endif
                <button class="btn btn-sm btn-info" onclick="impersonateBusiness('{{ $biz->slug }}')">
                    Impersonate
                </button>
            </td>
        </tr>
        <!-- Remarks Modal for Verify/Deactivate -->
        <div class="modal fade" id="remarksModal-{{ $biz->slug }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Remarks</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control" id="remarks-{{ $biz->slug }}" rows="4"
                            placeholder="Enter remarks"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary"
                            onclick="submitRemarks('{{ $biz->slug }}')">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <tr>
            <td colspan="4">No businesses found.</td>
        </tr>
        @endforelse
    </tbody>
</table>
{{ $businesses->links() }}

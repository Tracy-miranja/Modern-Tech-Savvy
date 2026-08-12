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
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modulesModal-{{ $biz->slug }}">
                        Assign Modules
                    </button>
                @endif
                <button class="btn btn-sm btn-info" onclick="impersonateBusiness('{{ $biz->slug }}')">
                  Switch to Business
                </button>
            </td>
        </tr>
        <!-- Remarks Modal for Verify/Deactivate -->
        <div class="modal fade" id="remarksModal-{{ $biz->slug }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Remarks</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <textarea class="form-control" id="remarks-{{ $biz->slug }}" rows="4"
                            placeholder="Enter remarks"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary"
                            onclick="submitRemarks('{{ $biz->slug }}')">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modules Modal -->
        <div class="modal fade" id="modulesModal-{{ $biz->slug }}" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Modules for {{ $biz->company_name }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="modulesForm-{{ $biz->slug }}">
                            @csrf
                            <input type="hidden" name="business_slug" value="{{ $biz->slug }}">
                            @foreach (\App\Models\Module::all() as $module)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="modules[]"
                                    value="{{ $module->id }}" @if ($biz->modules->contains($module->id)) checked
                                @endif>
                                <label class="form-check-label">{{ $module->name }}</label>
                            </div>
                            @endforeach
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary"
                            onclick="assignModules(this, '{{ $biz->slug }}')">Save</button>
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

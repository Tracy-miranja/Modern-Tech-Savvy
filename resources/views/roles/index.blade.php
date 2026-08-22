<x-app-layout title="Roles Management">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between text-white">
                    <h5 class="mb-0">Roles Management</h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openNewRoleModal()">
                        <i class="bi bi-plus-circle me-1"></i> New Custom Role
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        The roles below are the platform's built-in set. Create your own custom role to grant
                        exactly the module access your business needs - each custom role can View, Create, Edit,
                        Delete, and/or Approve within whichever modules you choose.
                    </p>
                    <div class="mb-3">
                        <input type="text" id="roleFilter" class="form-control" placeholder="Filter by name...">
                    </div>
                    <div id="rolesContainer">
                        {{ loader() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New/Edit Custom Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">New Custom Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" name="role_id" id="roleFormId">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" name="display_name" id="roleFormDisplayName" class="form-control" placeholder="e.g. Leave Approver" required>
                        </div>
                        <label class="form-label mb-1">Module Permissions</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="roleModulesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Module</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Create</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                        <th class="text-center">Approve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="text-center text-muted">Loading modules…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Modules your business hasn't subscribed to are shown locked and can't be granted.</small>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitRoleBtn" onclick="saveRole(this)">Save</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/roles.js') }}" type="module"></script>
    <script>
    $(document).ready(() => getRoles());
    </script>
    @endpush
</x-app-layout>

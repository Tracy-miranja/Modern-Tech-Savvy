<x-app-layout title="Platform Admins">
    <div class="container py-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                <h2 class="mb-0 fw-bold text-dark">Platform Admins</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlatformAdminModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Add Platform Admin
                </button>
            </div>
            <div class="card-body p-4">
                <p class="text-muted">
                    Platform admins can browse and impersonate client businesses, and operate this
                    business exactly like a business-admin would.
                </p>

                <table class="table table-bordered align-middle" id="platformAdminsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($platformAdmins as $admin)
                        <tr id="platform-admin-row-{{ $admin->id }}">
                            <td>{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger" onclick="revokePlatformAdmin({{ $admin->id }}, '{{ $admin->email }}')">
                                    Revoke
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="platform-admins-empty-row">
                            <td colspan="3" class="text-center text-muted">No platform admins yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Platform Admin Modal -->
    <div class="modal fade" id="createPlatformAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Platform Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createPlatformAdminForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                            <small class="text-muted">Must be able to receive email - platform admins require 2FA on login, and a new account gets a "set your password" email sent here.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitCreatePlatformAdminBtn">
                        <i class="bi bi-check-circle me-1"></i> Create
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.currentBusinessSlug = "{{ $business->slug }}";
    </script>
    <script src="{{ asset('js/main/platform-admins.js') }}" type="module"></script>
    @endpush
</x-app-layout>

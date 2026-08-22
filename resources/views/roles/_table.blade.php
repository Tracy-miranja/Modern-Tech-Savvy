<table class="table table-hover table-striped" id="rolesTable">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($roles as $index => $role)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $role->display_name }}</td>
            <td>
                @if ($role->is_custom)
                    <span class="badge bg-primary">Custom</span>
                @else
                    <span class="badge bg-secondary">Built-in</span>
                @endif
            </td>
            <td>{{ $role->created_at->format('d M Y') }}</td>
            <td>
                <div class="btn-group" role="group">
                    <a href="{{ route('business.roles.show', ['business' => $businessSlug, 'role' => urlencode($role->name)]) }}"
                        class="btn btn-info btn-sm">
                        <i class="bi bi-eye"></i> View
                    </a>
                    @if ($role->is_custom)
                        <button type="button" class="btn btn-warning btn-sm" onclick="editRole(this)" data-id="{{ $role->id }}">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteRole(this)" data-id="{{ $role->id }}">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

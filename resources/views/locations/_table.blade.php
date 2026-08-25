<table class="table table-striped table-hover" id="locationsTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Country</th>
            <th>Coordinates</th>
            <th>Company Size</th>

            <th>Physical Address</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($locations as $index => $location)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $location->name }}</td>
                <td>{{ $location->country ?: '—' }}</td>
                <td>
                    @if(!empty($location->latitude) && !empty($location->longitude))
                        {{ $location->latitude }}, {{ $location->longitude }}
                        <small class="text-muted">({{ $location->radius_m ?? 150 }}m)</small>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $location->company_size }}</td>

                <td>{{ $location->physical_address }}</td>
                <td>{{ \Carbon\Carbon::parse($location->created_at)->diffForHumans() }}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-primary view-location" data-location="{{ $location->slug }}"
                            data-bs-toggle="modal" data-bs-target="#locationDetailsModal" data-bs-toggle="tooltip"
                            title="View Location">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-info edit-location" onclick="editLocation(this)"
                            data-location="{{ $location->slug }}" data-bs-toggle="tooltip" title="Edit Location">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger delete-location" onclick="deleteLocation(this)"
                            data-location="{{ $location->slug }}" data-bs-toggle="tooltip" title="Delete Location">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

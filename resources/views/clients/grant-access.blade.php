<x-app-layout>
    <div class="container">
        @include('clients._access_nav')
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3>Grant Access</h3>
                        <p>Review and approve access requests for your business.</p>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if ($requests->isEmpty())
                        <p>No pending access requests.</p>
                        @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Requester</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requests as $request)
                                <tr>
                                    <td>{{ $request->requester->name ?? 'N/A' }}</td>
                                    <td>{{ $request->email }}</td>
                                    <td>
                                        <select class="form-control" id="role-{{ $request->id }}">
                                            <option value="business-admin">Business Admin</option>
                                            <option value="business-employee">Business Employee</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success"
                                            onclick="grantAccess(this, {{ $request->id }})">
                                            Approve
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('js/main/clients.js') }}"></script>
    @endpush
</x-app-layout>
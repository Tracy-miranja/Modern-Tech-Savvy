<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <h4 class="mb-4">My Profile</h4>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="">
                @csrf
                @method('PUT')

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white text-white">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', Auth::user()->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', Auth::user()->email) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ old('phone', Auth::user()->phone) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control"
                                value="{{ old('address', Auth::user()->address) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control"
                                value="{{ old('dob', Auth::user()->dob) }}">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white text-white">
                        <h5 class="mb-0">Employment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" value="{{ Auth::user()->employee_id }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control"
                                value="{{ old('department', Auth::user()->department) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control"
                                value="{{ old('position', Auth::user()->position) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hire Date</label>
                            <input type="date" name="hire_date" class="form-control"
                                value="{{ old('hire_date', Auth::user()->hire_date) }}">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white text-white">
                        <h5 class="mb-0">Banking Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control"
                                value="{{ old('bank_name', Auth::user()->bank_name) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="bank_account" class="form-control"
                                value="{{ old('bank_account', Auth::user()->bank_account) }}">
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white text-white">
                        <h5 class="mb-0">Emergency Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control"
                                value="{{ old('emergency_contact_name', Auth::user()->emergency_contact_name) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_contact_relationship" class="form-control"
                                value="{{ old('emergency_contact_relationship', Auth::user()->emergency_contact_relationship) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" class="form-control"
                                value="{{ old('emergency_contact_phone', Auth::user()->emergency_contact_phone) }}">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
            </form>
        </div>
    </div>
</div>

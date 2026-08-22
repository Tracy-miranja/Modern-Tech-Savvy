<x-app-layout>
    @include('components.phone-input-assets')
    <form id="employeeUpdateForm" method="post" enctype="multipart/form-data" action="/employee/update-profile">
        <!-- Assuming Laravel, adjust action URL as needed -->

        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-title"> <i class="bi bi-person"></i> Bio Information</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="last_name">Surname</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required
                            placeholder="Last Name" value="{{ $employee->last_name ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required
                            placeholder="First Name" value="{{ $employee->first_name ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name"
                            placeholder="Middle Name" value="{{ $employee->middle_name ?? '' }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="gender">Gender</label>
                        <select name="gender" id="gender" class="form-select">
                            <option value="male" {{ $employee->gender == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ $employee->gender == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                            placeholder="Personal e-mail Address" value="{{ $employee->email ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="phone0">Phone</label>
                        <input type="text" class="phone-input-control" id="phone0" name="phone" required
                            value="{{ $employee->phone ?? '' }}">
                        <input type="text" hidden id="code0" name="phone_code"
                            value="{{ $employee->phone_code ?? '' }}">
                        <input type="text" hidden id="country0" name="phone_country"
                            value="{{ $employee->phone_country ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="phone1">Alternate Phone No.</label>
                        <input type="text" class="phone-input-control" id="phone1" name="alternate_phone"
                            value="{{ $employee->alternate_phone ?? '' }}">
                        <input type="text" hidden id="code1" name="alternate_phone_code"
                            value="{{ $employee->alternate_phone_code ?? '' }}">
                        <input type="text" hidden id="country1" name="alternate_phone_country"
                            value="{{ $employee->alternate_phone_country ?? '' }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="text" class="form-control datepicker" id="date_of_birth" name="date_of_birth"
                            required placeholder="Date of Birth" value="{{ $employee->date_of_birth ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="place_of_birth">Place of Birth</label>
                        <input type="text" class="form-control" id="place_of_birth" name="place_of_birth"
                            placeholder="Place of Birth" value="{{ $employee->place_of_birth ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="marital_status">Marital Status</label>
                        <select name="marital_status" id="marital_status" class="form-select">
                            <option value="single" {{ $employee->marital_status == 'single' ? 'selected' : '' }}>Single
                            </option>
                            <option value="married" {{ $employee->marital_status == 'married' ? 'selected' : '' }}>
                                Married
                            </option>
                            <option value="divorced" {{ $employee->marital_status == 'divorced' ? 'selected' : '' }}>
                                Divorced</option>
                            <option value="widowed" {{ $employee->marital_status == 'widowed' ? 'selected' : '' }}>
                                Widowed
                            </option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="address">Residential Address</label>
                        <input type="text" class="form-control" id="address" name="address" required
                            placeholder="Residential Address" value="{{ $employee->address ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="permanent_address">Permanent Residential Address</label>
                        <input type="text" class="form-control" id="permanent_address" name="permanent_address"
                            placeholder="Permanent Residential Address"
                            value="{{ $employee->permanent_address ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="blood_group">Blood Group</label>
                        <select name="blood_group" id="blood_group" class="form-select">
                            <option value="" disabled>Select Blood Group</option>
                            <option value="A+" {{ $employee->blood_group == 'A+' ? 'selected' : '' }}>A+</option>
                            <option value="A-" {{ $employee->blood_group == 'A-' ? 'selected' : '' }}>A-</option>
                            <option value="B+" {{ $employee->blood_group == 'B+' ? 'selected' : '' }}>B+</option>
                            <option value="B-" {{ $employee->blood_group == 'B-' ? 'selected' : '' }}>B-</option>
                            <option value="AB+" {{ $employee->blood_group == 'AB+' ? 'selected' : '' }}>AB+</option>
                            <option value="AB-" {{ $employee->blood_group == 'AB-' ? 'selected' : '' }}>AB-</option>
                            <option value="O+" {{ $employee->blood_group == 'O+' ? 'selected' : '' }}>O+</option>
                            <option value="O-" {{ $employee->blood_group == 'O-' ? 'selected' : '' }}>O-</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="avatar">Profile Picture</label>
                        <input type="file" class="form-control" id="avatar" name="avatar">
                        <small>Leave empty to keep current picture</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-title"> <i class="bi bi-person-lines-fill"></i> Contact Information</h4>
            </div>
            <div class="card-body">
                <h5 class="mb-2">Spouse Details (If Married)</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="spouse_surname_name">Surname</label>
                        <input type="text" class="form-control" id="spouse_surname_name" name="spouse_surname_name"
                            placeholder="Surname" value="{{ $employee->spouse_surname_name ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="spouse_first_name">First Name</label>
                        <input type="text" class="form-control" id="spouse_first_name" name="spouse_first_name"
                            placeholder="First Name" value="{{ $employee->spouse_first_name ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="spouse_middle_name">Middle Name</label>
                        <input type="text" class="form-control" id="spouse_middle_name" name="spouse_middle_name"
                            placeholder="Middle Name" value="{{ $employee->spouse_middle_name ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="spouse_date_of_birth">Date of Birth</label>
                        <input type="text" class="form-control datepicker" id="spouse_date_of_birth"
                            name="spouse_date_of_birth" placeholder="Date of Birth"
                            value="{{ $employee->spouse_date_of_birth ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="phone2">Contact Phone</label>
                        <input type="text" class="phone-input-control" id="phone2" name="spouse_phone"
                            value="{{ $employee->spouse_phone ?? '' }}">
                        <input type="text" hidden id="code2" name="spouse_phone_code"
                            value="{{ $employee->spouse_phone_code ?? '' }}">
                        <input type="text" hidden id="country2" name="spouse_phone_country"
                            value="{{ $employee->spouse_phone_country ?? '' }}">
                    </div>
                </div>

                <h5 class="mb-2">Emergency Contact</h5>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="emmergency_contact_name_1">Name</label>
                        <input type="text" class="form-control" id="emmergency_contact_name_1"
                            name="emmergency_contact_name[]" required placeholder="Name"
                            value="{{ $employee->emergency_contacts[0]->name ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="emmergency_contact_relationship_1">Relationship</label>
                        <input type="text" class="form-control" id="emmergency_contact_relationship_1"
                            name="emmergency_contact_relationship[]" required
                            placeholder="e.g. Father / Wife / Brother etc"
                            value="{{ $employee->emergency_contacts[0]->relationship ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="phone3">Contact Phone</label>
                        <input type="text" class="phone-input-control" id="phone3" name="emmergency_contact_phone[]"
                            required value="{{ $employee->emergency_contacts[0]->phone ?? '' }}">
                        <input type="text" hidden id="code3" name="emmergency_contact_phone_code[]"
                            value="{{ $employee->emergency_contacts[0]->phone_code ?? '' }}">
                        <input type="text" hidden id="country3" name="emmergency_contact_phone_country[]"
                            value="{{ $employee->emergency_contacts[0]->phone_country ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100"> <i class="bi bi-check-circle"></i> Update
                    Profile</button>
            </div>
        </div>
    </form>
</x-app-layout>

<script>
    // Add your JavaScript for datepicker, phone input, etc., as needed
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize datepickers
        $('.datepicker').datepicker();
        // Initialize phone input controls if using a library like intl-tel-input
    });
</script>
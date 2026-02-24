<div class="table-responsive">
    <table id="employeesTable" class="table table-hover table-bordered w-100">
        <thead class="bg-light">
            <tr>
                <th>Name</th>
                <th>Code</th>
                <th>Department</th>
                <th>Job Category</th>
                <th>Location</th>
                <th>Monthly Salary</th>
                <th>Hourly Rate</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {{-- DataTables fills this via Ajax from /employees/fetch --}}
            <tr id="loadingRow" style="display: none;">
                <td colspan="8" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

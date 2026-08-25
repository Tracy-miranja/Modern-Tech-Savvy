<!DOCTYPE html>
<html>

<head>
    <title>Roster Report</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        margin: 0;
        padding: 20px;
    }

    .container {
        width: 100%;
    }

    h1 {
        text-align: center;
        color: #f89616;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #dee2e6;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .filters {
        margin-bottom: 20px;
    }

    .filters ul {
        list-style: none;
        padding: 0;
    }

    .filters li {
        margin-bottom: 5px;
    }

    .footer {
        margin-top: 20px;
        text-align: right;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Roster Report</h1>
        <div class="filters">
            <strong>Filters Applied:</strong>
            <ul>
                <li>Department:
                    {{ $filters['department_id'] ? App\Models\Department::find($filters['department_id'])->name : 'All' }}
                </li>
                <li>Job Category:
                    {{ $filters['job_category_id'] ? App\Models\JobCategory::find($filters['job_category_id'])->name : 'All' }}
                </li>
                <li>Location:
                    {{ $filters['location_id'] ? App\Models\Location::find($filters['location_id'])->name : 'All' }}
                </li>
                <li>Employee:
                    {{ $filters['employee_id'] ? App\Models\Employee::find($filters['employee_id'])->user->first_name . ' ' . App\Models\Employee::find($filters['employee_id'])->user->last_name : 'All' }}
                </li>
            </ul>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Roster</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Job Category</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Shift</th>
                    <th>Leave</th>
                    <th>Overtime (Hrs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rosters as $roster)
                @foreach ($roster->assignments as $assignment)
                <tr>
                    <td>{{ $roster->name }}</td>
                    <td>{{ $assignment->employee->user->first_name }} {{ $assignment->employee->user->last_name }}</td>
                    <td>{{ $assignment->department->name }}</td>
                    <td>{{ $assignment->jobCategory->name }}</td>
                    <td>{{ $assignment->location->name }}</td>
                    <td>{{ $assignment->date->format('Y-m-d') }}</td>
                    <td>{{ $assignment->shift ? $assignment->shift->name : 'N/A' }}</td>
                    <td>{{ $assignment->leave ? $assignment->leave->name : 'N/A' }}</td>
                    <td>{{ number_format($assignment->overtime_hours, 2) }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="8" style="text-align: right;">Total Overtime Hours:</th>
                    <th>{{ number_format($rosters->flatMap->assignments->sum('overtime_hours'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
        <div class="footer">
            Generated on {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</body>

</html>
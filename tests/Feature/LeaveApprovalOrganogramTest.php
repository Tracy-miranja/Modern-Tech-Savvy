<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for leave-approval routing now being enforced by the
 * organogram (Employee.manager_id chain) instead of bare role assignment.
 * Being tagged "head-of-department" no longer grants approval rights on
 * its own - the acting employee must actually be upstream of the requester
 * in the real reporting chain. HR/admin and chief-of-staff
 * (a genuine cross-departmental pivot assignment, not just a role label)
 * remain as standing administrative overrides.
 */
class LeaveApprovalOrganogramTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'hrmamsol',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);

        DB::purge('mysql');
        DB::connection('mysql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployee(?int $managerId = null, ?int $departmentId = 1): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'manager_id' => $managerId,
            'employee_code' => 'LAOT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    private function makeLeaveRequest(Employee $employee): LeaveRequest
    {
        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Organogram Approval Leave ' . uniqid(),
        ]);

        return LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'current_approval_level' => 0,
        ]);
    }

    public function test_direct_manager_can_approve(): void
    {
        $manager = $this->makeEmployee();
        $employee = $this->makeEmployee($manager->id);
        $leaveRequest = $this->makeLeaveRequest($employee);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($manager->user));
    }

    public function test_indirect_manager_further_up_the_chain_can_approve(): void
    {
        $grandManager = $this->makeEmployee();
        $manager = $this->makeEmployee($grandManager->id);
        $employee = $this->makeEmployee($manager->id);
        $leaveRequest = $this->makeLeaveRequest($employee);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($grandManager->user));
    }

    public function test_head_of_department_role_alone_no_longer_grants_approval(): void
    {
        // Same department, "head-of-department" role active, but NOT
        // actually the requester's manager per the organogram - this is
        // exactly the gap the organogram enforcement closes.
        $unrelatedHod = $this->makeEmployee(null, 1);
        $employee = $this->makeEmployee(null, 1); // no manager assigned at all
        $leaveRequest = $this->makeLeaveRequest($employee);

        session(['active_role' => 'head-of-department']);
        $this->assertFalse($leaveRequest->canUserApprove($unrelatedHod->user));
    }

    public function test_a_stranger_manager_in_a_different_line_cannot_approve(): void
    {
        $realManager = $this->makeEmployee();
        $employee = $this->makeEmployee($realManager->id);
        $unrelatedManager = $this->makeEmployee();
        $leaveRequest = $this->makeLeaveRequest($employee);

        session(['active_role' => 'business-employee']);
        $this->assertFalse($leaveRequest->canUserApprove($unrelatedManager->user));
    }

    public function test_business_hr_retains_standing_override_with_no_organogram_link(): void
    {
        $employee = $this->makeEmployee(null, 1);
        $hrEmployee = $this->makeEmployee(null, 2);
        $leaveRequest = $this->makeLeaveRequest($employee);

        session(['active_role' => 'business-hr']);
        $this->assertTrue($leaveRequest->canUserApprove($hrEmployee->user));
    }

    public function test_a_manager_cannot_approve_the_same_request_twice(): void
    {
        $manager = $this->makeEmployee();
        $employee = $this->makeEmployee($manager->id);
        $leaveRequest = $this->makeLeaveRequest($employee);
        $leaveRequest->approval_history = [
            ['approver_id' => $manager->user->id, 'approver_role' => 'business-employee'],
        ];
        $leaveRequest->save();

        session(['active_role' => 'business-employee']);
        $this->assertFalse($leaveRequest->fresh()->canUserApprove($manager->user));
    }

    public function test_final_approval_notification_finds_chief_of_staff_scoped_to_department(): void
    {
        // Regression: findChiefOfStaffApproversForDepartment() called
        // whereHas('assignedDepartments', ...) - a relation that doesn't
        // exist on Employee (the real one is departments(), the
        // employee_departments pivot) - which threw "Call to undefined
        // method" and aborted final-approval notifications entirely.
        $business = Business::find(1);
        $inDeptEmployee = $this->makeEmployee(null, 1);
        $inDeptEmployee->user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'chief-of-staff', 'guard_name' => 'web']));
        $inDeptEmployee->departments()->attach([1]);

        $outsideDeptEmployee = $this->makeEmployee(null, 2);
        $outsideDeptEmployee->user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'chief-of-staff', 'guard_name' => 'web']));
        $outsideDeptEmployee->departments()->attach([2]);

        $controller = new \App\Http\Controllers\LeaveRequestController();
        $method = new \ReflectionMethod($controller, 'findChiefOfStaffApproversForDepartment');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $business, 1);

        $userIds = $result->pluck('id');
        $this->assertTrue($userIds->contains($inDeptEmployee->user->id));
        $this->assertFalse($userIds->contains($outsideDeptEmployee->user->id));
    }
}

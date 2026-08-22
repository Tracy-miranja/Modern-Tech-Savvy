<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Mail\LeaveRequestSubmitted;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression coverage for LeaveType.approval_chain: businesses can now
 * explicitly configure who approves each level of a leave type
 * ('organogram'|'hr'|'department_head') instead of routing being
 * hardcoded. A business that wants every leave request for a type routed
 * straight to HR (bypassing the requester's manager chain entirely) sets
 * approval_chain=['hr'] - this is exactly that scenario, tested.
 */
class LeaveApprovalRoutingConfigTest extends TestCase
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

    private function makeEmployeeUser(?int $managerId = null, ?int $departmentId = 1): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'manager_id' => $managerId,
            'employee_code' => 'ARC-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh()];
    }

    private function makeLeaveRequest(Employee $employee, LeaveType $leaveType): LeaveRequest
    {
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

    public function test_hr_only_routing_blocks_the_requesters_own_manager(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'HR Only Leave ' . uniqid(),
            'approval_chain' => ['hr'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-employee']);
        $this->assertFalse($leaveRequest->canUserApprove($managerUser), 'A plain manager must not be able to approve an HR-only-routed leave type.');
    }

    public function test_hr_only_routing_lets_hr_approve(): void
    {
        [, $requesterEmployee] = $this->makeEmployeeUser(null);
        [$hrUser, $hrEmployee] = $this->makeEmployeeUser(null, 2);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'HR Only Leave ' . uniqid(),
            'approval_chain' => ['hr'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-hr']);
        $this->assertTrue($leaveRequest->canUserApprove($hrUser));
    }

    /**
     * Departments 1/2 (the shared test business) may already have a real,
     * production-configured org-structure HOD (OrganizationOwnershipService) -
     * department_head routing now prefers that derived HOD over the bare
     * 'head-of-department' role (see the fallback test below for the
     * no-org-structure-configured case), so this uses FRESH departments to
     * test the role-only fallback path in isolation.
     */
    public function test_department_head_routing_requires_the_role_and_same_department(): void
    {
        $deptA = \App\Models\Department::create(['business_id' => 1, 'name' => 'Dept Head Test A ' . uniqid()]);
        $deptB = \App\Models\Department::create(['business_id' => 1, 'name' => 'Dept Head Test B ' . uniqid()]);

        [, $requesterEmployee] = $this->makeEmployeeUser(null, $deptA->id);
        [$hodUser, $hodEmployee] = $this->makeEmployeeUser(null, $deptA->id);
        $hodUser->assignRole('head-of-department');

        [$otherDeptHodUser, $otherDeptHodEmployee] = $this->makeEmployeeUser(null, $deptB->id);
        $otherDeptHodUser->assignRole('head-of-department');

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Dept Head Leave ' . uniqid(),
            'approval_chain' => ['department_head'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($hodUser), 'The HOD in the requester\'s own department should be able to approve.');
        $this->assertFalse($leaveRequest->canUserApprove($otherDeptHodUser), 'An HOD in a DIFFERENT department must not be able to approve.');
    }

    public function test_department_head_routing_rejects_a_manager_without_the_hod_role(): void
    {
        $dept = \App\Models\Department::create(['business_id' => 1, 'name' => 'Dept Head Reject Test ' . uniqid()]);
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser(null, $dept->id);
        [, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id, $dept->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Dept Head Leave ' . uniqid(),
            'approval_chain' => ['department_head'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-employee']);
        // Even though this is the requester's real organogram manager, the
        // configured type is department_head, not organogram - being the
        // manager isn't enough without the actual role (and this fresh
        // department has no org-structure Position covering it either).
        $this->assertFalse($leaveRequest->canUserApprove($managerUser));
    }

    /**
     * The org-structure-derived HOD (OrganizationOwnershipService) now
     * takes precedence over the bare 'head-of-department' Spatie role for
     * department_head routing - this is the actual "leave syncs with
     * organization structure" fix: a business that has configured Roles/
     * Positions gets leave routed to whoever the org chart actually says
     * is the department's head, even if that person never got the
     * separate Spatie role, and a role-holder who ISN'T the org-structure
     * HOD no longer gets to approve once the department has one configured.
     */
    public function test_department_head_routing_prefers_the_org_structure_derived_hod_over_the_bare_role(): void
    {
        $dept = \App\Models\Department::create(['business_id' => 1, 'name' => 'Dept Head Derived Test ' . uniqid()]);

        $hodRole = \App\Models\OrganogramRole::create(['business_id' => 1, 'name' => 'Derived HOD ' . uniqid(), 'level' => 1]);
        [, $requesterEmployee] = $this->makeEmployeeUser(null, $dept->id);
        [$derivedHodUser, $derivedHodEmployee] = $this->makeEmployeeUser(null, $dept->id);
        \App\Models\OrganogramPosition::create([
            'business_id' => 1, 'organogram_role_id' => $hodRole->id, 'employee_id' => $derivedHodEmployee->id,
        ])->departments()->attach([$dept->id]);

        // Holds the Spatie role, but is NOT the org-structure-derived HOD
        // for this department - should no longer be able to approve now
        // that the department has a real derived HOD configured.
        [$roleOnlyUser,] = $this->makeEmployeeUser(null, $dept->id);
        $roleOnlyUser->assignRole('head-of-department');

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Dept Head Derived Leave ' . uniqid(),
            'approval_chain' => ['department_head'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($derivedHodUser), 'The org-structure-derived HOD should be able to approve, even without the Spatie role.');
        $this->assertFalse($leaveRequest->canUserApprove($roleOnlyUser), 'A Spatie-role holder who is NOT the derived HOD should no longer approve once the department has a real derived HOD.');
    }

    public function test_organogram_routing_is_the_default_when_no_chain_is_configured(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Default Routing Leave ' . uniqid(),
            // approval_chain intentionally omitted
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($managerUser));
    }

    public function test_admin_roles_remain_a_standing_override_regardless_of_configured_type(): void
    {
        [, $requesterEmployee] = $this->makeEmployeeUser(null, 1);
        [$headUser,] = $this->makeEmployeeUser(null, 2);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Dept Head Leave ' . uniqid(),
            'approval_chain' => ['department_head'],
        ]);
        $leaveRequest = $this->makeLeaveRequest($requesterEmployee, $leaveType);

        session(['active_role' => 'business-hr']);
        $this->assertTrue($leaveRequest->canUserApprove($headUser), 'business-hr should always be able to approve, no matter the configured routing.');
    }

    public function test_saving_a_leave_type_persists_the_approval_chain_and_syncs_level_count(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveTypeController();
        $request = Request::create('/leave-types', 'POST', [
            'name' => 'Chain Persist Leave ' . uniqid(),
            'requires_approval' => true,
            'is_paid' => true,
            'allowance_accruable' => true,
            'allows_half_day' => true,
            'requires_attachment' => false,
            'min_notice_days' => 0,
            'department' => 'all',
            'job_category' => 'all',
            'gender_applicable' => 'all',
            'prorated_for_new_employees' => false,
            'default_days' => 21,
            'accrual_frequency' => 'yearly',
            'accrual_amount' => 0,
            'max_carryover_days' => 0,
            'minimum_service_days_required' => 0,
            'effective_date' => '2020-01-01',
            'allows_backdating' => false,
            'approval_levels' => 1, // deliberately wrong - should be overridden by the chain's length
            'approval_chain' => ['organogram', 'hr'],
            'is_stepwise' => false,
        ]);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());

        $leaveType = LeaveType::where('business_id', 1)->where('name', 'like', 'Chain Persist Leave%')->first();
        $this->assertNotNull($leaveType);
        $this->assertSame(['organogram', 'hr'], $leaveType->approval_chain);
        $this->assertSame(2, $leaveType->approval_levels);
        $this->assertSame('organogram', $leaveType->approverTypeForLevel(1));
        $this->assertSame('hr', $leaveType->approverTypeForLevel(2));
    }

    public function test_hr_only_leave_type_emails_hr_on_submission_not_the_requesters_manager(): void
    {
        Mail::fake();

        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);
        [$hrUser, $hrEmployee] = $this->makeEmployeeUser(null, 2);
        $hrUser->assignRole('business-hr');

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'HR Only Submission Leave ' . uniqid(),
            'requires_approval' => true,
            'approval_chain' => ['hr'],
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'HR Routing Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $requesterEmployee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-08',
        ]);
        $request->setUserResolver(fn () => $requesterUser);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());

        Mail::assertQueued(LeaveRequestSubmitted::class, function ($mail) use ($hrUser) {
            return $mail->hasTo($hrUser->email);
        });
        Mail::assertNotQueued(LeaveRequestSubmitted::class, function ($mail) use ($managerUser) {
            return $mail->hasTo($managerUser->email);
        });
    }
}

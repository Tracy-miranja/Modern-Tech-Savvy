<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the Phase 1 "business-defined roles & module permissions" pattern
 * actually works end-to-end for Leave, the module it was wired into first:
 *
 * 1. Route-level: business.leave.* used to be gated only by the fixed
 *    role: business-admin|business-hr|business-finance|head-of-department|
 *    restricted-hr|chief-of-staff list (routes/web.php). It's now gated by
 *    role_or_permission_or_impersonation with the same role list PLUS the
 *    module.leave-management.{view,create,edit,delete,approve} permissions,
 *    so a user holding ONLY a business-created custom role with the right
 *    permission can reach these pages - and a role-less/permission-less
 *    bystander still can't.
 * 2. Model-level: LeaveRequest::canUserApprove() now also accepts a user
 *    holding module.leave-management.approve as a standing override,
 *    independent of the fixed chief-of-staff/business-hr/business-admin
 *    roles - so a custom "Leave Approver" role can actually approve leave,
 *    not just reach the page.
 *
 * Every existing fixed-role Leave test (LeaveApprovalRoutingConfigTest and
 * the wider Leave suite) is expected to keep passing unchanged - this file
 * only adds the new custom-role path, it doesn't touch the old one.
 */
class LeaveCustomRolePermissionEnforcementTest extends TestCase
{
    private Business $business;

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

        $this->business = Business::find(1); // amsol
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeCustomRoleUser(array $permissionNames, ?int $departmentId = 1): array
    {
        $displayName = 'Test Custom Role ' . uniqid();
        $role = Role::create([
            'name' => Role::generateUniqueName($this->business->id, $displayName),
            'display_name' => $displayName,
            'guard_name' => 'web',
            'business_id' => $this->business->id,
            'is_custom' => true,
        ]);
        $role->syncPermissions($permissionNames);

        $user = User::factory()->create();
        $user->assignRole($role);

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => $departmentId,
            'employee_code' => 'CUSTOMROLE-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh(), $role];
    }

    private function makePlainBystanderUser(?int $departmentId = 1): array
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => $departmentId,
            'employee_code' => 'BYSTANDER-' . uniqid(),
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

    public function test_a_custom_role_holder_with_the_view_permission_can_reach_the_leave_requests_page(): void
    {
        [$user, , $role] = $this->makeCustomRoleUser(['module.leave-management.view']);

        $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->get(route('business.leave.index', $this->business->slug))
            ->assertOk();
    }

    public function test_a_role_less_permission_less_bystander_cannot_reach_the_leave_requests_page(): void
    {
        [$user] = $this->makePlainBystanderUser();

        // Non-AJAX 403s in this app redirect to wherever the user actually
        // does belong (RoleHomeRouteService) instead of a bare error page -
        // an AJAX/JSON request is what deterministically surfaces the raw
        // 403 status regardless of that redirect-somewhere-useful behavior.
        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->getJson(route('business.leave.index', $this->business->slug));

        $response->assertStatus(403);
    }

    public function test_a_custom_role_holder_without_any_leave_permission_still_cannot_reach_the_leave_requests_page(): void
    {
        [$user, , $role] = $this->makeCustomRoleUser(['module.employee-management.view']);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->getJson(route('business.leave.index', $this->business->slug));

        $response->assertStatus(403);
    }

    public function test_a_custom_role_holder_with_the_approve_permission_can_actually_approve_a_leave_request(): void
    {
        [$approverUser, $approverEmployee, $role] = $this->makeCustomRoleUser(['module.leave-management.approve'], 2);
        [, $requesterEmployee] = $this->makePlainBystanderUser(1);

        // approval_chain = ['hr'] deliberately excludes organogram/department_head
        // routing entirely - only an actual HR/admin (or, now, a custom role
        // holding module.leave-management.approve) can pass canUserApprove()
        // for this leave type, proving the permission is a genuinely
        // independent path and not incidentally satisfied by manager/HOD logic.
        $leaveType = LeaveType::create([
            'business_id' => $this->business->id,
            'name' => 'Custom Role Approval Leave ' . uniqid(),
            'approval_chain' => ['hr'],
        ]);
        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber($this->business->id),
            'employee_id' => $requesterEmployee->id,
            'business_id' => $this->business->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'current_approval_level' => 0,
        ]);

        session(['active_role' => $role->name]);
        $this->assertTrue(
            $leaveRequest->canUserApprove($approverUser),
            'A custom role holding module.leave-management.approve should be able to approve leave, independent of the fixed admin/HR roles.'
        );

        $response = $this->actingAs($approverUser)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->post(route('business.leave.status', $this->business->slug), [
                'reference_number' => $leaveRequest->reference_number,
                'status' => 'approved',
            ]);

        $response->assertOk();
        $this->assertSame('approved', $leaveRequest->fresh()->status);
    }

    public function test_a_custom_role_holder_without_the_approve_permission_cannot_approve_a_leave_request(): void
    {
        [$viewerUser, , $role] = $this->makeCustomRoleUser(['module.leave-management.view'], 2);
        [, $requesterEmployee] = $this->makePlainBystanderUser(1);

        $leaveType = LeaveType::create([
            'business_id' => $this->business->id,
            'name' => 'Custom Role No-Approve Leave ' . uniqid(),
            'approval_chain' => ['hr'],
        ]);
        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber($this->business->id),
            'employee_id' => $requesterEmployee->id,
            'business_id' => $this->business->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'current_approval_level' => 0,
        ]);

        session(['active_role' => $role->name]);
        $this->assertFalse($leaveRequest->canUserApprove($viewerUser));
    }
}

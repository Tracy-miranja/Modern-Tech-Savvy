<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEncashment;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * business.leave.encashments.approve/reject/mark-disbursed sit behind a
 * SECOND, narrower inner role: gate on top of the outer Leave group's own
 * role_or_permission_or_impersonation gate (see routes/web.php) - a custom
 * role holding module.leave-management.approve could reach the outer Leave
 * group (proven in LeaveCustomRolePermissionEnforcementTest) but was still
 * blocked at this inner gate, since it only ever accepted the 3 fixed
 * roles. This is the exact "deeper per-action permission override" gap
 * flagged as follow-up work; fixed by swapping that inner gate to
 * role_or_permission_or_impersonation too.
 */
class LeaveEncashmentCustomRolePermissionTest extends TestCase
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

    private function makeCustomRoleUser(array $permissionNames): array
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
            'department_id' => 1,
            'employee_code' => 'ENCASHROLE-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => 1,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return [$user->fresh(), $role];
    }

    private function makePendingEncashment(): LeaveEncashment
    {
        $requesterUser = User::factory()->create();
        $requesterEmployee = Employee::create([
            'user_id' => $requesterUser->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'ENCASHREQ-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        $leaveType = LeaveType::create([
            'business_id' => $this->business->id,
            'name' => 'Encashment Perm Test Leave ' . uniqid(),
        ]);
        $period = LeavePeriod::create([
            'business_id' => $this->business->id, 'name' => 'Encashment Perm Test Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => $this->business->id, 'employee_id' => $requesterEmployee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        return LeaveEncashment::create([
            'business_id' => $this->business->id,
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'days_requested' => 2,
            'daily_rate' => 1000,
            'amount' => 2000,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    public function test_a_custom_role_holder_with_the_approve_permission_can_approve_an_encashment(): void
    {
        [$user, $role] = $this->makeCustomRoleUser(['module.leave-management.approve']);
        $encashment = $this->makePendingEncashment();

        $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->post(route('business.leave.encashments.approve', [$this->business->slug, $encashment->id]))
            ->assertOk();

        $this->assertSame('approved', $encashment->fresh()->status);
    }

    public function test_a_custom_role_holder_without_the_approve_permission_cannot_approve_an_encashment(): void
    {
        [$user, $role] = $this->makeCustomRoleUser(['module.leave-management.view']);
        $encashment = $this->makePendingEncashment();

        $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => $role->name,
                '2fa_verified' => true,
            ])
            ->postJson(route('business.leave.encashments.approve', [$this->business->slug, $encashment->id]))
            ->assertStatus(403);

        $this->assertSame('pending', $encashment->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveTypeController;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\JobCategory;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeavePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LeaveTypeController::update() used to write policy changes with
 * LeavePolicy::updateOrCreate() keyed on (leave_type, department,
 * job_category, gender) only - no effective_date in the key - so editing a
 * policy (e.g. bumping max_carryover_days for the new period) silently
 * rewrote the SAME row, destroying any record of what the policy was
 * before. Since resolvePolicy() resolves "the policy as of date X" by
 * effective_date/end_date, that meant an edit made today could retroactively
 * change what a leave request from a period already closed would resolve to
 * if anything ever recomputed it. update() now versions the policy: closes
 * the currently-open row and inserts a new dated one instead of overwriting
 * in place, so past dates keep resolving to what was actually in effect
 * then.
 */
class LeavePolicyVersioningTest extends TestCase
{
    private Business $business;
    private Department $department;
    private JobCategory $jobCategory;

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

        // Dedicated department/job category so "all" doesn't expand across
        // amsol's real (numerous) seeded ones and swamp the policy counts
        // this test is asserting on.
        $this->department = Department::create([
            'business_id' => $this->business->id,
            'name' => 'Policy Versioning Dept ' . uniqid(),
        ]);
        $this->jobCategory = JobCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Policy Versioning Job ' . uniqid(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeLeaveType(string $effectiveDate = '2020-01-01'): LeaveType
    {
        $leaveType = LeaveType::create([
            'business_id' => $this->business->id,
            'name' => 'Versioning Leave ' . uniqid(),
            'allowance_accruable' => false,
        ]);

        LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'department_id' => $this->department->id,
            'job_category_id' => $this->jobCategory->id,
            'gender_applicable' => 'all',
            'prorated_for_new_employees' => false,
            'default_days' => 21,
            'accrual_frequency' => 'yearly',
            'accrual_amount' => 0,
            'max_carryover_days' => 10,
            'minimum_service_days_required' => 0,
            'effective_date' => $effectiveDate,
            'end_date' => null,
            'is_active' => true,
        ]);

        return $leaveType->fresh();
    }

    private function callUpdate(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $this->actingAs(User::factory()->create());
        session(['active_business_slug' => $this->business->slug]);

        $controller = new LeaveTypeController();
        $request = Request::create('/leave-types/update', 'POST', array_merge([
            'department' => $this->department->slug,
            'job_category' => $this->jobCategory->slug,
            'gender_applicable' => 'all',
        ], $payload));
        $request->setUserResolver(fn () => auth()->user());

        return $controller->update($request)->toResponse($request);
    }

    public function test_changing_max_carryover_days_versions_the_policy_instead_of_overwriting_it(): void
    {
        $leaveType = $this->makeLeaveType('2020-01-01');

        $response = $this->callUpdate([
            'leave_type_slug' => $leaveType->slug,
            'max_carryover_days' => 12,
        ]);
        $this->assertSame(200, $response->getStatusCode());

        $policies = LeavePolicy::where('leave_type_id', $leaveType->id)
            ->orderBy('effective_date')
            ->get();

        $this->assertCount(2, $policies, 'Editing a versioned field should insert a new row, not overwrite the old one.');

        $old = $policies->first();
        $new = $policies->last();

        $this->assertSame(10, (int) $old->max_carryover_days);
        $this->assertSame(now()->subDay()->toDateString(), $old->end_date->toDateString());

        $this->assertSame(12, (int) $new->max_carryover_days);
        $this->assertSame(now()->toDateString(), $new->effective_date->toDateString());
        $this->assertNull($new->end_date);
    }

    public function test_resolve_policy_for_a_past_date_still_returns_the_old_value_after_an_edit(): void
    {
        $leaveType = $this->makeLeaveType('2020-01-01');

        $employee = $this->makeEmployee();

        $this->callUpdate([
            'leave_type_slug' => $leaveType->slug,
            'max_carryover_days' => 12,
        ]);

        $policyService = app(LeavePolicyService::class);

        // Resolving as of a date from the already-closed period must still
        // return the ORIGINAL cap (10), not today's edited value (12).
        $pastPolicy = $policyService->resolvePolicy($leaveType->id, $employee, \Carbon\Carbon::parse('2025-06-01'));
        $this->assertNotNull($pastPolicy);
        $this->assertSame(10, (int) $pastPolicy->max_carryover_days);

        // Resolving as of today (or later) gets the new value.
        $currentPolicy = $policyService->resolvePolicy($leaveType->id, $employee, now());
        $this->assertNotNull($currentPolicy);
        $this->assertSame(12, (int) $currentPolicy->max_carryover_days);
    }

    public function test_resyncing_scope_without_changing_values_does_not_create_a_spurious_version(): void
    {
        $leaveType = $this->makeLeaveType('2020-01-01');

        $response = $this->callUpdate([
            'leave_type_slug' => $leaveType->slug,
            // Resubmitting the SAME value, not a change.
            'max_carryover_days' => 10,
        ]);
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(1, LeavePolicy::where('leave_type_id', $leaveType->id)->count());
    }

    public function test_backdated_effective_date_at_or_before_the_current_version_updates_in_place(): void
    {
        $leaveType = $this->makeLeaveType('2026-01-01');

        $response = $this->callUpdate([
            'leave_type_slug' => $leaveType->slug,
            'max_carryover_days' => 15,
            'effective_date' => '2026-01-01', // same as the existing row's start
        ]);
        $this->assertSame(200, $response->getStatusCode());

        $policies = LeavePolicy::where('leave_type_id', $leaveType->id)->get();
        $this->assertCount(1, $policies, 'A same-or-earlier effective_date cannot open a new version - must correct in place.');
        $this->assertSame(15, (int) $policies->first()->max_carryover_days);
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => $this->department->id,
            'employee_code' => 'POLVER-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $this->department->id,
            'job_category_id' => $this->jobCategory->id,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }
}

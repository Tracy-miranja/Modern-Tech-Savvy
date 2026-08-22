<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the organogram foundation: manager_id relations,
 * cycle prevention, and multi-level report traversal.
 */
class OrganogramTest extends TestCase
{
    /**
     * See LeaveEntitlementBugFixesTest for why this connection dance is needed.
     */
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

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'ORG-' . uniqid(),
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
            'employment_date' => Carbon::parse('2020-01-01')->toDateString(),
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    public function test_direct_reports_and_manager_relation(): void
    {
        $ceo = $this->makeEmployee();
        $manager = $this->makeEmployee();
        $ic = $this->makeEmployee();

        $manager->manager_id = $ceo->id;
        $manager->save();
        $ic->manager_id = $manager->id;
        $ic->save();

        $this->assertTrue($ceo->directReports->pluck('id')->contains($manager->id));
        $this->assertSame($ceo->id, $manager->manager->id);
        $this->assertSame($manager->id, $ic->manager->id);
    }

    public function test_all_reports_walks_multiple_levels(): void
    {
        $ceo = $this->makeEmployee();
        $manager = $this->makeEmployee();
        $ic1 = $this->makeEmployee();
        $ic2 = $this->makeEmployee();

        $manager->manager_id = $ceo->id;
        $manager->save();
        $ic1->manager_id = $manager->id;
        $ic1->save();
        $ic2->manager_id = $manager->id;
        $ic2->save();

        $allReportIds = $ceo->allReports()->pluck('id');

        $this->assertTrue($allReportIds->contains($manager->id));
        $this->assertTrue($allReportIds->contains($ic1->id));
        $this->assertTrue($allReportIds->contains($ic2->id));
        $this->assertCount(3, $allReportIds);
    }

    public function test_reports_to_detects_indirect_management_chain(): void
    {
        $ceo = $this->makeEmployee();
        $manager = $this->makeEmployee();
        $ic = $this->makeEmployee();

        $manager->manager_id = $ceo->id;
        $manager->save();
        $ic->manager_id = $manager->id;
        $ic->save();

        $this->assertTrue($ic->fresh()->reportsTo($ceo));
        $this->assertFalse($ceo->fresh()->reportsTo($ic));
    }

    public function test_would_create_cycle_rejects_self_and_descendant_assignment(): void
    {
        $ceo = $this->makeEmployee();
        $manager = $this->makeEmployee();
        $ic = $this->makeEmployee();

        $manager->manager_id = $ceo->id;
        $manager->save();
        $ic->manager_id = $manager->id;
        $ic->save();

        // An employee cannot become their own manager.
        $this->assertTrue($ceo->wouldCreateCycle($ceo->id));

        // The CEO cannot report to their own indirect report.
        $this->assertTrue($ceo->fresh()->wouldCreateCycle($ic->id));

        // A brand new, unrelated assignment is fine.
        $newHire = $this->makeEmployee();
        $this->assertFalse($newHire->wouldCreateCycle($manager->id));
    }

    public function test_direct_line_manager_can_approve_regardless_of_active_role(): void
    {
        $manager = $this->makeEmployee();
        $report = $this->makeEmployee();
        $report->manager_id = $manager->id;
        $report->save();

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Org Approval Leave ' . uniqid(),
        ]);

        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $report->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-04',
            'current_approval_level' => 0,
        ]);

        // Manager is logged in with the plain employee role, not an approver role.
        session(['active_role' => 'business-employee']);

        $managerUser = $manager->user;
        $this->assertTrue($leaveRequest->canUserApprove($managerUser));

        // An unrelated employee (not in the chain) must not be able to approve.
        $stranger = $this->makeEmployee();
        $this->assertFalse($leaveRequest->canUserApprove($stranger->user));
    }

    public function test_business_employee_scope_includes_direct_reports_requests(): void
    {
        $manager = $this->makeEmployee();
        $report = $this->makeEmployee();
        $report->manager_id = $manager->id;
        $report->save();

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Org Scope Leave ' . uniqid(),
        ]);

        LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $report->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'current_approval_level' => 0,
        ]);

        session(['active_role' => 'business-employee']);

        $visibleIds = LeaveRequest::forRole($manager->user, 1)->pluck('employee_id');

        $this->assertTrue($visibleIds->contains($report->id));
    }
}

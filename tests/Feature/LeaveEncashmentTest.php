<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveEncashmentController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeePaymentDetail;
use App\Models\EmploymentDetail;
use App\Models\LeaveEncashment;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * On-demand Leave Encashment - Leave-side only, no Payroll module
 * involvement (see LeaveEncashmentController's docblock for why approval
 * is single-step rather than the full LeaveRequest approval_chain).
 */
class LeaveEncashmentTest extends TestCase
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

    private function makeEmployee(float $basicSalary = 60000): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'ENC-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();

        EmploymentDetail::create([
            'employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1,
            'employment_date' => '2020-01-01', 'employment_term' => 'permanent',
        ]);

        EmployeePaymentDetail::create([
            'employee_id' => $employee->id, 'basic_salary' => $basicSalary,
            'payment_type' => 'salary', 'currency' => 'KES', 'payment_mode' => 'bank',
            'account_name' => 'Test Employee', 'account_number' => 'ACC-' . uniqid(), 'bank_name' => 'Test Bank',
        ]);

        return $employee->fresh();
    }

    private function makeEncashableSetup(float $basicSalary = 60000, ?int $maxEncashableDays = null): array
    {
        $employee = $this->makeEmployee($basicSalary);
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Encashment Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Encashment Period ' . uniqid(),
            'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'is_active' => true,
        ]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0, 'max_carryover_days' => 0,
            'minimum_service_days_required' => 0, 'is_encashable' => true, 'max_encashable_days' => $maxEncashableDays,
            'effective_date' => '2020-01-01', 'is_active' => true,
        ]);
        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        return [$employee, $leaveType, $period, $entitlement];
    }

    private function actingAsAdmin(): User
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_a_request_computes_the_amount_from_basic_salary_over_30_days(): void
    {
        [$employee, $leaveType] = $this->makeEncashableSetup(60000);
        $business = Business::find(1);
        $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $request = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 5,
        ]);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $encashment = LeaveEncashment::where('employee_id', $employee->id)->first();
        $this->assertSame('2000.00', $encashment->daily_rate); // 60000 / 30
        $this->assertSame('10000.00', $encashment->amount); // 2000 * 5
        $this->assertSame('pending', $encashment->status);
    }

    public function test_a_request_is_rejected_when_the_leave_type_is_not_encashable(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Not Encashable Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Not Encashable Period ' . uniqid(),
            'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);
        // No LeavePolicy at all - is_encashable defaults false anyway.

        $business = Business::find(1);
        $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $request = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 2,
        ]);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_a_request_cannot_exceed_the_policys_max_encashable_days(): void
    {
        [$employee, $leaveType] = $this->makeEncashableSetup(60000, 3);
        $business = Business::find(1);
        $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $request = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 5,
        ]);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, LeaveEncashment::where('employee_id', $employee->id)->count());
    }

    public function test_a_request_cannot_exceed_the_remaining_balance(): void
    {
        [$employee, $leaveType, $period] = $this->makeEncashableSetup(60000);
        LeaveEntitlement::where('employee_id', $employee->id)->update(['days_remaining' => 1]);
        $business = Business::find(1);
        $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $request = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 5,
        ]);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_approving_deducts_the_days_from_the_entitlement_via_adjustment(): void
    {
        [$employee, $leaveType, $period, $entitlement] = $this->makeEncashableSetup(60000);
        $business = Business::find(1);
        $admin = $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $storeRequest = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 4,
        ]);
        $controller->store($storeRequest, $business)->toResponse($storeRequest);
        $encashment = LeaveEncashment::where('employee_id', $employee->id)->first();

        $approveRequest = Request::create('/x', 'POST');
        $approveRequest->setUserResolver(fn () => $admin);
        $response = $controller->approve($approveRequest, $business, $encashment)->toResponse($approveRequest);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('approved', $encashment->fresh()->status);
        $this->assertSame($admin->id, $encashment->fresh()->approved_by);
        $entitlement->refresh();
        $this->assertSame(-4.0, $entitlement->adjustment_days);
        $this->assertSame(17.0, $entitlement->days_remaining); // 21 - 4
    }

    public function test_rejecting_requires_a_reason_and_does_not_touch_the_entitlement(): void
    {
        [$employee, $leaveType, $period, $entitlement] = $this->makeEncashableSetup(60000);
        $business = Business::find(1);
        $admin = $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $storeRequest = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 3,
        ]);
        $controller->store($storeRequest, $business)->toResponse($storeRequest);
        $encashment = LeaveEncashment::where('employee_id', $employee->id)->first();

        $rejectRequest = Request::create('/x', 'POST', ['rejection_reason' => 'Insufficient justification.']);
        $rejectRequest->setUserResolver(fn () => $admin);
        $response = $controller->reject($rejectRequest, $business, $encashment)->toResponse($rejectRequest);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rejected', $encashment->fresh()->status);
        $this->assertSame(0.0, (float) $entitlement->fresh()->adjustment_days);
    }

    public function test_mark_disbursed_only_works_on_an_approved_encashment(): void
    {
        [$employee, $leaveType] = $this->makeEncashableSetup(60000);
        $business = Business::find(1);
        $admin = $this->actingAsAdmin();

        $controller = new LeaveEncashmentController();
        $storeRequest = Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'days_requested' => 2,
        ]);
        $controller->store($storeRequest, $business)->toResponse($storeRequest);
        $encashment = LeaveEncashment::where('employee_id', $employee->id)->first();

        // Still pending - marking disbursed must be rejected.
        $disburseRequest = Request::create('/x', 'POST', ['disbursed_note' => 'Paid via Mpesa']);
        $earlyResponse = $controller->markDisbursed($disburseRequest, $business, $encashment)->toResponse($disburseRequest);
        $this->assertSame(400, $earlyResponse->getStatusCode());

        $approveRequest = Request::create('/x', 'POST');
        $approveRequest->setUserResolver(fn () => $admin);
        $controller->approve($approveRequest, $business, $encashment)->toResponse($approveRequest);

        $disburseRequest2 = Request::create('/x', 'POST', ['disbursed_note' => 'Paid via Mpesa']);
        $response = $controller->markDisbursed($disburseRequest2, $business, $encashment->fresh())->toResponse($disburseRequest2);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('disbursed', $encashment->fresh()->status);
        $this->assertNotNull($encashment->fresh()->disbursed_at);
    }
}

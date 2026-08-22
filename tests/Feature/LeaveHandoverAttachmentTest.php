<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveRequestController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression coverage for the optional handover attachment - a separate
 * file field sitting directly below handover notes on the leave request
 * form, independent of the leave-type's own (conditionally required)
 * attachment field.
 */
class LeaveHandoverAttachmentTest extends TestCase
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

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'HOA-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh()];
    }

    public function test_leave_request_can_be_submitted_with_a_handover_attachment(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Handover Attachment Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Handover Attachment Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        session(['active_business_slug' => Business::find(1)->slug]);
        $this->actingAs($user);

        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'handover_notes' => 'Ongoing work status attached.',
        ]);
        $request->files->set('handover_attachment', UploadedFile::fake()->create('handover.pdf', 100, 'application/pdf'));
        $request->setUserResolver(fn () => $user);

        $controller = new LeaveRequestController();
        $response = $controller->store($request)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'unknown error');

        $leaveRequest = LeaveRequest::where('employee_id', $employee->id)->first();
        $this->assertNotNull($leaveRequest->handover_attachment);
        Storage::disk('public')->assertExists($leaveRequest->handover_attachment);
    }

    public function test_handover_attachment_is_optional(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'No Handover Attachment Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'No Handover Attachment Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        session(['active_business_slug' => Business::find(1)->slug]);
        $this->actingAs($user);

        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-06',
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = new LeaveRequestController();
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());

        $leaveRequest = LeaveRequest::where('employee_id', $employee->id)->first();
        $this->assertNull($leaveRequest->handover_attachment);
    }
}

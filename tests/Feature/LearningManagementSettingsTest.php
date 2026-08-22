<?php

namespace Tests\Feature;

use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseMandateController;
use App\Http\Controllers\LearningController;
use App\Mail\CertificateExpiryReminderMail;
use App\Mail\CourseSessionReminderMail;
use App\Models\Business;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseEnrollment;
use App\Models\CourseMandate;
use App\Models\CourseSession;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\Learning\LearningSchedulerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Learning Management "Settings" round - Course Categories, Mandatory/
 * Compliance Courses (auto-enroll), Certificate Defaults, and Expiry/
 * Session Reminders. Builds on LearningManagementTest's base module.
 */
class LearningManagementSettingsTest extends TestCase
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

        DB::table('business_modules')->where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function actingAsAdmin(Business $business): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return $admin;
    }

    private function makeEmployee(?int $departmentId = null, ?int $jobCategoryId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId ?? 1,
            'employee_code' => 'LMS-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();

        if ($jobCategoryId) {
            EmploymentDetail::create(['employee_id' => $employee->id, 'department_id' => $departmentId ?? 1, 'job_category_id' => $jobCategoryId, 'employment_date' => '2020-01-01']);
        }

        return $employee;
    }

    // ---- Course Categories -----------------------------------------------

    public function test_a_category_can_be_created_and_used_on_a_course(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new CourseCategoryController();

        $storeResponse = $controller->store(Request::create('/x', 'POST', ['name' => 'LMS Compliance']), $business)->toResponse(Request::create('/x'));
        $this->assertSame(201, $storeResponse->getStatusCode(), $storeResponse->getContent());

        $category = CourseCategory::where('business_id', 1)->where('name', 'LMS Compliance')->first();
        $this->assertSame('lms-compliance', $category->slug);

        $course = Course::create(['business_id' => 1, 'title' => 'LMS Course', 'course_category_id' => $category->id, 'status' => 'active']);
        $this->assertSame('LMS Compliance', $course->fresh()->category->name);
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $category = CourseCategory::create(['business_id' => 1, 'name' => 'LMS Safety', 'slug' => 'lms-safety']);
        Course::create(['business_id' => 1, 'title' => 'LMS Fire Drill', 'course_category_id' => $category->id, 'status' => 'active']);

        $controller = new CourseCategoryController();
        $response = $controller->destroy(Request::create('/x', 'DELETE'), $business, $category)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(CourseCategory::find($category->id));
    }

    // ---- Mandatory / Compliance Courses -----------------------------------

    public function test_creating_an_organization_wide_mandate_auto_enrolls_every_employee(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employeeA = $this->makeEmployee();
        $employeeB = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Code of Conduct', 'status' => 'active']);

        $controller = new CourseMandateController();
        $request = Request::create('/x', 'POST', ['course_id' => $course->id, 'scope_type' => 'organization']);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $this->assertTrue(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employeeA->id)->exists());
        $this->assertTrue(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employeeB->id)->exists());
    }

    public function test_a_department_scoped_mandate_only_enrolls_matching_employees(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $department = Department::create(['business_id' => 1, 'name' => 'LMS Warehouse ' . uniqid(), 'slug' => 'lms-warehouse-' . uniqid()]);
        $inDept = $this->makeEmployee($department->id);
        $outsideDept = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Forklift Safety', 'status' => 'active']);

        $controller = new CourseMandateController();
        $request = Request::create('/x', 'POST', ['course_id' => $course->id, 'scope_type' => 'department', 'scope_ids' => [$department->id]]);
        $request->setUserResolver(fn () => $admin);
        $controller->store($request, $business)->toResponse($request);

        $this->assertTrue(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $inDept->id)->exists());
        $this->assertFalse(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $outsideDept->id)->exists());
    }

    public function test_a_job_category_scoped_mandate_only_enrolls_matching_employees(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $jobCategory = JobCategory::create(['business_id' => 1, 'name' => 'LMS Driver ' . uniqid(), 'slug' => 'lms-driver-' . uniqid()]);
        $driver = $this->makeEmployee(null, $jobCategory->id);
        $nonDriver = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Defensive Driving', 'status' => 'active']);

        $controller = new CourseMandateController();
        $request = Request::create('/x', 'POST', ['course_id' => $course->id, 'scope_type' => 'job_category', 'scope_ids' => [$jobCategory->id]]);
        $request->setUserResolver(fn () => $admin);
        $controller->store($request, $business)->toResponse($request);

        $this->assertTrue(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $driver->id)->exists());
        $this->assertFalse(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $nonDriver->id)->exists());
    }

    public function test_a_mandate_never_enrolls_the_same_employee_twice_across_repeated_syncs(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Repeat Sync', 'status' => 'active']);

        $mandate = CourseMandate::create(['business_id' => 1, 'course_id' => $course->id, 'scope_type' => 'organization', 'created_by' => $admin->id]);
        $mandate->autoEnroll();
        $mandate->autoEnroll();

        $this->assertSame(1, CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employee->id)->count());
    }

    public function test_deleting_a_mandate_does_not_remove_enrollments_it_created(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Keep Enrollment', 'status' => 'active']);
        $mandate = CourseMandate::create(['business_id' => 1, 'course_id' => $course->id, 'scope_type' => 'organization', 'created_by' => $admin->id]);
        $mandate->autoEnroll();
        $enrollmentCountBefore = CourseEnrollment::where('course_id', $course->id)->count();
        $this->assertGreaterThan(0, $enrollmentCountBefore);

        $controller = new CourseMandateController();
        $controller->destroy(Request::create('/x', 'DELETE'), $business, $mandate)->toResponse(Request::create('/x'));

        $this->assertNull(CourseMandate::find($mandate->id));
        $this->assertSame($enrollmentCountBefore, CourseEnrollment::where('course_id', $course->id)->count());
    }

    // ---- Certificate Defaults ---------------------------------------------

    public function test_settings_update_persists_certificate_and_reminder_defaults(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new LearningController();

        $request = Request::create('/x', 'POST', [
            'learning_certificate_validity_months' => 12,
            'learning_certificate_number_prefix' => 'LMS',
            'learning_session_reminder_days' => 5,
            'learning_certificate_expiry_reminder_days' => 45,
        ]);
        $response = $controller->updateSettings($request, $business)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $business->refresh();
        $this->assertSame(12, $business->learning_certificate_validity_months);
        $this->assertSame('LMS', $business->learning_certificate_number_prefix);
        $this->assertSame(5, $business->learning_session_reminder_days);
        $this->assertSame(45, $business->learning_certificate_expiry_reminder_days);
    }

    public function test_marking_complete_auto_fills_certificate_number_and_expiry_from_business_defaults(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $business->update(['learning_certificate_validity_months' => 6, 'learning_certificate_number_prefix' => 'LMS']);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Auto Cert', 'status' => 'active']);
        $enrollment = CourseEnrollment::create(['course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $request = Request::create('/x', 'POST', ['status' => 'completed', 'certificate_issued' => true]);
        $controller->updateEnrollment($request, $business, $enrollment)->toResponse($request);

        $enrollment->refresh();
        $this->assertSame('LMS-' . str_pad($enrollment->id, 5, '0', STR_PAD_LEFT), $enrollment->certificate_number);
        $this->assertSame(now()->addMonths(6)->toDateString(), $enrollment->certificate_expiry_date->toDateString());
    }

    public function test_manually_provided_certificate_number_is_never_overridden(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $business->update(['learning_certificate_number_prefix' => 'LMS']);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Manual Cert', 'status' => 'active']);
        $enrollment = CourseEnrollment::create(['course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $request = Request::create('/x', 'POST', ['status' => 'completed', 'certificate_issued' => true, 'certificate_number' => 'CUSTOM-001']);
        $controller->updateEnrollment($request, $business, $enrollment)->toResponse($request);

        $this->assertSame('CUSTOM-001', $enrollment->fresh()->certificate_number);
    }

    // ---- Reminders (LearningSchedulerService) -----------------------------

    public function test_session_reminder_is_sent_exactly_once_on_the_configured_day(): void
    {
        Mail::fake();
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $business->update(['learning_session_reminder_days' => 3]);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Reminder Course', 'status' => 'active']);
        $session = CourseSession::create(['course_id' => $course->id, 'business_id' => 1, 'start_date' => now()->addDays(3)]);
        CourseEnrollment::create([
            'course_id' => $course->id, 'course_session_id' => $session->id, 'business_id' => 1,
            'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now(),
        ]);

        $service = new LearningSchedulerService();
        $sentFirstRun = $service->sendSessionReminders();
        $sentSecondRun = $service->sendSessionReminders();

        $this->assertSame(1, $sentFirstRun);
        $this->assertSame(0, $sentSecondRun, 'must not send the same reminder twice');
        Mail::assertSent(CourseSessionReminderMail::class, 1);
    }

    public function test_certificate_expiry_reminder_is_sent_on_the_configured_day(): void
    {
        Mail::fake();
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $business->update(['learning_certificate_expiry_reminder_days' => 30]);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Expiry Course', 'status' => 'active']);
        CourseEnrollment::create([
            'course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id,
            'status' => 'completed', 'enrolled_at' => now()->subYear(), 'completed_at' => now()->subYear(),
            'certificate_issued' => true, 'certificate_number' => 'X-1', 'certificate_expiry_date' => now()->addDays(30),
        ]);

        $service = new LearningSchedulerService();
        $sent = $service->sendCertificateExpiryReminders();

        $this->assertSame(1, $sent);
        Mail::assertSent(CertificateExpiryReminderMail::class, 1);
    }

    public function test_scheduler_service_sync_auto_enrolls_via_active_mandates(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $course = Course::create(['business_id' => 1, 'title' => 'LMS Sync Course', 'status' => 'active']);
        CourseMandate::create(['business_id' => 1, 'course_id' => $course->id, 'scope_type' => 'organization', 'created_by' => $admin->id]);
        $employee = $this->makeEmployee();

        $service = new LearningSchedulerService();
        $enrolledCount = $service->syncMandateEnrollments();

        $this->assertGreaterThanOrEqual(1, $enrolledCount);
        $this->assertTrue(CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employee->id)->exists());
    }
}

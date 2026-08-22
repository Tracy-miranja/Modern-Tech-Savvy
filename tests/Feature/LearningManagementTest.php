<?php

namespace Tests\Feature;

use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningReportController;
use App\Models\Business;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\Employee;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Learning Management - the last of the 3 previously-unimplemented modules
 * (see ModuleGatingAndAssetsTest for the sibling Asset Management module
 * this follows the same pattern as). Courses, per-course sessions
 * ("Training Schedules"), and enrollment tracking through to completion +
 * certification fields.
 */
class LearningManagementTest extends TestCase
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

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'LMT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();
    }

    // ---- Module gating (HTTP-level, mirrors ModuleGatingAndAssetsTest) ---

    public function test_middleware_allows_access_when_business_never_selected_modules(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.learning.index', $business->slug));

        $response->assertOk();
    }

    public function test_middleware_redirects_when_business_has_other_modules_but_not_learning_management(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $other = Module::create(['name' => 'LMT Other Module ' . uniqid(), 'description' => 'x', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false, 'features' => []]);
        $business->modules()->attach($other->id, ['is_active' => true]);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.learning.index', $business->slug));

        $response->assertRedirect(route('business.index', $business->slug));
    }

    // ---- Courses ---------------------------------------------------------

    public function test_a_course_can_be_created_updated_and_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new LearningController();

        $storeRequest = Request::create('/x', 'POST', ['title' => 'LMT Onboarding 101']);
        $storeResponse = $controller->storeCourse($storeRequest, $business)->toResponse($storeRequest);
        $this->assertSame(201, $storeResponse->getStatusCode(), $storeResponse->getContent());
        $course = Course::where('business_id', 1)->where('title', 'LMT Onboarding 101')->first();
        $this->assertSame('active', $course->status);

        $updateRequest = Request::create('/x', 'POST', ['status' => 'archived']);
        $controller->updateCourse($updateRequest, $business, $course)->toResponse($updateRequest);
        $this->assertSame('archived', $course->fresh()->status);

        $destroyResponse = $controller->destroyCourse(Request::create('/x', 'DELETE'), $business, $course)->toResponse(Request::create('/x'));
        $this->assertSame(200, $destroyResponse->getStatusCode());
        $this->assertNull(Course::find($course->id));
    }

    public function test_a_course_with_enrollments_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Compliance', 'status' => 'active']);
        CourseEnrollment::create(['course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $response = $controller->destroyCourse(Request::create('/x', 'DELETE'), $business, $course)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(Course::find($course->id));
    }

    // ---- Sessions ("Training Schedules") ----------------------------------

    public function test_a_session_can_be_added_to_a_course_and_fetched(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Fire Safety', 'status' => 'active']);

        $controller = new LearningController();
        $storeRequest = Request::create('/x', 'POST', ['start_date' => now()->addWeek()->toDateString(), 'location' => 'Online']);
        $storeResponse = $controller->storeSession($storeRequest, $business, $course)->toResponse($storeRequest);
        $this->assertSame(201, $storeResponse->getStatusCode(), $storeResponse->getContent());

        $fetchResponse = $controller->fetchSessions(Request::create('/x'), $business, $course)->toResponse(Request::create('/x'));
        $data = json_decode($fetchResponse->getContent(), true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame('Online', $data[0]['location']);
    }

    public function test_a_session_with_enrollments_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT First Aid', 'status' => 'active']);
        $session = CourseSession::create(['course_id' => $course->id, 'business_id' => 1, 'start_date' => now()->addWeek()]);
        CourseEnrollment::create(['course_id' => $course->id, 'course_session_id' => $session->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $response = $controller->destroySession(Request::create('/x', 'DELETE'), $business, $session)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(CourseSession::find($session->id));
    }

    // ---- Enrollments -------------------------------------------------------

    public function test_an_employee_cannot_be_enrolled_twice_in_the_same_course(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Data Protection', 'status' => 'active']);

        $controller = new LearningController();
        $firstRequest = Request::create('/x', 'POST', ['employee_id' => $employee->id]);
        $firstRequest->setUserResolver(fn () => $admin);
        $firstResponse = $controller->storeEnrollment($firstRequest, $business, $course)->toResponse($firstRequest);
        $this->assertSame(201, $firstResponse->getStatusCode(), $firstResponse->getContent());

        $secondRequest = Request::create('/x', 'POST', ['employee_id' => $employee->id]);
        $secondRequest->setUserResolver(fn () => $admin);
        $secondResponse = $controller->storeEnrollment($secondRequest, $business, $course)->toResponse($secondRequest);
        $this->assertSame(400, $secondResponse->getStatusCode());
        $this->assertSame(1, CourseEnrollment::where('course_id', $course->id)->where('employee_id', $employee->id)->count());
    }

    public function test_marking_an_enrollment_completed_stamps_completed_at_and_can_record_a_certificate(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Manual Handling', 'status' => 'active']);
        $enrollment = CourseEnrollment::create(['course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $updateRequest = Request::create('/x', 'POST', [
            'status' => 'completed',
            'score' => 92.5,
            'certificate_issued' => true,
            'certificate_number' => 'CERT-LMT-1',
        ]);
        $response = $controller->updateEnrollment($updateRequest, $business, $enrollment)->toResponse($updateRequest);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        $enrollment->refresh();
        $this->assertSame('completed', $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertTrue($enrollment->certificate_issued);
        $this->assertSame('CERT-LMT-1', $enrollment->certificate_number);
        $this->assertSame('92.50', $enrollment->score);
    }

    public function test_dropping_an_enrollment_clears_completed_at(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Anti Harassment', 'status' => 'active']);
        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id,
            'status' => 'completed', 'enrolled_at' => now()->subWeek(), 'completed_at' => now(),
        ]);

        $controller = new LearningController();
        $updateRequest = Request::create('/x', 'POST', ['status' => 'dropped']);
        $controller->updateEnrollment($updateRequest, $business, $enrollment)->toResponse($updateRequest);

        $this->assertNull($enrollment->fresh()->completed_at);
        $this->assertSame('dropped', $enrollment->fresh()->status);
    }

    public function test_fetch_enrollments_can_be_filtered_by_course(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $courseA = Course::create(['business_id' => 1, 'title' => 'LMT Course A', 'status' => 'active']);
        $courseB = Course::create(['business_id' => 1, 'title' => 'LMT Course B', 'status' => 'active']);
        CourseEnrollment::create(['course_id' => $courseA->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);
        CourseEnrollment::create(['course_id' => $courseB->id, 'business_id' => 1, 'employee_id' => $employee->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $controller = new LearningController();
        $response = $controller->fetchEnrollments(Request::create('/x', 'GET', ['course_id' => $courseA->id]), $business)->toResponse(Request::create('/x'));
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame($courseA->id, $data[0]['course_id']);
    }

    // ---- Report smoke test --------------------------------------------

    public function test_completions_report_preview_renders_without_error(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $course = Course::create(['business_id' => 1, 'title' => 'LMT Reportable Course', 'status' => 'active']);
        CourseEnrollment::create([
            'course_id' => $course->id, 'business_id' => 1, 'employee_id' => $employee->id,
            'status' => 'completed', 'enrolled_at' => now()->subWeek(), 'completed_at' => now(), 'score' => 88,
        ]);

        $controller = new LearningReportController();
        $html = $controller->completionsPreview(Request::create('/x'), $business);

        $this->assertStringContainsString('Learning Completions Report', $html);
        $this->assertStringContainsString('LMT Reportable Course', $html);
    }
}

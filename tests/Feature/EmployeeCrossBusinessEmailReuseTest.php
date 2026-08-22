<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WelcomeEmployeeNotification;
use Tests\TestCase;

/**
 * EmployeeController::store() used to hard-block ('unique:users,email') any
 * email already belonging to a User - so the same real person could never
 * legitimately be added as an employee at a second business under their
 * existing account, even though User::employees()/activeEmployee() already
 * support exactly that (built for the "Add Business" feature). It now
 * mirrors PlatformAdminController::store()'s existing-user-reuse pattern:
 * an existing email is reused (not duplicated), a fresh Employee row is
 * created for the new business, and only a no-link welcome notice is sent
 * (no password reset - they already have an account). Adding the SAME
 * business twice for the same email is still blocked.
 *
 * Also covers the accompanying constraint change: employee_code/
 * national_id/tax_no/nhif_no/nssf_no/passport_no are now unique per
 * business, not globally.
 */
class EmployeeCrossBusinessEmailReuseTest extends TestCase
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

    private function makeBusinessContext(string $label): array
    {
        $business = Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => "Reuse Test {$label} " . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '07' . random_int(10000000, 99999999),
            'code' => strtoupper($label) . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);

        $department = Department::create(['business_id' => $business->id, 'name' => "Dept {$label} " . uniqid()]);
        $jobCategory = JobCategory::create(['business_id' => $business->id, 'name' => "Job {$label} " . uniqid()]);
        $location = Location::create(['business_id' => $business->id, 'name' => "Loc {$label} " . uniqid(), 'country' => 'Kenya']);

        return [$business, $department, $jobCategory, $location];
    }

    private function employeePayload(Department $dept, JobCategory $job, Location $loc, string $email, string $employeeCode, ?string $nationalId = null): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $email,
            'gender' => 'female',
            'employee_code' => $employeeCode,
            'department_id' => $dept->id,
            'job_category_id' => $job->id,
            'location_id' => $loc->id,
            'payment_type' => 'salary',
            'basic_salary' => 50000,
            'currency' => 'KES',
            'payment_mode' => 'bank',
            'account_name' => 'Jane Doe',
            'account_number' => 'ACC-' . uniqid(),
            'bank_name' => 'Test Bank',
            // national_id/tax_no are NOT NULL at the DB level despite being
            // validated as nullable - always supply them unless a test is
            // deliberately exercising a specific national_id collision.
            'national_id' => $nationalId ?? ('NID-' . uniqid()),
            'tax_no' => 'TAX-' . uniqid(),
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'permanent_address' => 'Nairobi',
            'phone' => '0700' . random_int(100000, 999999),
            'employment_term' => 'permanent',
        ];
    }

    public function test_creating_an_employee_with_a_brand_new_email_works_as_before(): void
    {
        Notification::fake();
        [$business, $dept, $job, $loc] = $this->makeBusinessContext('New');
        $admin = User::factory()->create();

        $email = 'brandnew_' . uniqid() . '@example.com';
        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($dept, $job, $loc, $email, 'EMP-' . uniqid()));

        $response->assertCreated();

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('business-employee'));
        $this->assertSame(1, Employee::where('user_id', $user->id)->count());

        Notification::assertSentTo($user, WelcomeEmployeeNotification::class);
    }

    public function test_existing_email_is_reused_as_an_employee_at_a_second_business(): void
    {
        Notification::fake();
        [$bizA, $deptA, $jobA, $locA] = $this->makeBusinessContext('A');
        [$bizB, $deptB, $jobB, $locB] = $this->makeBusinessContext('B');
        $admin = User::factory()->create();

        $sharedEmail = 'shared_' . uniqid() . '@example.com';
        $sharedNationalId = 'NID-' . uniqid();

        // First: create as an employee at business A (brand new account).
        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($deptA, $jobA, $locA, $sharedEmail, 'A-EMP-1', $sharedNationalId))
            ->assertCreated();

        $user = User::where('email', $sharedEmail)->firstOrFail();
        $userIdAfterFirst = $user->id;

        // Second: same email AND same real national ID, added as an
        // employee at a totally different business B.
        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizB->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($deptB, $jobB, $locB, $sharedEmail, 'B-EMP-1', $sharedNationalId));

        $response->assertCreated();

        // No duplicate User was created - same account reused.
        $this->assertSame(1, User::where('email', $sharedEmail)->count());
        $reusedUser = User::where('email', $sharedEmail)->firstOrFail();
        $this->assertSame($userIdAfterFirst, $reusedUser->id);

        // Now has TWO Employee rows, one per business, both resolvable.
        $employees = Employee::where('user_id', $reusedUser->id)->get();
        $this->assertCount(2, $employees);
        $this->assertTrue($employees->contains('business_id', $bizA->id));
        $this->assertTrue($employees->contains('business_id', $bizB->id));

        // activeEmployee() correctly resolves to whichever business is active.
        session(['active_business_slug' => $bizB->slug]);
        $this->assertSame($bizB->id, $reusedUser->fresh()->activeEmployee()->business_id);
        session(['active_business_slug' => $bizA->slug]);
        $this->assertSame($bizA->id, $reusedUser->fresh()->activeEmployee()->business_id);

        // Only a no-link welcome notice was sent for the SECOND (reuse)
        // creation - no password reset mail at all.
        Notification::assertSentToTimes($reusedUser, WelcomeEmployeeNotification::class, 2);
        Notification::assertSentTo($reusedUser, WelcomeEmployeeNotification::class, function ($notification, $channels) use ($bizB, $reusedUser) {
            $mail = $notification->toMail($reusedUser);
            return str_contains($mail->subject, $bizB->company_name);
        });
    }

    public function test_same_email_cannot_be_added_as_an_employee_twice_at_the_same_business(): void
    {
        Notification::fake();
        [$business, $dept, $job, $loc] = $this->makeBusinessContext('Dup');
        $admin = User::factory()->create();
        $email = 'dupe_' . uniqid() . '@example.com';

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($dept, $job, $loc, $email, 'DUP-EMP-1'))
            ->assertCreated();

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($dept, $job, $loc, $email, 'DUP-EMP-2'));

        $response->assertStatus(400);
        $this->assertSame(1, Employee::where('business_id', $business->id)
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->count());
    }

    public function test_identity_numbers_can_repeat_across_businesses_but_not_within_one(): void
    {
        Notification::fake();
        [$bizA, $deptA, $jobA, $locA] = $this->makeBusinessContext('IdA');
        [$bizB, $deptB, $jobB, $locB] = $this->makeBusinessContext('IdB');
        $admin = User::factory()->create();
        $nationalId = 'SHARED-NID-' . uniqid();

        // Same national ID at two DIFFERENT businesses - allowed.
        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($deptA, $jobA, $locA, 'idtest1_' . uniqid() . '@example.com', 'IDA-1', $nationalId))
            ->assertCreated();

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizB->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $this->employeePayload($deptB, $jobB, $locB, 'idtest2_' . uniqid() . '@example.com', 'IDB-1', $nationalId))
            ->assertCreated();

        // Same national ID TWICE within the SAME business - rejected by validation.
        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->postJson(route('employees.store'), $this->employeePayload($deptA, $jobA, $locA, 'idtest3_' . uniqid() . '@example.com', 'IDA-2', $nationalId));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('national_id');
    }
}

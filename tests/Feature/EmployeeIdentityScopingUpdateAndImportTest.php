<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeController;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Same per-business identity-field scoping as EmployeeCrossBusinessEmailReuseTest
 * (which covers store()), extended to update() and the XLSX bulk import() -
 * two businesses can legitimately reuse the same employee_code/national_id/
 * etc, but within ONE business they must still be unique, and an email
 * already belonging to a User is reused (not rejected) when imported as an
 * employee at a different business too.
 */
class EmployeeIdentityScopingUpdateAndImportTest extends TestCase
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
            'company_name' => "Scoping Test {$label} " . uniqid(),
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

    private function makeEmployee(Business $business, Department $dept, JobCategory $job, Location $loc, string $employeeCode, ?string $nationalId = null, ?string $taxNo = null): Employee
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'department_id' => $dept->id,
            'job_category_id' => $job->id,
            'location_id' => $loc->id,
            'employee_code' => $employeeCode,
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => $nationalId ?? ('NID-' . uniqid()),
            'tax_no' => $taxNo ?? ('TAX-' . uniqid()),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $dept->id,
            'job_category_id' => $job->id,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    // ---------- file_number (optional identifier, same per-business scoping) ----------

    public function test_store_saves_an_optional_file_number(): void
    {
        [$business, $dept, $job, $loc] = $this->makeBusinessContext('FileNumStore');
        $admin = User::factory()->create();

        $payload = [
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'filenum_' . uniqid() . '@example.com',
            'gender' => 'female',
            'employee_code' => 'FN-' . uniqid(),
            'file_number' => 'FILE-001',
            'department_id' => $dept->id, 'job_category_id' => $job->id, 'location_id' => $loc->id,
            'payment_type' => 'salary', 'basic_salary' => 50000, 'currency' => 'KES',
            'payment_mode' => 'bank', 'account_name' => 'Jane Doe',
            'account_number' => 'ACC-' . uniqid(), 'bank_name' => 'Test Bank',
            'national_id' => 'NID-' . uniqid(), 'tax_no' => 'TAX-' . uniqid(),
            'date_of_birth' => '1990-01-01', 'marital_status' => 'single',
            'permanent_address' => 'Nairobi', 'phone' => '0700' . random_int(100000, 999999),
            'employment_term' => 'permanent',
        ];

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('employees.store'), $payload);

        $response->assertStatus(201);
        $this->assertSame('FILE-001', Employee::where('business_id', $business->id)->latest('id')->first()->file_number);
    }

    public function test_update_rejects_file_number_colliding_within_the_same_business(): void
    {
        [$business, $dept, $job, $loc] = $this->makeBusinessContext('FileNumDup');

        $existing = $this->makeEmployee($business, $dept, $job, $loc, 'FN-DUP-A');
        $existing->update(['file_number' => 'TAKEN-FILE']);
        $employeeToEdit = $this->makeEmployee($business, $dept, $job, $loc, 'FN-DUP-B');

        $admin = User::factory()->create();
        $request = Request::create('/employees/' . $employeeToEdit->id . '/update', 'POST', [
            'employee_id' => $employeeToEdit->id,
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => $employeeToEdit->user->email,
            'gender' => 'female',
            'employee_code' => $employeeToEdit->employee_code,
            'file_number' => 'TAKEN-FILE', // collides within the SAME business
            'payment_type' => 'salary', 'basic_salary' => 50000, 'currency' => 'KES',
            'payment_mode' => 'bank', 'account_name' => 'Jane Doe',
            'account_number' => 'ACC-' . uniqid(), 'bank_name' => 'Test Bank',
            'national_id' => $employeeToEdit->national_id,
            'date_of_birth' => '1990-01-01', 'marital_status' => 'single',
            'permanent_address' => 'Nairobi', 'phone' => '0700' . random_int(100000, 999999),
            'employment_term' => 'permanent',
        ]);
        $request->setUserResolver(fn () => $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new EmployeeController())->update($request, $employeeToEdit->id);
    }

    // ---------- update() ----------

    public function test_update_allows_reusing_another_businesss_employee_code(): void
    {
        [$bizA, $deptA, $jobA, $locA] = $this->makeBusinessContext('UpA');
        [$bizB, $deptB, $jobB, $locB] = $this->makeBusinessContext('UpB');

        $this->makeEmployee($bizA, $deptA, $jobA, $locA, 'SHARED-CODE');
        $employeeB = $this->makeEmployee($bizB, $deptB, $jobB, $locB, 'ORIGINAL-CODE-B');

        $admin = User::factory()->create();
        $request = Request::create('/employees/' . $employeeB->id . '/update', 'POST', [
            'employee_id' => $employeeB->id,
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => $employeeB->user->email,
            'gender' => 'female',
            // Same code as business A's employee - must be allowed, different business.
            'employee_code' => 'SHARED-CODE',
            'department_id' => $deptB->id, 'job_category_id' => $jobB->id, 'location_id' => $locB->id,
            'payment_type' => 'salary', 'basic_salary' => 50000, 'currency' => 'KES',
            'payment_mode' => 'bank', 'account_name' => 'Jane Doe',
            'account_number' => 'ACC-' . uniqid(), 'bank_name' => 'Test Bank',
            'national_id' => $employeeB->national_id, 'tax_no' => $employeeB->tax_no,
            'date_of_birth' => '1990-01-01', 'marital_status' => 'single',
            'permanent_address' => 'Nairobi', 'phone' => '0700' . random_int(100000, 999999),
            'employment_term' => 'permanent',
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = (new EmployeeController())->update($request, $employeeB->id)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('SHARED-CODE', $employeeB->fresh()->employee_code);
    }

    public function test_update_rejects_employee_code_colliding_within_the_same_business(): void
    {
        [$business, $dept, $job, $loc] = $this->makeBusinessContext('UpDup');

        $this->makeEmployee($business, $dept, $job, $loc, 'TAKEN-CODE');
        $employeeToEdit = $this->makeEmployee($business, $dept, $job, $loc, 'ORIGINAL-CODE');

        $admin = User::factory()->create();
        $request = Request::create('/employees/' . $employeeToEdit->id . '/update', 'POST', [
            'employee_id' => $employeeToEdit->id,
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => $employeeToEdit->user->email,
            'gender' => 'female',
            'employee_code' => 'TAKEN-CODE', // collides within the SAME business
            'payment_type' => 'salary', 'basic_salary' => 50000, 'currency' => 'KES',
            'payment_mode' => 'bank', 'account_name' => 'Jane Doe',
            'account_number' => 'ACC-' . uniqid(), 'bank_name' => 'Test Bank',
            'national_id' => $employeeToEdit->national_id,
            'date_of_birth' => '1990-01-01', 'marital_status' => 'single',
            'permanent_address' => 'Nairobi', 'phone' => '0700' . random_int(100000, 999999),
            'employment_term' => 'permanent',
        ]);
        $request->setUserResolver(fn () => $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        (new EmployeeController())->update($request, $employeeToEdit->id);
    }

    // ---------- import() ----------

    private function buildXlsx(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = sys_get_temp_dir() . '/import_test_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'employees.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function importHeaders(): array
    {
        return [
            'first_name', 'last_name', 'email', 'phone', 'gender', 'employee_code',
            'department', 'job_category', 'location', 'basic_salary', 'currency',
            'payment_mode', 'account_name', 'account_number', 'bank_name',
            'national_id', 'tax_no', 'marital_status', 'date_of_birth', 'permanent_address', 'employment_term',
        ];
    }

    public function test_import_reuses_an_existing_email_as_an_employee_at_a_new_business(): void
    {
        Notification::fake();
        [$bizA, $deptA, $jobA, $locA] = $this->makeBusinessContext('ImpA');
        [$bizB, $deptB, $jobB, $locB] = $this->makeBusinessContext('ImpB');

        $existingEmployee = $this->makeEmployee($bizA, $deptA, $jobA, $locA, 'IMP-A-1');
        $sharedEmail = $existingEmployee->user->email;

        $admin = User::factory()->create();
        $file = $this->buildXlsx($this->importHeaders(), [[
            'Jane', 'Doe', $sharedEmail, '0700111222', 'female', 'IMP-B-1',
            $deptB->name, $jobB->name, '', 50000, 'KES', 'bank', 'Jane Doe', 'ACC-' . uniqid(), 'Test Bank',
            'IMP-NID-' . uniqid(), 'TX-' . substr(uniqid(), -8), 'single', '1990-01-01', 'Nairobi', 'permanent',
        ]]);

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizB->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post('/employees/import', ['file' => $file]);

        $response->assertOk();
        $response->assertJsonPath('successful', 1);
        $response->assertJsonPath('errors', []);

        $this->assertSame(1, User::where('email', $sharedEmail)->count());
        $reusedUser = User::where('email', $sharedEmail)->firstOrFail();
        $this->assertSame($existingEmployee->user_id, $reusedUser->id);
        $this->assertCount(2, Employee::where('user_id', $reusedUser->id)->get());

        Notification::assertSentToTimes($reusedUser, WelcomeEmployeeNotification::class, 1);
    }

    public function test_import_allows_employee_code_reuse_across_businesses_but_not_within_one(): void
    {
        Notification::fake();
        [$bizA, $deptA, $jobA, $locA] = $this->makeBusinessContext('ImpCodeA');
        [$bizB, $deptB, $jobB, $locB] = $this->makeBusinessContext('ImpCodeB');

        $this->makeEmployee($bizA, $deptA, $jobA, $locA, 'CROSS-CODE');
        // Also seed one directly in business B to test the within-business collision.
        $this->makeEmployee($bizB, $deptB, $jobB, $locB, 'INBIZ-DUP');

        $admin = User::factory()->create();
        $file = $this->buildXlsx($this->importHeaders(), [
            // Row 1: same code as business A's employee - different business, must succeed.
            [
                'Row', 'One', 'importrow1_' . uniqid() . '@example.com', '0700111222', 'female', 'CROSS-CODE',
                $deptB->name, $jobB->name, '', 50000, 'KES', 'bank', 'Row One', 'ACC-' . uniqid(), 'Test Bank',
                'IMP-NID-' . uniqid(), 'TX-' . substr(uniqid(), -8), 'single', '1990-01-01', 'Nairobi', 'permanent',
            ],
            // Row 2: collides with business B's OWN existing employee code - must fail (row-level error, not a crash).
            [
                'Row', 'Two', 'importrow2_' . uniqid() . '@example.com', '0700111223', 'female', 'INBIZ-DUP',
                $deptB->name, $jobB->name, '', 50000, 'KES', 'bank', 'Row Two', 'ACC-' . uniqid(), 'Test Bank',
                'IMP-NID-' . uniqid(), 'TX-' . substr(uniqid(), -8), 'single', '1990-01-01', 'Nairobi', 'permanent',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $bizB->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post('/employees/import', ['file' => $file]);

        $response->assertOk();
        $response->assertJsonPath('successful', 1);
        $this->assertCount(1, $response->json('errors'));
        $this->assertStringContainsString('employee code', strtolower($response->json('errors')[0]));
    }
}

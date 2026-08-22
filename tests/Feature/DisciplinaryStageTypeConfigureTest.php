<?php

namespace Tests\Feature;

use App\Http\Controllers\DisciplinaryStageTypeController;
use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warning;
use App\Services\DisciplinaryStageTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Configure" - fully business-configurable disciplinary stages (GUIDE
 * plan Phase 3). Covers the lazy-seed + backfill service and every guard
 * rail: can't delete a stage referenced by a case, must always keep at
 * least one active terminal stage, reordering stays contiguous.
 */
class DisciplinaryStageTypeConfigureTest extends TestCase
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

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'DST-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return [$user->fresh(), $employee->fresh()];
    }

    private function actingAsAdmin()
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        return $this->actingAs($admin)->withSession([
            'active_business_slug' => $business->slug,
            'active_role' => 'business-admin',
            '2fa_verified' => true,
        ]);
    }

    // ---- Seed + backfill -------------------------------------------------

    public function test_ensure_seeded_creates_the_five_stock_stages_in_order(): void
    {
        // Business::find(1) is the shared test business - safe here because
        // each test runs in its own rolled-back transaction, so it never
        // actually has pre-existing stage types at the start of a test.
        $business = Business::find(1);

        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $stages = DisciplinaryStageType::where('business_id', $business->id)->ordered()->get();

        $this->assertSame(5, $stages->count());
        $this->assertSame(['verbal_warning', 'written_warning', 'final_warning', 'suspension', 'termination'], $stages->pluck('slug')->all());
        $this->assertTrue($stages->last()->is_terminal);
        $this->assertFalse($stages->first()->is_terminal);
    }

    public function test_ensure_seeded_backfills_existing_warnings_stage_type_id(): void
    {
        $business = Business::find(1);
        [$issuer, $employee] = $this->makeEmployeeUser();

        $warning = Warning::create([
            'employee_id' => $employee->id,
            'business_id' => $business->id,
            'case_type' => 'final_warning',
            'severity' => 'high',
            'issue_date' => now(),
            'reason' => 'Pre-existing case before stages were configured',
            'status' => 'active',
            'issued_by' => $issuer->id,
        ]);
        $this->assertNull($warning->stage_type_id);

        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $finalStage = DisciplinaryStageType::where('business_id', $business->id)->where('slug', 'final_warning')->first();
        $this->assertSame($finalStage->id, $warning->fresh()->stage_type_id);
    }

    public function test_ensure_seeded_is_idempotent(): void
    {
        $business = Business::find(1);

        $service = app(DisciplinaryStageTypeService::class);
        $service->ensureSeeded($business);
        $service->ensureSeeded($business);

        $this->assertSame(5, DisciplinaryStageType::where('business_id', $business->id)->count());
    }

    // ---- Guard rails -----------------------------------------------------

    public function test_a_stage_referenced_by_a_case_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        [$issuer, $employee] = $this->makeEmployeeUser();
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $stage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'verbal_warning')->first();
        Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'stage_type_id' => $stage->id, 'severity' => 'low', 'issue_date' => now(),
            'reason' => 'DST referenced case', 'status' => 'active', 'issued_by' => $issuer->id,
        ]);

        $this->actingAsAdmin();
        $controller = new DisciplinaryStageTypeController();
        $request = Request::create("/disciplinary-stage-types/{$stage->id}/destroy", 'DELETE');
        $response = $controller->destroy($request, $stage)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(DisciplinaryStageType::find($stage->id));
    }

    public function test_the_last_active_terminal_stage_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $termination = DisciplinaryStageType::where('business_id', 1)->where('slug', 'termination')->first();

        $this->actingAsAdmin();
        $controller = new DisciplinaryStageTypeController();
        $request = Request::create("/disciplinary-stage-types/{$termination->id}/destroy", 'DELETE');
        $response = $controller->destroy($request, $termination)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(DisciplinaryStageType::find($termination->id));
    }

    public function test_the_last_active_terminal_stage_cannot_have_its_terminal_flag_removed(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $termination = DisciplinaryStageType::where('business_id', 1)->where('slug', 'termination')->first();

        $this->actingAsAdmin();
        $controller = new DisciplinaryStageTypeController();
        $request = Request::create("/disciplinary-stage-types/{$termination->id}/update", 'POST', ['is_terminal' => false]);
        $response = $controller->update($request, $termination)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertTrue($termination->fresh()->is_terminal);
    }

    public function test_an_unreferenced_non_terminal_stage_can_be_deleted_and_renumbers_the_rest(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $verbal = DisciplinaryStageType::where('business_id', $business->id)->where('slug', 'verbal_warning')->first();
        $written = DisciplinaryStageType::where('business_id', $business->id)->where('slug', 'written_warning')->first();
        $this->assertSame(1, $verbal->sequence_order);
        $this->assertSame(2, $written->sequence_order);

        session(['active_business_slug' => $business->slug]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $controller = new DisciplinaryStageTypeController();
        $request = Request::create("/disciplinary-stage-types/{$verbal->id}/destroy", 'DELETE');
        $response = $controller->destroy($request, $verbal)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull(DisciplinaryStageType::find($verbal->id));
        // written_warning was #2, verbal (#1) is now gone - it should have
        // been renumbered down to #1, not left with a gap.
        $this->assertSame(1, $written->fresh()->sequence_order);
    }

    public function test_reorder_renumbers_sequence_order_to_match_the_given_order(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);

        $stages = DisciplinaryStageType::where('business_id', $business->id)->ordered()->get();
        $reversedIds = $stages->pluck('id')->reverse()->values()->all();

        session(['active_business_slug' => $business->slug]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $controller = new DisciplinaryStageTypeController();
        $request = Request::create('/disciplinary-stage-types/reorder', 'POST', ['ordered_ids' => $reversedIds]);
        $response = $controller->reorder($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        foreach ($reversedIds as $index => $id) {
            $this->assertSame($index + 1, DisciplinaryStageType::find($id)->sequence_order);
        }
    }
}

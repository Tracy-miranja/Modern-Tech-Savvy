<?php

namespace Tests\Feature;

use App\Http\Controllers\JobPostController;
use App\Models\JobPost;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the slug-stability audit that followed the
 * Business fix (see GUIDE.md discussion / BusinessSlugStabilityTest):
 * Spatie's HasSlug regenerates the slug from its source field on every
 * save() by default, including updates that don't touch the source field
 * at all. Two real, currently-live call sites hit this:
 *  - JobPostController::togglePublic() updates only `is_public`.
 *  - LeaveTypeController::update() is PATCH-semantics ('sometimes' rules)
 *    and routinely saves with only one unrelated field (e.g. excluded_days)
 *    and no `name` in the payload at all.
 * Both models now have doNotGenerateSlugsOnUpdate() in getSlugOptions().
 */
class SlugStabilityOnUpdateTest extends TestCase
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

    public function test_toggling_a_job_posts_public_flag_does_not_change_its_slug(): void
    {
        $user = User::factory()->create();

        // Slug deliberately doesn't match Str::slug(title) - if the trait
        // were still regenerating on update, togglePublic() would rewrite
        // it back to the auto-generated form and this test would catch it.
        $jobPost = JobPost::create([
            'business_id' => 1,
            'title' => 'Senior Backend Engineer ' . uniqid(),
            'slug' => 'custom-job-slug-' . uniqid(),
            'description' => 'Test description',
            'employment_type' => 'full-time',
            'created_by' => $user->id,
        ]);

        $originalSlug = $jobPost->slug;
        $originalIsPublic = (bool) $jobPost->is_public;

        $controller = new JobPostController();
        $request = Request::create('/job-posts/toggle-public', 'POST', ['job_post' => $originalSlug]);
        $response = $controller->togglePublic($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame($originalIsPublic, (bool) $jobPost->fresh()->is_public, 'togglePublic() should still actually toggle the flag.');
        $this->assertSame($originalSlug, $jobPost->fresh()->slug, 'Toggling an unrelated flag must not touch the slug.');
    }

    public function test_leave_type_patch_update_with_no_name_field_does_not_change_its_slug(): void
    {
        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Compassionate Leave ' . uniqid(),
            'slug' => 'custom-leave-slug-' . uniqid(),
        ]);

        $originalSlug = $leaveType->slug;

        // Mirrors LeaveTypeController::update()'s PATCH pattern: only an
        // unrelated field changes, name is never in the payload.
        $leaveType->fill(['excluded_days' => ['saturday', 'sunday']]);
        $leaveType->save();

        $this->assertSame($originalSlug, $leaveType->fresh()->slug);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for a slug-stability bug discovered while building
 * the "Getting Started" setup-progress feature: Business::getSlugOptions()
 * did not call doNotGenerateSlugsOnUpdate(), so Spatie's HasSlug
 * regenerated the slug from company_name on EVERY save() - including
 * updates to completely unrelated fields. Since the slug is the primary
 * identifier in every business URL, session, and route binding throughout
 * the app, an unrelated update could silently invalidate a business's
 * active links/sessions mid-use.
 */
class BusinessSlugStabilityTest extends TestCase
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

    public function test_updating_an_unrelated_field_does_not_change_the_business_slug(): void
    {
        $business = Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => 'Slug Stability Co ' . uniqid(),
            'slug' => 'slug-stability-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'code' => 'SS' . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);

        $originalSlug = $business->slug;

        $business->update(['setup_guide_dismissed_at' => now()]);
        $this->assertSame($originalSlug, $business->fresh()->slug);

        $business->update(['phone' => '0711111111']);
        $this->assertSame($originalSlug, $business->fresh()->slug);
    }
}

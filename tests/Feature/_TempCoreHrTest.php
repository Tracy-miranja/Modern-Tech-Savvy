<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use Tests\TestCase;

class _TempCoreHrTest extends TestCase
{
    public function test_assign_core_hr_management(): void
    {
        $user = User::find(37);

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'krest', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->postJson('/businesses/krest/clients/grace-tech/modules/assign', [
                'modules' => [1, 7], // core-hr-management + asset-management
            ]);

        fwrite(STDERR, "\n--- STATUS: " . $response->getStatusCode() . " ---\n");
        fwrite(STDERR, "--- BODY: " . substr($response->getContent(), 0, 1000) . " ---\n");

        $business = Business::findBySlug('grace-tech');
        $rows = $business->modules()->get(['modules.id', 'modules.slug'])->map(fn($m) => $m->slug . ':' . $m->pivot->is_active)->implode(', ');
        fwrite(STDERR, "--- business_modules after assign: $rows ---\n");
    }
}

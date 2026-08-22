<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PHPUnit runs every test in one shared PHP process, unlike a real
     * request - Business::findBySlug()/hasModule() memoize per-process
     * for performance (see Business.php's docblock on those caches),
     * which would otherwise leak a business object from one test's
     * since-rolled-back transaction into the next test as stale data.
     * Runs before each test's own DB transaction begins.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Business::clearRequestCaches();
    }
}

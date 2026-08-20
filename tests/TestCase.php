<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent model observers (CacheInvalidationObserver, etc.) from firing
        // during tests. This avoids memory accumulation from event/listener chains
        // across hundreds of test methods. Tests that need events can call
        // Event::fake(null) to restore real dispatching.
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

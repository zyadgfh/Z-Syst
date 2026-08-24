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
        // during tests. This avoids memory accumulation from event/listener chains.
        // Note: This also blocks eloquent model events (creating, created), so
        // auto-generation of po_number, invoice_number, payment_number must be
        // handled by factories and services, not model boot() methods.
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

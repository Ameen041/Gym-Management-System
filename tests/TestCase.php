<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // settings are kept in memory for the whole PHP process: each test starts from its own database's
        (new \ReflectionProperty(Setting::class, 'cache'))->setValue(null, null);
    }
}

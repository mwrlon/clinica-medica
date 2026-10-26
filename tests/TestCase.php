<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // As páginas usam @vite(...): sem isto os testes exigiriam "npm run build" antes.
        $this->withoutVite();
    }
}

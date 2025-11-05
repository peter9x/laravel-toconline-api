<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;

class TOConlineClientTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Mupy\TOConline\TOConlineServiceProvider::class];
    }

    /** @test */
    public function it_work()
    {
        $this->assertTrue(true);
    }
}

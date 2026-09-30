<?php

namespace Tests\Feature;

use Mupy\TOConline\TOConlineServiceProvider;
use Orchestra\Testbench\TestCase;

class TOConlineClientTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TOConlineServiceProvider::class];
    }

    /** @test */
    public function it_work()
    {
        $this->assertTrue(true);
    }
}

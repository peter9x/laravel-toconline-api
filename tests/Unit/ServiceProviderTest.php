<?php

namespace Tests\Unit;

use Mupy\TOConline\TOConlineServiceProvider;
use Mupy\TOConline\Facades\TOConline;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TOConlineServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'TOConline' => TOConline::class,
        ];
    }

    /** @test */
    public function it_registers_the_business_central_client()
    {
        $client = TOConline::getClient();
        $this->assertNotNull($client);
    }
}

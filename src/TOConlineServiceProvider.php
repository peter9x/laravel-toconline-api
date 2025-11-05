<?php

namespace Mupy\TOConline;

use Illuminate\Support\ServiceProvider;

class TOConlineServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/toconline.php' => config_path('toconline.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/toconline.php',
            'toconline'
        );

        $this->app->singleton(TOConlineClient::class, function ($app) {
            /** @var array{
             *     connections: array<string, array{client_id: string, secret: string}>,
             *     api_url: string
             * } $config */
            $config = config('toconline');

            return new TOConlineClient($config);
        });

        $this->app->alias(TOConlineClient::class, 'toconline');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Mupy\TOConline\TOConlineClient;
use Mupy\TOConline\TOConlineServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class OAuthFlowTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TOConlineServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('toconline.connections.default', ['client_id' => 'id', 'client_secret' => 'secret']);
        $app['config']->set('toconline.base_url_oauth', 'https://toc.test/oauth');
        $app['config']->set('toconline.redirect_uri_oauth', 'https://app.test/toconline/oauth/callback');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('toconline.routes.middleware', ['web']);
    }

    private function auth()
    {
        return $this->app->make(TOConlineClient::class)->api()->auth();
    }

    #[Test]
    public function callback_exchanges_code_and_stores_tokens()
    {
        Http::fake(['toc.test/oauth/token' => Http::response(['access_token' => 'A1', 'refresh_token' => 'R1', 'expires_in' => 14400])]);

        $redirect = $this->get('/toconline/oauth/authorize');
        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->get('/toconline/oauth/callback?code=CODE&state='.$query['state'])->assertOk();

        Http::assertSent(fn ($request) => $request['grant_type'] === 'authorization_code' && $request['code'] === 'CODE');
        $this->assertSame('A1', $this->auth()->getBearer());
    }

    #[Test]
    public function callback_rejects_unknown_state()
    {
        $this->get('/toconline/oauth/callback?code=CODE&state=forged')->assertForbidden();
    }

    #[Test]
    public function expired_access_token_is_renewed_with_refresh_token()
    {
        Http::fake(['toc.test/oauth/token' => Http::sequence()
            ->push(['access_token' => 'A1', 'refresh_token' => 'R1', 'expires_in' => 14400])
            ->push(['access_token' => 'A2', 'refresh_token' => 'R2', 'expires_in' => 14400]),
        ]);

        $this->auth()->exchangeAuthorizationCode('CODE');
        $this->auth()->forgetAccessToken();

        $this->assertSame('A2', $this->auth()->getBearer());
        Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'R1');
    }

    #[Test]
    public function expired_refresh_token_asks_for_manual_authorization()
    {
        Http::fake([
            'toc.test/oauth/token' => Http::sequence()
                ->push(['access_token' => 'A1', 'refresh_token' => 'R1', 'expires_in' => 14400])
                ->push(['error' => 'invalid_grant'], 400),
            'toc.test/oauth/auth*' => Http::response('<html>login</html>', 200),
        ]);

        $this->auth()->exchangeAuthorizationCode('CODE');
        $this->auth()->forgetAccessToken();

        try {
            $this->auth()->getBearer();
            $this->fail('Expected exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('autorizar a aplicação manualmente', $e->getMessage());
            $this->assertStringContainsString('Expected 302 response, got 200', $e->getMessage());
        }

        $this->assertFalse($this->auth()->isAuthorized());
    }

    #[Test]
    public function refresh_command_renews_stored_tokens()
    {
        Http::fake(['toc.test/oauth/token' => Http::sequence()
            ->push(['access_token' => 'A1', 'refresh_token' => 'R1', 'expires_in' => 14400])
            ->push(['access_token' => 'A2', 'refresh_token' => 'R2', 'expires_in' => 14400]),
        ]);

        $this->auth()->exchangeAuthorizationCode('CODE');

        $this->artisan('toconline:refresh-token')->assertSuccessful();
        $this->assertSame('A2', $this->auth()->getBearer());
    }
}

<?php

declare(strict_types=1);

namespace Mupy\TOConline\Console;

use Illuminate\Console\Command;
use Mupy\TOConline\TOConlineClient;
use Throwable;

final class TestConnectionCommand extends Command
{
    protected $signature = 'toconline:test
        {connection=default : Connection name}
        {--code= : authorization_code copied from the browser redirect URL}
        {--refresh : Force a token refresh and check if the refresh_token is rotated}';

    protected $description = 'Test the TOConline connection: configuration, authorization, tokens and an API request';

    public function handle(TOConlineClient $toconline): int
    {
        $connection = (string) $this->argument('connection');

        $this->components->info("TOConline: a testar a ligação [{$connection}]");

        // 1. Configuration
        try {
            $client = $toconline->api($connection)->cache(false);
        } catch (Throwable $e) {
            $this->components->error('Configuração inválida: '.$e->getMessage());

            return self::FAILURE;
        }

        $auth = $client->auth();
        $cacheStore = config('cache.default');
        $cacheDriver = config("cache.stores.{$cacheStore}.driver");

        $this->components->twoColumnDetail('client_id', $this->mask((string) config("toconline.connections.{$connection}.client_id")));
        $this->components->twoColumnDetail('base_url', (string) config('toconline.base_url'));
        $this->components->twoColumnDetail('base_url_oauth', (string) config('toconline.base_url_oauth'));
        $this->components->twoColumnDetail('redirect_uri_oauth', (string) config('toconline.redirect_uri_oauth'));
        $this->components->twoColumnDetail('cache', "{$cacheStore} ({$cacheDriver})");

        if (in_array($cacheDriver, ['array', 'null'], true)) {
            $this->components->warn("A cache '{$cacheDriver}' não é persistente: os tokens perdem-se no fim de cada processo.");
        }

        // 2. Authorization
        if ($this->option('code')) {
            if (! $this->step('Trocar authorization_code por tokens', fn () => $auth->exchangeAuthorizationCode((string) $this->option('code')))) {
                return self::FAILURE;
            }
        } elseif (! $auth->isAuthorized()) {
            $this->components->warn('Sem tokens em cache. A tentar obter o authorization_code sem browser (fluxo antigo)...');

            $authorized = $this->step('Obter authorization_code sem browser', function () use ($auth) {
                $auth->exchangeAuthorizationCode($auth->oauthAuthorizationCode());
            });

            if (! $authorized) {
                $this->newLine();
                $this->line('  É necessário autorizar pelo browser:');
                if (! $this->runningInTestbench()) {
                    $this->line('  - com sessão iniciada na app, abrir <comment>'.$this->authorizeRoute($connection).'</comment>, ou');
                }
                $this->line('  - abrir o URL abaixo, fazer login, copiar o parâmetro <comment>code</comment> do URL de redirect e correr:');
                $this->line("    <comment>{$this->artisanBinary()} toconline:test {$connection} --code=CODIGO</comment>");
                $this->newLine();
                $this->line('  '.$auth->getAuthorizationUrl());

                return self::FAILURE;
            }
        } else {
            $this->components->twoColumnDetail('Autorização', '<fg=green>tokens em cache</>');
        }

        // 3. Refresh (optional)
        if ($this->option('refresh')) {
            $tokenData = [];
            if (! $this->step('Renovar tokens com refresh_token', function () use ($auth, &$tokenData) {
                $tokenData = $auth->refreshStoredTokens();
            })) {
                return self::FAILURE;
            }

            $this->components->twoColumnDetail(
                'refresh_token rodado',
                ($tokenData['refresh_token_rotated'] ?? false)
                    ? '<fg=green>sim</> (o agendamento mantém a ligação ativa)'
                    : '<fg=yellow>não</> (expira 8h após o login, será preciso autorizar de novo)'
            );
        }

        // 4. Bearer token
        $bearer = '';
        if (! $this->step('Obter access_token', function () use ($auth, &$bearer) {
            $bearer = $auth->getBearer();
        })) {
            return self::FAILURE;
        }
        $this->components->twoColumnDetail('access_token', $this->mask($bearer));

        // 5. API request: list 10 customers
        $response = [];
        if (! $this->step('GET /api/customers (10 clientes)', function () use ($client, &$response) {
            $response = $client->customers()->paginate(1, 10);
        })) {
            return self::FAILURE;
        }

        $customers = $response['data'] ?? [];
        $this->components->twoColumnDetail('Clientes devolvidos', (string) count($customers));

        if ($customers !== []) {
            $this->table(
                ['ID', 'Nome', 'NIF', 'Email'],
                array_map(fn (array $customer) => [
                    $customer['id'] ?? '-',
                    $customer['attributes']['business_name'] ?? '-',
                    $customer['attributes']['tax_registration_number'] ?? '-',
                    $customer['attributes']['email'] ?? '-',
                ], $customers)
            );
        }

        $this->newLine();
        $this->components->info('Ligação ao TOConline OK.');

        return self::SUCCESS;
    }

    private function step(string $description, callable $callback): bool
    {
        try {
            $callback();
            $this->components->twoColumnDetail($description, '<fg=green;options=bold>DONE</>');

            return true;
        } catch (Throwable $e) {
            $this->components->twoColumnDetail($description, '<fg=red;options=bold>FAIL</>');
            $this->components->error($e->getMessage());

            for ($previous = $e->getPrevious(); $previous !== null; $previous = $previous->getPrevious()) {
                if (! str_contains($e->getMessage(), $previous->getMessage())) {
                    $this->line('  <fg=gray>Causa: '.$previous->getMessage().'</>');
                }
            }

            return false;
        }
    }

    private function runningInTestbench(): bool
    {
        return $this->laravel->bound('TESTBENCH_COMMANDER');
    }

    private function artisanBinary(): string
    {
        return $this->runningInTestbench() ? 'vendor/bin/testbench' : 'php artisan';
    }

    private function authorizeRoute(string $connection): string
    {
        try {
            return route('toconline.authorize', ['connection' => $connection]);
        } catch (Throwable) {
            return "/toconline/oauth/authorize/{$connection}";
        }
    }

    private function mask(string $value): string
    {
        return $value === '' ? '<fg=red>(vazio)</>' : substr($value, 0, 6).'…';
    }
}

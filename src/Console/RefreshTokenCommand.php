<?php

declare(strict_types=1);

namespace Mupy\TOConline\Console;

use Illuminate\Console\Command;
use Mupy\TOConline\TOConlineClient;
use Throwable;

final class RefreshTokenCommand extends Command
{
    protected $signature = 'toconline:refresh-token {connection? : Connection name (defaults to all connections)}';

    protected $description = 'Renew the TOConline access_token using the stored refresh_token';

    public function handle(TOConlineClient $toconline): int
    {
        $connections = $this->argument('connection')
            ? [$this->argument('connection')]
            : $toconline->connections();

        $failed = false;

        foreach ($connections as $connection) {
            $auth = $toconline->api($connection)->auth();

            try {
                $auth->refreshStoredTokens();
                $this->info("[{$connection}] Token renovado.");
            } catch (Throwable $e) {
                $failed = true;
                $this->error("[{$connection}] {$e->getMessage()}");
                $this->line('Autorize novamente em: '.route('toconline.authorize', ['connection' => $connection]));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

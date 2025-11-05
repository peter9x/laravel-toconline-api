<?php

namespace Mupy\TOConline;

use InvalidArgumentException;

class TOConlineClient
{
    private array $config;

    public function __construct(array $config)
    {
        if (! isset($config['connections']) || ! is_array($config['connections'])) {
            throw new InvalidArgumentException("Config must have a 'connections' array.");
        }
        if (! isset($config['base_url']) || ! isset($config['base_url_oauth']) || ! isset($config['redirect_uri_oauth'])) {
            throw new InvalidArgumentException("Config must have an 'base_url' or 'base_url_oauth' or 'redirect_uri_oauth' defined.");
        }

        $this->config = $config;
    }

    public function api(string $connectionName = 'default'): TOCClient
    {
        if (! isset($this->config['connections'][$connectionName])) {
            throw new InvalidArgumentException("The Business Central connection '{$connectionName}' doesn't exist.");
        }

        $connection = $this->config['connections'][$connectionName];

        return new TOCClient(
            $connection,
            baseUrl: $this->config['base_url'],
            baseUrlOAuth: $this->config['base_url_oauth']
        );
    }
}

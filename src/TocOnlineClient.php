<?php

namespace Mupy\TOConline;

use InvalidArgumentException;
use RuntimeException;

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
            throw new InvalidArgumentException("The Toconline connection '{$connectionName}' doesn't exist.");
        }

        $connection = $this->config['connections'][$connectionName];

        throw_if(empty($connection['client_id']), RuntimeException::class, 'TOC_CLIENT_ID is required.');
        throw_if(empty($connection['client_secret']), RuntimeException::class, 'TOC_CLIENT_SECRET is required.');
        throw_if(empty($this->config['base_url']), RuntimeException::class, 'base_url is required.');
        throw_if(empty($this->config['base_url_oauth']), RuntimeException::class, 'base_url_oauth is required.');
        throw_if(empty($this->config['redirect_uri_oauth']), RuntimeException::class, 'TOC_URI_OAUTH is required.');

        return new TOCClient(
            client_id: $connection['client_id'],
            client_secret: $connection['client_secret'],
            baseUrl: $this->config['base_url'],
            baseUrlOAuth: $this->config['base_url_oauth'],
            redirectUriOauth: $this->config['redirect_uri_oauth']
        );
    }
}

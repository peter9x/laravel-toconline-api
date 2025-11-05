<?php

namespace Mupy\TOConline;

use GuzzleHttp\Client;
use Mupy\TOConline\Auth\OAuth2Client;

class TOCClient
{
    private Client $http;
    private string $accessToken;

    public function __construct(
        array $config,
        string $redirect_uri_oauth,
        private string $baseUrl,
        private string $baseUrlOAuth
    ) {

        $this->http = new Client(['base_uri' => $this->baseUrl]);

        $oauthClient = new OAuth2Client(
            $this->baseUrlOAuth,
            $config['client_id'],
            $config['client_secret'],
            $redirect_uri_oauth
        );

        $this->accessToken = $oauthClient->getAccessToken();
    }

    public function request(string $method, string $uri, array $body = [])
    {
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/vnd.api+json'
            ]
        ];

        if (!empty($body)) {
            $options['json'] = $body;
        }

        $response = $this->http->request($method, $uri, $options);
        return json_decode($response->getBody(), true);
    }
}

<?php

declare(strict_types=1);

namespace Mupy\TOConline;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Mupy\TOConline\Auth\TOConlineAuth;
use RuntimeException;

final class TOCClient
{
    private readonly Client $http;

    private readonly TOConlineAuth $oauthClient;

    private ?string $accessToken = null;

    public function __construct(
        string $client_id,
        string $client_secret,
        string $baseUrlOAuth,
        string $redirectUriOauth,
        private readonly string $baseUrl
    ) {
        $this->http = new Client(['base_uri' => rtrim($this->baseUrl, '/').'/']);

        $this->oauthClient = new TOConlineAuth(
            $client_id,
            $client_secret,
            $baseUrlOAuth,
            $redirectUriOauth
        );
    }

    /**
     * Lazily fetches or refreshes OAuth access token.
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken === null) {
            $this->accessToken = $this->oauthClient->getBearer();
        }

        return $this->accessToken;
    }

    /**
     * Sends authenticated HTTP requests to TOConline API.
     * Automatically retries once on 401 (token expired).
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  string  $uri  Endpoint URI (relative)
     * @param  array  $body  Optional JSON body
     * @return array Decoded JSON response
     *
     * @throws RuntimeException|\Throwable
     */
    public function request(string $method, string $uri, array $body = []): array
    {
        return $this->sendRequest($method, $uri, $body, retry: true);
    }

    /**
     * Internal request handler with controlled retry logic.
     */
    private function sendRequest(string $method, string $uri, array $body, bool $retry): array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/vnd.api+json',
            ],
        ];

        if (! empty($body)) {
            $options['json'] = $body;
        }

        try {
            $response = $this->http->request($method, ltrim($uri, '/'), $options);
            $decoded = json_decode($response->getBody()->getContents(), true);

            return is_array($decoded) ? $decoded : [];
        } catch (ClientException $e) {
            $status = $e->getResponse()?->getStatusCode();

            // Retry once if 401 (token expired)
            if ($status === 401 && $retry) {
                // Force token refresh and retry once
                $this->accessToken = $this->oauthClient->getBearer();

                return $this->sendRequest($method, $uri, $body, retry: false);
            }

            // Re-throw with better message
            $msg = sprintf(
                'TOConline API request failed (%s %s): %s',
                strtoupper($method),
                $uri,
                $e->getMessage()
            );
            throw new RuntimeException($msg, $status ?? 0, $e);
        }
    }

    public function documents(): \Mupy\TOConline\Support\TOCQueryBuilder
    {
        return \Mupy\TOConline\Support\TOCQueryBuilder::make($this, '/api/v1/commercial_sales_documents');
    }

    public function getDocument(int|string $id): \Mupy\TOConline\DTO\SalesDocument
    {
        $response = $this->request('GET', "/api/v1/commercial_sales_documents/{$id}");

        return \Mupy\TOConline\DTO\SalesDocument::fromArray($response);
    }
}

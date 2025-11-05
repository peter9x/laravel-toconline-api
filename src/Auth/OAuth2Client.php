<?php

namespace Mupy\TOConline\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class OAuth2Client
{
    private string $authUrl;
    private string $tokenUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private Client $http;

    private ?string $accessToken = null;
    private ?int $expiresIn = null;
    private ?int $tokenIssuedAt = null;

    public function __construct(string $baseUrlOAuth, string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->authUrl = rtrim($baseUrlOAuth, '/') . '/auth';
        $this->tokenUrl = rtrim($baseUrlOAuth, '/') . '/token';
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->http = new Client([
            'timeout' => 10,
            'verify' => true
        ]);
    }

    /**
     * Retrieve and cache an OAuth2 token using the client_credentials flow.
     * Automatically reuses cached token if valid.
     */
    public function getAccessToken(): string
    {
        $cached = Cache::get("toconline.oauth.token-{$this->clientId}");

        if ($cached && isset($cached['access_token'], $cached['expires_in'], $cached['token_issued_at'])) {
            $this->accessToken = $cached['access_token'];
            $this->expiresIn = $cached['expires_in'];
            $this->tokenIssuedAt = $cached['token_issued_at'];

            if (!$this->isTokenExpired()) {
                return $this->accessToken;
            }
        }

        try {
            $response = $this->http->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'commercial',
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if (empty($data['access_token'])) {
                throw new \RuntimeException('Invalid OAuth2 token response: missing access_token');
            }

            $this->accessToken = $data['access_token'];
            $this->expiresIn = $data['expires_in'] ?? 3600;
            $this->tokenIssuedAt = time();

            $ttlSeconds = $this->expiresIn - 30; // leave buffer
            Cache::put("toconline.oauth.token-{$this->clientId}", [
                'access_token' => $this->accessToken,
                'expires_in' => $this->expiresIn,
                'token_issued_at' => $this->tokenIssuedAt,
            ], $ttlSeconds);

            return $this->accessToken;
        } catch (RequestException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : $e->getMessage();

            throw new \RuntimeException("Failed to obtain OAuth2 token: " . $body);
        }
    }

    /**
     * Checks if the cached token has expired.
     */
    private function isTokenExpired(): bool
    {
        if (!$this->accessToken || !$this->expiresIn || !$this->tokenIssuedAt) {
            return true;
        }

        $elapsed = time() - $this->tokenIssuedAt;
        return $elapsed >= ($this->expiresIn - 30);
    }

    /**
     * Returns the authorization URL (useful if switching to interactive mode later)
     */
    public function getAuthorizationUrl(array $params = []): string
    {
        $defaultParams = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'commercial',
            'state' => bin2hex(random_bytes(16))
        ];

        $query = http_build_query(array_merge($defaultParams, $params));
        return "{$this->authUrl}?{$query}";
    }
}

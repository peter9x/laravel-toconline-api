<?php

declare(strict_types=1);

namespace Mupy\TOConline\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class TOConlineAuth
{
    private readonly string $oauthUrl;

    private string $scope = 'commercial';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        string $oauthUrl,
        private readonly string $redirectUri
    ) {
        $this->oauthUrl = rtrim($oauthUrl, '/');
    }

    protected static function getCacheKey(string $clientId, string $key): string
    {
        return "toconline_{$key}_".sha1($clientId);
    }

    /**
     * Generate the authorization URL for user login and consent.
     */
    public function getAuthorizationUrl(): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope,
        ]);

        return "{$this->oauthUrl}/auth?{$query}";
    }

    /**
     * Obtain authorization code via redirect resolution (if supported).
     *
     * @throws RuntimeException
     */
    public function oauthAuthorizationCode(string $key = 'code'): string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ])->withOptions([
            'allow_redirects' => true,
            'max' => 10,
        ])->get($this->getAuthorizationUrl());

        $effectiveUrl = $response->transferStats->getEffectiveUri() ?? null;

        if ($effectiveUrl) {
            $parts = parse_url((string) $effectiveUrl);
            parse_str($parts['query'] ?? '', $queryParams);

            if (isset($queryParams[$key])) {
                return (string) $queryParams[$key];
            }
        }

        throw new RuntimeException('Falha ao obter authorization_code.');
    }

    /**
     * Exchange an authorization code for an access token.
     *
     * @throws RuntimeException
     */
    public function requestAccessToken(string $authorizationCode = ''): array
    {
        $authorizationCode = $authorizationCode !== ''
            ? $authorizationCode
            : $this->oauthAuthorizationCode();

        $authorization = 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}");

        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $authorization,
            ])
            ->post("{$this->oauthUrl}/token", [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'scope' => $this->scope,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao obter access_token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'] ?? throw new RuntimeException('access_token ausente na resposta.'),
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'refresh_token' => $data['refresh_token'] ?? null,
            'created_at' => time(),
        ];
    }

    /**
     * Refresh the access token using a stored or provided refresh_token.
     *
     * @throws RuntimeException
     */
    public function refreshAccessToken(?string $refreshToken = null): array
    {
        $refreshToken = $refreshToken
            ?? Cache::get(self::getCacheKey($this->clientId, 'refresh_token'))
            ?? throw new RuntimeException('Nenhum refresh_token encontrado.');

        $authorization = 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}");

        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $authorization,
            ])
            ->post("{$this->oauthUrl}/token", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'scope' => $this->scope,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Erro ao renovar access_token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'] ?? throw new RuntimeException('access_token ausente na resposta.'),
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'created_at' => time(),
        ];
    }

    /**
     * Retrieve or refresh the bearer token reliably.
     */
    public function getBearer(): string
    {
        $cacheKey = self::getCacheKey($this->clientId, 'access_token');
        $lockKey = "{$cacheKey}_lock";

        $tokenData = Cache::get($cacheKey);

        $isExpired = true;
        if (is_array($tokenData) && isset($tokenData['created_at'], $tokenData['expires_in'])) {
            $expiresAt = $tokenData['created_at'] + $tokenData['expires_in'];
            $isExpired = (time() >= $expiresAt - 30); // refresh 30s early
        }

        if (! $isExpired && isset($tokenData['access_token'])) {
            return $tokenData['access_token'];
        }

        // Prevent concurrent refreshes
        $lock = Cache::lock($lockKey, 10);

        try {
            if ($lock->get()) {
                // Re-check under lock (another process may have refreshed)
                $tokenData = Cache::get($cacheKey);
                if (is_array($tokenData) && isset($tokenData['created_at'], $tokenData['expires_in'])) {
                    $expiresAt = $tokenData['created_at'] + $tokenData['expires_in'];
                    $isExpired = (time() >= $expiresAt - 30);
                    if (! $isExpired && isset($tokenData['access_token'])) {
                        return $tokenData['access_token'];
                    }
                }

                // Refresh or request new
                $tokenData = ! empty($tokenData['refresh_token'])
                    ? $this->refreshAccessToken($tokenData['refresh_token'])
                    : $this->requestAccessToken();

                // Cache tokens
                $ttl = max(60, ($tokenData['expires_in'] ?? 3600) - 30); // never less than 1 min
                Cache::put($cacheKey, $tokenData, $ttl);
                Cache::put(
                    self::getCacheKey($this->clientId, 'refresh_token'),
                    $tokenData['refresh_token'],
                    now()->addDays(30)
                );

                return $tokenData['access_token'];
            }

            usleep(200_000);

            return $this->getBearer();
        } finally {
            optional($lock)->release();
        }
    }
}

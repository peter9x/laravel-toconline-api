<?php

declare(strict_types=1);

namespace Mupy\TOConline\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class TOConlineAuth
{
    private readonly string $oauthUrl;

    private string $scope = 'commercial';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        string $oauthUrl,
        private readonly string $redirectUri,
        private readonly int $refreshTokenTtl = 28800
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
    public function getAuthorizationUrl(?string $state = null): string
    {
        $query = http_build_query(array_filter([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => $this->scope,
            'state' => $state,
        ]));

        return "{$this->oauthUrl}/auth?{$query}";
    }

    /**
     * Obtain authorization code via redirect resolution, without user interaction.
     * TOConline no longer supports this for most accounts: the code must be obtained
     * by a user through getAuthorizationUrl() and handled by exchangeAuthorizationCode().
     *
     * @throws RuntimeException
     */
    public function oauthAuthorizationCode(string $key = 'code'): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
            ])
                ->withOptions([
                    'allow_redirects' => false,
                ])
                ->get($this->getAuthorizationUrl());

            if ($response->status() !== 302) {
                throw new RuntimeException('Expected 302 response, got '.$response->status().': '.Str::limit($response->body(), 300));
            }

            $location = $response->header('Location');

            if (! $location) {
                throw new RuntimeException('Missing Location header in OAuth response.');
            }

            $parts = parse_url($location);
            parse_str($parts['query'] ?? '', $queryParams);

            if (isset($queryParams['error'])) {
                throw new RuntimeException(
                    "TOConline devolveu error={$queryParams['error']}. Confirme que o redirect_uri ({$this->redirectUri}) "
                    .'é exatamente o registado nos dados API do TOConline.'
                );
            }

            if (! isset($queryParams[$key])) {
                throw new RuntimeException('Authorization code not found in Location header: '.$location);
            }

            return (string) $queryParams[$key];
        } catch (\Throwable $th) {
            throw new RuntimeException(
                'Falha ao obter authorization_code ('.$th->getMessage().'). '
                .'É necessário autorizar a aplicação manualmente em: '.$this->getAuthorizationUrl(),
                previous: $th
            );
        }
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
                'redirect_uri' => $this->redirectUri,
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
     * Exchange the code received on the OAuth callback and store the resulting tokens.
     *
     * @throws RuntimeException
     */
    public function exchangeAuthorizationCode(string $authorizationCode): array
    {
        $tokenData = $this->requestAccessToken($authorizationCode);
        $this->storeTokens($tokenData);

        return $tokenData;
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
            ?? throw new RuntimeException('Nenhum refresh_token encontrado. É necessário autorizar a aplicação em: '.$this->getAuthorizationUrl());

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
            'refresh_token_rotated' => isset($data['refresh_token']) && $data['refresh_token'] !== $refreshToken,
        ];
    }

    /**
     * Refresh with the stored refresh_token and store the new tokens.
     * Meant to be scheduled so the refresh_token (8h) never expires.
     *
     * @throws RuntimeException
     */
    public function refreshStoredTokens(): array
    {
        $tokenData = $this->refreshAccessToken();
        $this->storeTokens($tokenData);

        return $tokenData;
    }

    /**
     * Persist tokens: the access_token until it expires, the refresh_token for its own lifetime.
     */
    public function storeTokens(array $tokenData): void
    {
        $ttl = max(60, ($tokenData['expires_in'] ?? 3600) - 30); // never less than 1 min
        Cache::put(self::getCacheKey($this->clientId, 'access_token'), $tokenData, $ttl);

        if (empty($tokenData['refresh_token'])) {
            return;
        }

        $refreshKey = self::getCacheKey($this->clientId, 'refresh_token');

        // A reused refresh_token keeps its original expiry, only a new one gets a full lifetime
        if (($tokenData['refresh_token_rotated'] ?? true) || ! Cache::has($refreshKey)) {
            Cache::put($refreshKey, $tokenData['refresh_token'], max(60, $this->refreshTokenTtl - 60));
        }
    }

    /**
     * Drop the cached access_token so the next getBearer() refreshes it.
     */
    public function forgetAccessToken(): void
    {
        Cache::forget(self::getCacheKey($this->clientId, 'access_token'));
    }

    /**
     * Whether a refresh_token is available, i.e. no manual authorization is needed.
     */
    public function isAuthorized(): bool
    {
        return Cache::has(self::getCacheKey($this->clientId, 'refresh_token'))
            || Cache::has(self::getCacheKey($this->clientId, 'access_token'));
    }

    /**
     * Retrieve or refresh the bearer token reliably.
     */
    public function getBearer(): string
    {
        $cacheKey = self::getCacheKey($this->clientId, 'access_token');
        $lockKey = "{$cacheKey}_lock";

        $accessToken = $this->validAccessToken(Cache::get($cacheKey));

        if ($accessToken !== null) {
            return $accessToken;
        }

        // Prevent concurrent refreshes
        $lock = Cache::lock($lockKey, 10);

        try {
            if ($lock->get()) {
                // Re-check under lock (another process may have refreshed)
                $accessToken = $this->validAccessToken(Cache::get($cacheKey));
                if ($accessToken !== null) {
                    return $accessToken;
                }

                $refreshKey = self::getCacheKey($this->clientId, 'refresh_token');
                $refreshToken = Cache::get($refreshKey);
                $tokenData = null;

                if (! empty($refreshToken)) {
                    try {
                        $tokenData = $this->refreshAccessToken($refreshToken);
                    } catch (RuntimeException $e) {
                        // refresh_token expired or revoked: a new authorization is required
                        Cache::forget($refreshKey);
                        report($e);
                    }
                }

                $tokenData ??= $this->requestAccessToken();

                $this->storeTokens($tokenData);

                return $tokenData['access_token'];
            }

            usleep(200_000);

            return $this->getBearer();
        } finally {
            optional($lock)->release();
        }
    }

    private function validAccessToken(mixed $tokenData): ?string
    {
        if (! is_array($tokenData) || ! isset($tokenData['created_at'], $tokenData['expires_in'], $tokenData['access_token'])) {
            return null;
        }

        $expiresAt = $tokenData['created_at'] + $tokenData['expires_in'];

        return time() < $expiresAt - 30 ? $tokenData['access_token'] : null; // refresh 30s early
    }
}

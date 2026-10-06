<p align="center">
    <a href="https://packagist.org/packages/peter9x/laravel-toconline-api">
        <img src="https://img.shields.io/packagist/v/peter9x/laravel-toconline-api?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/peter9x/laravel-toconline-api">
        <img src="https://img.shields.io/packagist/l/peter9x/laravel-toconline-api?style=for-the-badge" alt="License"></a>
</p>

# laravel-toconline-api

Laravel Toconline API

## Installation

1. Install the package via Composer:

```bash
composer require peter9x/laravel-toconline-api
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --provider="Mupy\\TOConline\\TOConlineServiceProvider" --tag=config
```

3. Add the following to your `.env` file:

```env
TOC_CLIENT_ID=your-client-id
TOC_CLIENT_SECRET=your-client-secret
TOC_URI_OAUTH=https://your-app.com/toconline/oauth/callback
```
`TOC_URI_OAUTH` must match the redirect URI registered for your API credentials on TOConline.

4. Optional config to add on the `.env` file:
```env
TOC_BASE_URL=https://example.com
TOC_BASE_URL_OAUTH=https://example.com/oauth
TOC_REFRESH_TOKEN_TTL=28800
```

## Authorization

TOConline only supports the `authorization_code` grant: a user has to log in and authorize the app once.
The tokens are then kept in the Laravel cache (use a persistent store such as redis, database or file).

1. While logged in to your app, open `https://your-app.com/toconline/oauth/authorize` (or `/toconline/oauth/authorize/{connection}`).
2. Log in on TOConline and accept. You are redirected back to `/toconline/oauth/callback` and the tokens are stored.

The `access_token` lasts 4h and is renewed automatically with the `refresh_token`, which lasts 8h.
Once the `refresh_token` expires the authorization has to be repeated, so schedule a refresh to keep it alive:

```php
// routes/console.php
Schedule::command('toconline:refresh-token')->everyTwoHours();
```

The routes use the `web` and `auth` middleware by default, configurable in `toconline.routes.middleware`.

## Testing the connection

`php artisan toconline:test {connection=default}` checks the configuration, the authorization, the tokens and makes an API request.

- `--code=CODE` exchanges an `authorization_code` copied from the browser redirect URL.
- `--refresh` forces a token refresh and shows whether TOConline returned a new `refresh_token`.

### Without a Laravel app (package development)

```bash
composer install
cp .env.example .env   # fill in TOC_CLIENT_ID and TOC_CLIENT_SECRET
vendor/bin/testbench toconline:test
```

If there are no tokens yet, the command prints the authorization URL. Open it, log in, copy the `code`
parameter from the URL you are redirected to and run `vendor/bin/testbench toconline:test --code=CODE`.
Tokens are kept in the file cache, so the following runs reuse them.

## Usage

```php
use Mupy\TOConline\Facades\TOConline;

try {
    $docs = TOConline::api()->documents();
} catch (\Throwable $th) {
    // Handle exceptions as needed
}
```

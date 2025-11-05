<p align="center">
    <a href="https://github.com/peter9x/laravel-toconline-api/actions"><img src="https://github.com/peter9x/laravel-bc/actions/workflows/php.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/peter9x/laravel-toconline-api"><img src="https://img.shields.io/packagist/v/peter9x/laravel-toconline-api" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/peter9x/laravel-toconline-api"><img src="https://img.shields.io/packagist/l/peter9x/laravel-toconline-api" alt="License"></a>
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
TOC_URI_OAUTH=https://example.com/callback
```
4. Optional config to add on the `.env` file:
```env
TOC_BASE_URL=https://example.com
TOC_BASE_URL_OAUTH=https://example.com/oauth
```

## Usage

```php
use Mupy\TOConline\Facades\TOConline;

try {
    $docs = TOConline::api()->documents();
} catch (\Throwable $th) {
    // Handle exceptions as needed
}
```
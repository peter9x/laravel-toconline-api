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
```
4. Optional config to add on the `.env` file:
```env
TOC_BASE_URL=https://example.com
TOC_BASE_URL_OAUTH=https://example.com/oauth
TOC_URI_OAUTH=https://example.com/callback
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
try {

        //$response = $tocClient->request('GET', 'api/commercial_sales_documents');

        //$docs = $tocClient->documents()->paginate(1, 10);
        //$doc = $tocClient->documents()->find(76);
        $doc = $tocClient->getDocument(76);

        dd($doc);

    } catch (\Throwable $th) {
        throw($th);
        // Handle exceptions as needed
    }
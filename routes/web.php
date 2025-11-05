<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'toconline',
    'middleware' => ['web'],
], function () {
    Route::get('/oauth/callback', function (Request $request) {
        $code = $request->query('code');
    })->name('toconline.callback');
});

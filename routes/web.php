<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mupy\TOConline\TOConlineClient;

Route::group([
    'prefix' => 'toconline',
    'middleware' => config('toconline.routes.middleware', ['web', 'auth']),
], function () {
    Route::get('/oauth/authorize/{connection?}', function (TOConlineClient $toconline, string $connection = 'default') {
        $state = Str::random(40);
        session()->put("toconline_oauth_state.{$state}", $connection);

        return redirect()->away($toconline->api($connection)->auth()->getAuthorizationUrl($state));
    })->name('toconline.authorize');

    Route::get('/oauth/callback', function (Request $request, TOConlineClient $toconline) {
        $state = (string) $request->query('state', '');
        $connection = $state !== '' ? session()->pull("toconline_oauth_state.{$state}") : null;

        abort_if($connection === null, 403, 'Estado OAuth inválido. Inicie a autorização em '.route('toconline.authorize'));

        if ($request->filled('error')) {
            abort(400, 'TOConline recusou a autorização: '.$request->query('error_description', $request->query('error')));
        }

        $code = (string) $request->query('code', '');
        abort_if($code === '', 400, 'authorization_code em falta no callback.');

        $toconline->api($connection)->auth()->exchangeAuthorizationCode($code);

        return response("TOConline autorizado com sucesso (ligação: {$connection}).");
    })->name('toconline.callback');
});

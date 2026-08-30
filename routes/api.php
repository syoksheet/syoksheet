<?php

use Illuminate\Support\Facades\Route;

/*
| The sold API. JSON via Sanctum bearer tokens. No `/api` prefix: the host says it.
| `/v1` carries `user:api` token routes; `/admin/v1` will carry `admin:api` ones.
*/

Route::prefix('v1')->group(function () {
    Route::get('/', fn () => response('api'))->name('api.v1.index');
});

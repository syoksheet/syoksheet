<?php

use Illuminate\Support\Facades\Route;

/*
| The apex: marketing, public walls, the jobs directory and policy pages.
| Server-rendered Blade, SEO-first, no auth.
*/

Route::get('/', fn () => response('public'))->name('public.home');
